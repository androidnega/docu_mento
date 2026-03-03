<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Concerns\InteractsWithAdminSession;
use App\Models\AttendanceUploadLog;
use App\Rules\YearGroupLevelRule;
use App\Models\AcademicClass;
use App\Models\ClassGroup;
use App\Models\Semester;
use App\Models\ClassGroupStudent;
use App\Models\Otp;
use App\Models\Student;
use App\Models\User;
use App\Services\ArkeselService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Database\QueryException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;
use App\Exports\ClassGroupStudentsExport;

class ClassGroupController extends Controller
{
    use InteractsWithAdminSession;

    private function classGroupIds(): array
    {
        $user = $this->adminUser();
        return $user ? $user->classGroupIds() : [];
    }

    /**
     * Resolve class group from student record (source of truth for nested URLs).
     * Optionally redirect GET pages to canonical URL when classGroupId in URL is stale.
     */
    private function resolveStudentClassGroup(
        string $classGroupId,
        ClassGroupStudent $student,
        string $ability,
        ?string $canonicalRoute = null
    ): ClassGroup|RedirectResponse {
        $classGroup = $student->classGroup;
        if (! $classGroup) {
            abort(404);
        }
        $this->authorize($ability, $classGroup);

        if ($canonicalRoute && (string) $classGroupId !== (string) $classGroup->getRouteKey()) {
            return redirect()->route($this->staffRoutePrefix() . '.' . $canonicalRoute, [
                'classGroupId' => $classGroup->getRouteKey(),
                'student' => $student->getRouteKey(),
            ]);
        }

        return $classGroup;
    }

    /**
     * Sync student account Docu Mento context from the class group (no level; access is group-driven).
     */
    private function syncStudentFromClassGroup(\App\Models\Student $studentAccount, ClassGroup $classGroup): void
    {
        $classGroup->load(['academicYear']);
        $studentAccount->semester_id = $classGroup->semester_id;
        $studentAccount->academic_year_id = $classGroup->academic_year_id;
        $studentAccount->academic_class_id = $classGroup->academic_class_id;
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', ClassGroup::class);
        $ids = $this->classGroupIds();

        $query = ClassGroup::query()
            ->withCount('students')
            ->whereIn('id', $ids)
            ->orderBy('name');

        $supervisorId = $request->query('supervisor_id');
        if ($supervisorId) {
            $query->where('supervisor_id', $supervisorId);
        }

        $academicYearId = $request->query('academic_year_id');
        if ($academicYearId && \Illuminate\Support\Facades\Schema::hasColumn('class_groups', 'academic_year_id')) {
            $query->where('academic_year_id', $academicYearId);
        }

        $classGroups = $query->paginate(24)->withQueryString();

        $user = $this->adminUser();

        $supervisors = $user?->isDocuMentorCoordinator()
            ? $user->supervisorsInScope()->get(['id', 'username', 'name'])
            : collect();
        $academicYears = \App\Models\DocuMentor\AcademicYear::orderBy('year', 'desc')->get(['id', 'year']);

        $classGroupIdsWithLiveSessions = [];
        $isSuperAdmin = $user && $user->isSuperAdmin();

        return view('admin.class-groups.index', compact('classGroups', 'supervisors', 'academicYears', 'classGroupIdsWithLiveSessions', 'isSuperAdmin'));
    }

    public function create(): View
    {
        $this->authorize('create', ClassGroup::class);
        $user = $this->adminUser();
        $supervisors = $user?->isDocuMentorCoordinator()
            ? $user->supervisorsInScope()->get(['id', 'username', 'name'])
            : collect();
        $levels = \App\Models\StudentLevel::ordered();
        $semesters = Semester::orderBy('sort_order')->orderBy('name')->get();
        $academicYears = \App\Models\DocuMentor\AcademicYear::orderBy('year', 'desc')->get();
        $academicClasses = AcademicClass::with('academicYear')->orderBy('name')->get();
        $accentColors = ClassGroup::ACCENT_COLORS;
        $allowedDevicesOptions = Schema::hasColumn('class_groups', 'allowed_devices') ? ClassGroup::allowedDevicesOptions() : [];
        return view('admin.class-groups.create', compact('supervisors', 'levels', 'semesters', 'academicYears', 'academicClasses', 'accentColors', 'allowedDevicesOptions'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', ClassGroup::class);
        $user = $this->adminUser();
        if (!$user) {
            return redirect()->route('login')->with('error', 'Error');
        }

        $supervisorId = $request->filled('supervisor_id') ? (int) $request->supervisor_id : null;
        $supervisorUser = $supervisorId ? User::find($supervisorId) : null;
        if (!$supervisorUser || $supervisorUser->role !== User::ROLE_SUPERVISOR) {
            return redirect()->route($this->staffRoutePrefix() . '.class-groups.create')
                ->withInput()->with('error', 'Please select a valid supervisor.');
        }

        $rules = [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('class_groups', 'name')->where('supervisor_id', $supervisorId),
            ],
            'supervisor_id' => 'required|exists:users,id',
            'level_id' => ['required', 'exists:student_levels,id', new YearGroupLevelRule((int) $request->academic_year_id, (int) $request->level_id)],
            'semester_id' => 'required|exists:semesters,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'academic_class_id' => 'nullable|exists:academic_classes,id',
        ];
        if (Schema::hasColumn('class_groups', 'allowed_devices')) {
            $rules['allowed_devices'] = 'nullable|in:desktop,mobile,both';
        }
        $request->validate($rules);

        $accentColor = $request->filled('accent_color') && array_key_exists($request->accent_color, ClassGroup::ACCENT_COLORS)
            ? $request->accent_color
            : ClassGroup::nextAccentColor();

        $createData = [
            'name' => trim($request->name),
            'supervisor_id' => $supervisorId,
            'level_id' => (int) $request->level_id,
            'semester_id' => (int) $request->semester_id,
            'academic_year_id' => (int) $request->academic_year_id,
            'academic_class_id' => $request->filled('academic_class_id') ? (int) $request->academic_class_id : null,
            'accent_color' => $accentColor,
        ];
        if (Schema::hasColumn('class_groups', 'allowed_devices')) {
            $createData['allowed_devices'] = in_array($request->input('allowed_devices'), [ClassGroup::ALLOWED_DEVICES_DESKTOP, ClassGroup::ALLOWED_DEVICES_MOBILE, ClassGroup::ALLOWED_DEVICES_BOTH], true)
                ? $request->input('allowed_devices')
                : ClassGroup::ALLOWED_DEVICES_DESKTOP;
        }
        $classGroup = ClassGroup::create($createData);

        return redirect()->route($this->staffRoutePrefix() . '.class-groups.show', $classGroup)
            ->with('success', 'Saved');
    }

    public function show(ClassGroup $classGroup): View
    {
        $this->authorize('view', $classGroup);
        $classGroup->load(['supervisor:id,username,name', 'level']);
        $students = $classGroup->students()->orderBy('index_number')->paginate(20);
        return view('admin.class-groups.show', compact('classGroup', 'students'));
    }

    public function edit(ClassGroup $classGroup): View|RedirectResponse
    {
        $this->authorize('update', $classGroup);
        $user = $this->adminUser();
        if ($user?->isDocuMentorSupervisor()) {
            return redirect()->route($this->staffRoutePrefix() . '.class-groups.show', $classGroup)
                ->with('error', 'Only coordinators can edit the class group structure.');
        }
        $classGroup->load(['supervisor:id,username,name', 'level']);
        $supervisors = $user?->isDocuMentorCoordinator()
            ? $user->supervisorsInScope()->get(['id', 'username', 'name'])
            : collect();
        $levels = \App\Models\StudentLevel::ordered();
        $semesters = Semester::orderBy('sort_order')->orderBy('name')->get();
        $academicYears = \App\Models\DocuMentor\AcademicYear::orderBy('year', 'desc')->get();
        $academicClasses = AcademicClass::orderBy('name')->get();
        $accentColors = ClassGroup::ACCENT_COLORS;
        $allowedDevicesOptions = Schema::hasColumn('class_groups', 'allowed_devices') ? ClassGroup::allowedDevicesOptions() : [];
        return view('admin.class-groups.edit', compact('classGroup', 'supervisors', 'levels', 'semesters', 'academicYears', 'academicClasses', 'accentColors', 'allowedDevicesOptions'));
    }

    public function update(Request $request, ClassGroup $classGroup): RedirectResponse
    {
        $this->authorize('update', $classGroup);
        $supervisorId = $request->filled('supervisor_id') ? (int) $request->supervisor_id : $classGroup->supervisor_id;
        $supervisorUser = $supervisorId ? User::find($supervisorId) : null;
        if (!$supervisorUser || $supervisorUser->role !== User::ROLE_SUPERVISOR) {
            return redirect()->route($this->staffRoutePrefix() . '.class-groups.edit', $classGroup)
                ->withInput()->with('error', 'Please select a valid supervisor.');
        }

        $rules = [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('class_groups', 'name')->where('supervisor_id', $supervisorId)->ignore($classGroup->id),
            ],
            'supervisor_id' => 'required|exists:users,id',
            'level_id' => ['required', 'exists:student_levels,id', new YearGroupLevelRule((int) $request->academic_year_id, (int) $request->level_id)],
            'semester_id' => 'required|exists:semesters,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'academic_class_id' => 'nullable|exists:academic_classes,id',
        ];
        if (Schema::hasColumn('class_groups', 'allowed_devices')) {
            $rules['allowed_devices'] = 'nullable|in:desktop,mobile,both';
        }
        $request->validate($rules);

        $accentColor = $request->filled('accent_color') && array_key_exists($request->accent_color, ClassGroup::ACCENT_COLORS)
            ? $request->accent_color
            : $classGroup->accent_color;

        $updateData = [
            'name' => trim($request->name),
            'supervisor_id' => $supervisorId,
            'level_id' => (int) $request->level_id,
            'semester_id' => (int) $request->semester_id,
            'academic_year_id' => (int) $request->academic_year_id,
            'academic_class_id' => $request->filled('academic_class_id') ? (int) $request->academic_class_id : null,
            'accent_color' => $accentColor,
        ];
        if (Schema::hasColumn('class_groups', 'allowed_devices')) {
            $reqAllowed = $request->input('allowed_devices');
            $validDevices = [ClassGroup::ALLOWED_DEVICES_DESKTOP, ClassGroup::ALLOWED_DEVICES_MOBILE, ClassGroup::ALLOWED_DEVICES_BOTH];
            $updateData['allowed_devices'] = in_array($reqAllowed, $validDevices, true)
                ? $reqAllowed
                : ($classGroup->getAttribute('allowed_devices') ?? ClassGroup::ALLOWED_DEVICES_DESKTOP);
        }
        $classGroup->update($updateData);

        return redirect()->route($this->staffRoutePrefix() . '.class-groups.show', $classGroup)->with('success', 'Saved');
    }

    /**
     * Update allowed_devices for a class group (coordinator toggle on show page).
     */
    public function updateAllowedDevices(Request $request, ClassGroup $classGroup): RedirectResponse
    {
        $this->authorize('update', $classGroup);
        if (!Schema::hasColumn('class_groups', 'allowed_devices')) {
            return redirect()->route($this->staffRoutePrefix() . '.class-groups.show', $classGroup)
                ->with('error', 'Device restrictions are not supported in this installation.');
        }
        $request->validate([
            'allowed_devices' => 'required|in:desktop,mobile,both',
        ]);
        $allowed = $request->input('allowed_devices');
        $classGroup->update([
            'allowed_devices' => $allowed,
        ]);

        return redirect()->route($this->staffRoutePrefix() . '.class-groups.show', $classGroup)
            ->with('success', 'Allowed devices updated for this class group.');
    }

    public function destroy(ClassGroup $classGroup): RedirectResponse
    {
        $this->authorize('delete', $classGroup);
        $name = $classGroup->name;

        // Before deleting the class group, cascade cleanup for all students in this group
        // so that Docu Mentor group leaders and memberships do not keep stale references.
        $removedIndices = $classGroup->students()->pluck('index_number');
        foreach ($removedIndices as $removedIndex) {
            \App\Models\Student::deleteEverywhereByIndex($removedIndex);
            $indexUpper = strtoupper(trim($removedIndex));
            \Illuminate\Support\Facades\Cache::forget('student_otp:' . $removedIndex);
            \Illuminate\Support\Facades\Cache::forget('student_otp:' . $indexUpper);
        }
        // Remove class group student rows (any remaining links are now safe to drop).
        $classGroup->students()->delete();

        try {
            $classGroup->delete();
        } catch (QueryException $e) {
            report($e);

            return redirect()
                ->route($this->staffRoutePrefix() . '.class-groups.index')
                ->with('error', "Could not delete class group '{$name}' because related records still depend on it.");
        }

        return redirect()
            ->route($this->staffRoutePrefix() . '.class-groups.index')
            ->with('success', 'Deleted');
    }

    /** Show the student indices management page for this class group. */
    public function studentsIndex(Request $request, ClassGroup $classGroup): View
    {
        $this->authorize('view', $classGroup);
        $classGroup->load(['supervisor:id,username,name', 'level']);
        $search = $request->input('search', '');
        // Eager load studentAccount with phone_contact and student_name fields
        $query = $classGroup->students()->with(['studentAccount' => function ($q) {
            $q->select('id', 'index_number', 'phone_contact', 'student_name');
        }])->orderBy('index_number');
        if ($search !== '') {
            $term = '%' . preg_replace('/%/', '\\%', trim($search)) . '%';
            $query->where(function ($q) use ($term) {
                $q->where('index_number', 'like', $term)
                    ->orWhere('student_name', 'like', $term)
                    ->orWhereHas('studentAccount', function ($q2) use ($term) {
                        $q2->where('phone_contact', 'like', $term)
                            ->orWhere('student_name', 'like', $term);
                    });
            });
        }
        $students = $query->paginate(30)->withQueryString();
        $isSuperAdmin = $this->adminUser()?->isSuperAdmin() ?? false;

        if ($request->boolean('ajax')) {
            $html = view('admin.class-groups.partials.students-rows', compact('classGroup', 'students', 'isSuperAdmin'))->render();
            return response()->json([
                'html' => $html,
                'next_page_url' => $students->hasMorePages() ? $students->nextPageUrl() . '&ajax=1' : null,
            ]);
        }

        return view('admin.class-groups.students', compact('classGroup', 'students', 'isSuperAdmin', 'search'));
    }

    /** Add a single student to the class group. Level and program context inherit from the class group. */
    public function addStudent(Request $request, ClassGroup $classGroup): RedirectResponse
    {
        $this->authorize('update', $classGroup);
        $request->validate([
            'index_number' => 'required|string|max:64',
            'student_name' => 'nullable|string|max:255',
        ]);

        $indexNumber = trim($request->index_number);
        $providedName = $request->filled('student_name') ? trim($request->student_name) : null;

        ClassGroupStudent::updateOrCreate(
            [
                'class_group_id' => $classGroup->id,
                'index_number' => $indexNumber,
            ],
            ['student_name' => $providedName]
        );

        $hash = \App\Models\Student::hashIndexNumber($indexNumber);
        $studentAccount = \App\Models\Student::firstOrCreate(
            ['index_number_hash' => $hash],
            ['index_number' => $indexNumber, 'index_number_hash' => $hash, 'student_name' => $providedName]
        );
        $studentAccount->student_name = $providedName ?? $studentAccount->student_name;
        $this->syncStudentFromClassGroup($studentAccount, $classGroup);
        $studentAccount->save();

        return redirect()->route($this->staffRoutePrefix() . '.class-groups.students.index', $classGroup)
            ->with('success', 'Saved');
    }

    /** Delete all student indices in this class group. Coordinator/super admin only. Use when re-uploading a fresh list. */
    public function clearStudents(ClassGroup $classGroup): RedirectResponse
    {
        $this->authorize('update', $classGroup);
        $count = $classGroup->students()->count();

        // Collect indices first so we can cascade cleanup across Docu Mento.
        $removedIndices = $classGroup->students()->pluck('index_number');
        foreach ($removedIndices as $removedIndex) {
            \App\Models\Student::deleteEverywhereByIndex($removedIndex);
            // Clear any cached OTP data for this index (legacy cache keys).
            $indexUpper = strtoupper(trim($removedIndex));
            \Illuminate\Support\Facades\Cache::forget('student_otp:' . $removedIndex);
            \Illuminate\Support\Facades\Cache::forget('student_otp:' . $indexUpper);
        }

        $classGroup->students()->delete();
        return redirect()->route($this->staffRoutePrefix() . '.class-groups.show', $classGroup)
            ->with('success', $count > 0 ? "All {$count} index numbers have been removed. You can re-upload or add students again." : 'Student index list is already empty.');
    }

    /** Show details page for one student in the class group. */
    public function showStudent(string $classGroupId, ClassGroupStudent $student): View|RedirectResponse
    {
        $resolved = $this->resolveStudentClassGroup($classGroupId, $student, 'view', 'class-groups.students.show');
        if ($resolved instanceof RedirectResponse) {
            return $resolved;
        }
        $classGroup = $resolved;
        
        $student->load('studentAccount');
        $studentAccount = $student->studentAccount;
        $phone = $studentAccount?->phone_contact ?? null;
        
        // Display name priority: student account name > class group name > "—"
        $displayName = $studentAccount?->student_name ?? $student->student_name ?? '—';

        return view('admin.class-groups.student-show', compact(
            'classGroup',
            'student',
            'studentAccount',
            'phone',
            'displayName'
        ));
    }

    /** Show edit form for one student in the class group. */
    public function editStudent(string $classGroupId, ClassGroupStudent $student): View|RedirectResponse
    {
        $resolved = $this->resolveStudentClassGroup($classGroupId, $student, 'update', 'class-groups.students.edit');
        if ($resolved instanceof RedirectResponse) {
            return $resolved;
        }
        $classGroup = $resolved;
        
        $student->load('studentAccount');
        $studentAccount = $student->studentAccount;
        $phone = $studentAccount?->phone_contact ?? null;
        return view('admin.class-groups.student-edit', compact('classGroup', 'student', 'studentAccount', 'phone'));
    }

    /** Update a student index/name/phone in the class group. */
    public function updateStudent(Request $request, string $classGroupId, ClassGroupStudent $student): RedirectResponse
    {
        $resolved = $this->resolveStudentClassGroup($classGroupId, $student, 'update');
        if ($resolved instanceof RedirectResponse) {
            return $resolved;
        }
        $classGroup = $resolved;
        $request->validate([
            'index_number' => 'required|string|max:64',
            'student_name' => 'nullable|string|max:255',
            'phone_contact' => 'nullable|string|max:20',
        ]);
        $indexNumber = trim($request->index_number);
        $name = $request->filled('student_name') ? trim($request->student_name) : null;
        $phoneRaw = $request->filled('phone_contact') ? trim($request->phone_contact) : null;
        $phone = $phoneRaw ? Student::normalizePhoneForStorage($phoneRaw) : null;
        
        // If index changed, ensure no duplicate (unique is class_group_id + index_number)
        if (strcasecmp($student->index_number, $indexNumber) !== 0) {
            if (ClassGroupStudent::where('class_group_id', $classGroup->id)->where('id', '!=', $student->id)->whereRaw('UPPER(TRIM(index_number)) = ?', [strtoupper($indexNumber)])->exists()) {
                return redirect()->route($this->staffRoutePrefix() . '.class-groups.students.index', $classGroup)
                    ->with('error', 'Error');
            }
        }
        
        $student->index_number = $indexNumber;
        $student->student_name = $name;
        $student->save();
        
        // Update or create Student account (for phone and level)
        $hash = \App\Models\Student::hashIndexNumber($indexNumber);
        $studentAccount = \App\Models\Student::firstOrCreate(
            ['index_number_hash' => $hash],
            ['index_number' => $indexNumber, 'index_number_hash' => $hash, 'student_name' => $name]
        );
        $studentAccount->student_name = $name ?? $studentAccount->student_name;
        $this->syncStudentFromClassGroup($studentAccount, $classGroup);
        if ($phone !== null) {
            $otherStudent = \App\Models\Student::where('phone_contact', $phone)->where('id', '!=', $studentAccount->id)->first();
            if ($otherStudent) {
                return redirect()->route($this->staffRoutePrefix() . '.class-groups.students.edit', [$classGroup, $student])
                    ->withInput()
                    ->with('error', 'This phone number is already in use by another student.');
            }
            $studentAccount->phone_contact = $phone;
        } else {
            $studentAccount->phone_contact = null;
        }

        try {
            $studentAccount->save();
        } catch (QueryException $e) {
            $message = 'Could not save. ';
            if (str_contains($e->getMessage(), 'Duplicate') || str_contains($e->getMessage(), 'unique')) {
                $message .= 'This phone number may already be in use.';
            } else {
                $message .= 'Please try again or contact support.';
            }
            return redirect()->route($this->staffRoutePrefix() . '.class-groups.students.edit', [$classGroup, $student])
                ->withInput()
                ->with('error', $message);
        }

        return redirect()->route($this->staffRoutePrefix() . '.class-groups.students.show', [$classGroup, $student])->with('success', 'Saved');
    }

    /** Upload Excel to replace or merge class group students. */
    public function uploadStudents(Request $request, ClassGroup $classGroup): RedirectResponse
    {
        $this->authorize('update', $classGroup);
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
            'upload_mode' => 'required|in:replace,merge',
        ]);

        $file = $request->file('file');
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();
        $header = array_shift($rows);
        $indexCol = 0;
        $nameCol = 1;
        foreach ($header as $i => $h) {
            $h = is_string($h) ? strtolower($h) : '';
            if (str_contains($h, 'index') || $i === 0) {
                $indexCol = $i;
            }
            if (str_contains($h, 'name') || str_contains($h, 'student')) {
                $nameCol = $i;
            }
        }
        $byIndex = [];
        foreach ($rows as $row) {
            $index = trim((string) ($row[$indexCol] ?? ''));
            if ($index === '') {
                continue;
            }
            $name = isset($row[$nameCol]) ? trim((string) $row[$nameCol]) : null;
            $byIndex[$index] = ['name' => $name];
        }

        $mode = $request->input('upload_mode');
        $rowsAdded = 0;
        $rowsUpdated = 0;
        $rowsDeleted = 0;

        if ($mode === 'replace') {
            $rowsDeleted = $classGroup->students()->count();

            // Delete ALL data for removed students (complete reset across Docu Mento).
            $removedIndices = $classGroup->students()->pluck('index_number');
            foreach ($removedIndices as $removedIndex) {
                \App\Models\Student::deleteEverywhereByIndex($removedIndex);
                $indexUpper = strtoupper(trim($removedIndex));
                \Illuminate\Support\Facades\Cache::forget('student_otp:' . $removedIndex);
                \Illuminate\Support\Facades\Cache::forget('student_otp:' . $indexUpper);
            }

            $classGroup->students()->delete();
        }
        
        $classGroup->load(['level', 'academicYear']);
        foreach ($byIndex as $index => $data) {
            $indexTrimmed = trim($index);
            $name = is_array($data) ? ($data['name'] ?? null) : $data;
            $name = $name ? trim($name) : null;

            $existing = ClassGroupStudent::where('class_group_id', $classGroup->id)->where('index_number', $indexTrimmed)->first();
            ClassGroupStudent::updateOrCreate(
                ['class_group_id' => $classGroup->id, 'index_number' => $indexTrimmed],
                ['student_name' => $name]
            );

            $hash = \App\Models\Student::hashIndexNumber($indexTrimmed);
            $studentAccount = \App\Models\Student::firstOrCreate(
                ['index_number_hash' => $hash],
                ['index_number' => $indexTrimmed, 'index_number_hash' => $hash, 'student_name' => $name]
            );
            $studentAccount->student_name = $name ?? $studentAccount->student_name;
            $this->syncStudentFromClassGroup($studentAccount, $classGroup);
            $studentAccount->save();

            if ($existing) {
                $rowsUpdated++;
            } else {
                $rowsAdded++;
            }
        }

        AttendanceUploadLog::create([
            'class_group_id' => $classGroup->id,
            'uploaded_by' => $this->adminUser()?->id,
            'upload_mode' => $mode,
            'rows_added' => $rowsAdded,
            'rows_updated' => $rowsUpdated,
            'rows_deleted' => $rowsDeleted,
            'uploaded_at' => now(),
        ]);

        $message = $mode === 'replace'
            ? 'Student list replaced with ' . count($byIndex) . ' indices.'
            : 'Merged ' . count($byIndex) . ' indices into the class group.';

        // Send 14-day reusable student_login OTP by SMS. Deduct from coordinator or class group supervisor.
        $classGroup->load('supervisor');
        $smsOwner = \App\Models\User::coordinatorWithSmsBalanceForClassGroup($classGroup);
        if (! $smsOwner && $classGroup->supervisor && $classGroup->supervisor->isDocuMentorSupervisor() && $classGroup->supervisor->sms_remaining > 0) {
            $smsOwner = $classGroup->supervisor;
        }
        if ($smsOwner) {
            $smsOwner->refresh();
            $remaining = $smsOwner->sms_remaining;
            if ($remaining > 0) {
                $studentsInGroup = $classGroup->students()->get();
                foreach ($studentsInGroup as $cgStudent) {
                    if ($remaining <= 0) {
                        break;
                    }
                    $indexNumber = strtoupper(trim($cgStudent->index_number));
                    $indexHash = Student::hashIndexNumber($indexNumber);
                    $studentAccount = Student::where('index_number_hash', $indexHash)->first();
                    if (!$studentAccount || !$studentAccount->hasPhone()) {
                        continue;
                    }
                    $code = (string) random_int(100000, 999999);
                    Otp::create([
                        'index_number_hash' => $indexHash,
                        'type' => Otp::TYPE_STUDENT_LOGIN,
                        'code' => $code,
                        'expires_at' => now()->addDays(Otp::STUDENT_LOGIN_VALID_DAYS),
                    ]);
                    $smsMessage = 'Your Docu Mento login code is: ' . $code . '. Valid for 90 days. Do not share.';
                    $result = ArkeselService::sendSms($studentAccount->phone_contact, $smsMessage);
                    if ($result['success']) {
                        $smsOwner->increment('sms_used');
                        $remaining--;
                    }
                }
            }
        }
        return redirect()->route($this->staffRoutePrefix() . '.class-groups.students.index', $classGroup)->with('success', 'Saved');
    }

    /**
     * Bulk remove multiple students from the class group.
     *
     * Mirrors the cascading clean-up performed in destroyStudent()
     * for each selected student.
     */
    public function bulkDestroyStudents(Request $request, ClassGroup $classGroup): RedirectResponse
    {
        $this->authorize('update', $classGroup);

        $ids = $request->input('student_ids', []);
        if (!is_array($ids) || count($ids) === 0) {
            return redirect()
                ->route($this->staffRoutePrefix() . '.class-groups.students.index', $classGroup)
                ->with('error', 'No students selected.');
        }

        $students = ClassGroupStudent::where('class_group_id', $classGroup->id)
            ->whereIn('id', $ids)
            ->get();

        if ($students->isEmpty()) {
            return redirect()
                ->route($this->staffRoutePrefix() . '.class-groups.students.index', $classGroup)
                ->with('error', 'No valid students selected.');
        }

        foreach ($students as $student) {
            $indexNumber = $student->index_number;
            \App\Models\Student::deleteEverywhereByIndex($indexNumber);

            // Delete class group student record
            $student->delete();
        }

        return redirect()
            ->route($this->staffRoutePrefix() . '.class-groups.students.index', $classGroup)
            ->with('success', 'Selected students deleted.');
    }

    /** Remove a student from the class group. */
    public function destroyStudent(string $classGroupId, ClassGroupStudent $student): RedirectResponse
    {
        $resolved = $this->resolveStudentClassGroup($classGroupId, $student, 'update');
        if ($resolved instanceof RedirectResponse) {
            return $resolved;
        }
        $classGroup = $resolved;
        
        $indexNumber = $student->index_number;
        \App\Models\Student::deleteEverywhereByIndex($indexNumber);

        // Delete class group student record
        $student->delete();
        
        return redirect()->route($this->staffRoutePrefix() . '.class-groups.students.index', $classGroup)
            ->with('success', 'Deleted');
    }

    /** Remove phone number from a student. */
    public function removeStudentPhone(string $classGroupId, ClassGroupStudent $student): RedirectResponse
    {
        $resolved = $this->resolveStudentClassGroup($classGroupId, $student, 'update');
        if ($resolved instanceof RedirectResponse) {
            return $resolved;
        }
        $classGroup = $resolved;
        
        $indexHash = \App\Models\Student::hashIndexNumber($student->index_number);
        $studentAccount = \App\Models\Student::where('index_number_hash', $indexHash)->first();
        if ($studentAccount) {
            $studentAccount->phone_contact = null;
            $studentAccount->save();
            Otp::where('index_number_hash', $indexHash)->where('type', Otp::TYPE_STUDENT_LOGIN)->delete();
            return redirect()->route($this->staffRoutePrefix() . '.class-groups.students.index', $classGroup)->with('success', 'Removed');
        }
        
        return redirect()->route($this->staffRoutePrefix() . '.class-groups.students.index', $classGroup)->with('error', 'Not found');
    }

    /**
     * Generate a one-time fallback login code for a student (supervisor or super admin/coordinator).
     * Code is displayed on screen for staff to give to the student; not sent via SMS.
     */
    public function generateFallbackCode(Request $request, string $classGroupId, ClassGroupStudent $student): RedirectResponse
    {
        $classGroup = $student->classGroup;
        if (! $classGroup || (string) $classGroupId !== (string) $classGroup->getRouteKey()) {
            abort(404);
        }
        $this->authorize('generateFallbackCode', $classGroup);

        $code = (string) random_int(100000, 999999);
        $indexHash = Student::hashIndexNumber($student->index_number);
        Otp::create([
            'index_number_hash' => $indexHash,
            'type' => Otp::TYPE_SUPERVISOR_FALLBACK,
            'code' => $code,
            'expires_at' => now()->addDays(Otp::SUPERVISOR_FALLBACK_VALID_DAYS),
        ]);

        return redirect()->route($this->staffRoutePrefix() . '.class-groups.students.show', [$classGroup, $student])
            ->with('success', 'One-time login code generated. Give it to the student. Valid for ' . Otp::SUPERVISOR_FALLBACK_VALID_DAYS . ' days.')
            ->with('fallback_code', $code);
    }

    /**
     * Export class group students list as Excel.
     */
    public function exportStudentsExcel(ClassGroup $classGroup): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $this->authorize('view', $classGroup);
        $filename = 'class-list-' . \Illuminate\Support\Str::slug($classGroup->name) . '-' . now()->format('Y-m-d-His') . '.xlsx';
        return Excel::download(new ClassGroupStudentsExport($classGroup), $filename);
    }

    /**
     * Export class group students list as PDF.
     */
    public function exportStudentsPdf(ClassGroup $classGroup): Response
    {
        $this->authorize('view', $classGroup);
        $classGroup->load(['supervisor:id,username,name', 'students.studentAccount']);

        $students = $classGroup->students()
            ->with('studentAccount')
            ->orderBy('index_number')
            ->get();

        $supervisorUser = $classGroup->supervisor;
        $lecturerName = $supervisorUser ? ($supervisorUser->name ?: $supervisorUser->username) : '—';
        $courseName = $classGroup->name;
        
        $institutionName = \App\Models\Setting::getValue(\App\Models\Setting::KEY_INSTITUTION_NAME, '');
        $logoPath = \App\Models\Setting::getValue(\App\Models\Setting::KEY_INSTITUTION_LOGO, '');
        $institutionLogoPath = null;
        if ($logoPath) {
            if (str_starts_with($logoPath, 'http')) {
                try {
                    $response = \Illuminate\Support\Facades\Http::timeout(10)->get($logoPath);
                    if ($response->successful()) {
                        $body = $response->body();
                        $mime = $response->header('Content-Type') ?: 'image/png';
                        $institutionLogoPath = 'data:' . (explode(';', $mime)[0] ?: 'image/png') . ';base64,' . base64_encode($body);
                    }
                } catch (\Throwable $e) {
                    // omit logo on fetch failure
                }
            } else {
                $fullPath = storage_path('app/public/' . $logoPath);
                if (file_exists($fullPath)) {
                    $mime = @mime_content_type($fullPath) ?: 'image/png';
                    $institutionLogoPath = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($fullPath));
                }
            }
        }
        
        $classGroupName = $classGroup->name;
        $reportDate = now()->format('F j, Y');
        
        $pdf = Pdf::loadView('admin.class-groups.export-pdf', [
            'classGroup' => $classGroup,
            'classGroupName' => $classGroupName,
            'students' => $students,
            'lecturerName' => $lecturerName,
            'courseName' => $courseName,
            'reportDate' => $reportDate,
            'institutionName' => $institutionName,
            'institutionLogoPath' => $institutionLogoPath,
        ])->setPaper('a4', 'portrait')->setWarnings(false);
        
        $filename = 'class-list-' . \Illuminate\Support\Str::slug($classGroup->name) . '-' . now()->format('Y-m-d') . '.pdf';
        return $pdf->download($filename);
    }
}
