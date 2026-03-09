<?php

namespace App\Http\Controllers\DocuMentor;

use App\Http\Controllers\Admin\Concerns\InteractsWithAdminSession;
use App\Http\Controllers\Controller;
use App\Models\AcademicClass;
use App\Models\ClassGroup;
use App\Models\ClassGroupStudent;
use App\Models\DocuMentor\AcademicYear;
use App\Models\Role;
use App\Models\Semester;
use App\Models\Student;
use App\Models\Supervisor;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;

class CoordinatorStudentController extends Controller
{
    use InteractsWithAdminSession;

    private function classGroupIds(User $user): array
    {
        return $user->classGroupIds();
    }

    private static function decodeIndex(string $encoded): ?string
    {
        $decoded = base64_decode(str_replace(['-', '_'], ['+', '/'], $encoded), true);
        return $decoded !== false ? $decoded : null;
    }

    public static function encodeIndex(string $index): string
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($index));
    }

    private function resolveStudentByIndex(string $encodedIndex, User $user): array
    {
        $decoded = self::decodeIndex($encodedIndex);
        if (!$decoded || trim($decoded) === '') {
            abort(404, 'Student not found.');
        }
        $indexNormalized = strtoupper(trim($decoded));
        $ids = $this->classGroupIds($user);

        if (!empty($ids) && \Illuminate\Support\Facades\Schema::hasTable('class_group_students')) {
            $cgStudent = ClassGroupStudent::whereIn('class_group_id', $ids)
                ->whereRaw('UPPER(TRIM(index_number)) = ?', [$indexNormalized])
                ->first();
            if ($cgStudent) {
                return [$cgStudent->index_number, $ids];
            }
        }

        $dmUser = $user->docuMentorStudentsInScope()
            ->whereRaw('UPPER(TRIM(index_number)) = ?', [$indexNormalized])
            ->first();
        if ($dmUser) {
            return [trim($decoded), []];
        }

        // Uploaded users (students table only, no class group)
        if (Schema::hasTable('students')) {
            $student = Student::whereRaw('UPPER(TRIM(index_number)) = ?', [$indexNormalized])->first();
            if ($student) {
                return [$student->index_number, []];
            }
        }
        abort(404, 'Student not found.');
    }

    public function index(Request $request): View|JsonResponse
    {
        $user = $this->adminUser();
        if (!$user || !$user->isDocuMentorCoordinator()) {
            abort(403, 'Access denied.');
        }

        $ids = $this->classGroupIds($user);

        // Academic years: coordinator → role → department (only years for their department)
        $deptId = $user->coordinatorDepartmentId();
        $academicYearsQuery = AcademicYear::orderBy('year', 'desc');
        if ($deptId !== null) {
            $academicYearsQuery->where(function ($q) use ($deptId) {
                $q->where('department_id', $deptId)->orWhereNull('department_id');
            });
        }
        $academicYears = $academicYearsQuery->get(['id', 'year']);
        $academicClasses = AcademicClass::with('academicYear')->orderBy('name')->get();
        $semesters = Semester::ordered();
        $classGroups = (!empty($ids) && \Illuminate\Support\Facades\Schema::hasTable('class_groups'))
            ? ClassGroup::whereIn('id', $ids)->orderBy('name')->get(['id', 'name'])
            : collect();

        $hasAcademicYearOnUsers = Schema::hasColumn('users', 'academic_year_id');
        // Include both 'leader' and 'group_leader' so counts match regardless of which role value is stored
        $studentRoles = [User::DM_ROLE_STUDENT, User::DM_ROLE_LEADER, User::ROLE_NAME_GROUP_LEADER];

        // Academic year cards: each year with student count and supervisor count (strictly per academic_year_id)
        $academicYearCards = $academicYears->map(function ($ay) use ($hasAcademicYearOnUsers, $studentRoles) {
            $studentsCount = 0;
            $supervisorsCount = 0;
            if ($hasAcademicYearOnUsers) {
                $studentsCount = User::whereIn('role', $studentRoles)
                    ->where('academic_year_id', $ay->id)
                    ->count();
                $supervisorsCount = User::where('role', User::DM_ROLE_SUPERVISOR)
                    ->where('academic_year_id', $ay->id)
                    ->count();
            }

            return (object) [
                'id' => $ay->id,
                'year' => $ay->year,
                'students_count' => $studentsCount,
                'supervisors_count' => $supervisorsCount,
            ];
        });
        $coordinatorDepartmentName = $user->roleModel?->department?->name ?? ($user->department_id ? \App\Models\Department::find($user->department_id)?->name : null) ?? 'Your department';

        // Stats for cards
        $stats = $this->computeStats($user, $ids);

        if ($request->wantsJson()) {
            return $this->fetchStudents($request, $ids, $user);
        }

        return view('docu-mentor.coordinators.students.index', compact(
            'academicYears', 'academicYearCards', 'coordinatorDepartmentName', 'academicClasses', 'semesters', 'classGroups', 'stats'
        ));
    }

    /**
     * View Students for one academic year (coordinator).
     * Filter: User::where('academic_year_id', $yearId) + role student/leader.
     * Supports search (index/name), status filter (active/inactive), and passes group_leader for toggle.
     */
    public function studentsByYear(Request $request, AcademicYear $academicYear): View
    {
        $user = $this->adminUser();
        if (!$user || !$user->isDocuMentorCoordinator()) {
            abort(403, 'Access denied.');
        }
        $this->ensureAcademicYearInScope($user, (int) $academicYear->id);

        // Strict per-academic-year isolation: only students with this exact academic_year_id are shown.
        $query = User::whereIn('role', [User::DM_ROLE_STUDENT, User::DM_ROLE_LEADER]);
        if (Schema::hasColumn('users', 'academic_year_id')) {
            $query->where('academic_year_id', $academicYear->id);
        } else {
            // If the column does not exist, there is no reliable way to distinguish years,
            // so we intentionally return an empty list instead of mixing students across years.
            $query->whereRaw('1 = 0');
        }

        $search = $request->query('search');
        if ($search !== null && trim($search) !== '') {
            $term = '%' . trim($search) . '%';
            $query->where(function ($q) use ($term) {
                $q->where('index_number', 'like', $term)
                    ->orWhere('name', 'like', $term)
                    ->orWhere('email', 'like', $term);
            });
        }
        $statusFilter = $request->query('status');
        if ($statusFilter === 'active') {
            $query->where('is_active', true);
        } elseif ($statusFilter === 'inactive') {
            $query->where('is_active', false);
        }

        $columns = ['id', 'index_number', 'name', 'email', 'is_active'];
        if (Schema::hasColumn('users', 'phone')) {
            $columns[] = 'phone';
        }
        if (Schema::hasColumn('users', 'group_leader')) {
            $columns[] = 'group_leader';
        }
        $students = $query->orderBy('name')->orderBy('index_number')->get($columns);

        return view('docu-mentor.coordinators.academic-years.students', compact('academicYear', 'students'));
    }

    /**
     * View Supervisors for one academic year (coordinator).
     * Filter: User::where('academic_year_id', $yearId) + role supervisor; with assigned projects count.
     */
    public function supervisorsByYear(AcademicYear $academicYear): View
    {
        $user = $this->adminUser();
        if (!$user || !$user->isDocuMentorCoordinator()) {
            abort(403, 'Access denied.');
        }
        $this->ensureAcademicYearInScope($user, (int) $academicYear->id);

        $query = User::where('role', User::DM_ROLE_SUPERVISOR);
        if (Schema::hasColumn('users', 'academic_year_id')) {
            $query->where('academic_year_id', $academicYear->id);
        } else {
            $deptId = $user->coordinatorDepartmentId();
            if ($deptId !== null) {
                $query->where('department_id', $deptId);
            }
        }
        $supervisors = $query
            ->withCount('supervisedProjects')
            ->with(['supervisedProjects.group.members'])
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'phone', 'is_active']);

        foreach ($supervisors as $sup) {
            $studentIds = [];
            foreach ($sup->supervisedProjects as $project) {
                $members = $project->group?->members ?? collect();
                foreach ($members as $member) {
                    if ($member && $member->id !== null) {
                        $studentIds[$member->id] = true;
                    }
                }
            }
            $sup->total_students_count = count($studentIds);
        }

        return view('docu-mentor.coordinators.academic-years.supervisors', compact('academicYear', 'supervisors'));
    }

    /** Dedicated Students list page: filter by academic year (default active). */
    public function studentsList(Request $request): View
    {
        $user = $this->adminUser();
        if (!$user || !$user->isDocuMentorCoordinator()) {
            abort(403, 'Access denied.');
        }
        $deptId = $user->coordinatorDepartmentId();
        $academicYearsQuery = AcademicYear::orderBy('year', 'desc');
        if ($deptId !== null) {
            $academicYearsQuery->where(function ($q) use ($deptId) {
                $q->where('department_id', $deptId)->orWhereNull('department_id');
            });
        }
        $academicYears = $academicYearsQuery->get(['id', 'year']);
        $activeYear = AcademicYear::active();
        $yearId = $request->query('academic_year_id');
        if ($yearId) {
            $academicYear = $academicYears->firstWhere('id', (int) $yearId);
        } else {
            $academicYear = $activeYear && $academicYears->contains('id', $activeYear->id) ? $activeYear : $academicYears->first();
        }
        if (!$academicYear) {
            $academicYear = null;
            $students = collect();
        } else {
            $this->ensureAcademicYearInScope($user, (int) $academicYear->id);
            $query = User::whereIn('role', [User::DM_ROLE_STUDENT, User::DM_ROLE_LEADER]);
            if (Schema::hasColumn('users', 'academic_year_id')) {
                $query->where('academic_year_id', $academicYear->id);
            } else {
                if ($deptId !== null) {
                    $query->where('department_id', $deptId);
                }
            }
            $students = $query->orderBy('name')->orderBy('index_number')
                ->get(['id', 'index_number', 'name', 'email', 'is_active']);
        }
        return view('docu-mentor.coordinators.students.list', compact('academicYears', 'academicYear', 'students'));
    }

    /** Dedicated Supervisors list page: filter by academic year (default active). */
    public function supervisorsList(Request $request): View
    {
        return $this->supervisorsIndex($request);
    }

    /** Supervisors page: all supervisors (no academic year). Prepopulate to assign to projects. Upload, add single, list. */
    public function supervisorsIndex(Request $request): View
    {
        $user = $this->adminUser();
        if (!$user || !$user->isDocuMentorCoordinator()) {
            abort(403, 'Access denied.');
        }
        $deptId = $user->coordinatorDepartmentId();
        $query = User::where('role', User::DM_ROLE_SUPERVISOR);
        if ($deptId !== null) {
            $query->where('department_id', $deptId);
        }

        $supervisors = $query
            ->withCount('supervisedProjects')
            ->with(['supervisedProjects.group.members'])
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'phone', 'is_active']);

        foreach ($supervisors as $sup) {
            $studentIds = [];
            foreach ($sup->supervisedProjects as $project) {
                $members = $project->group?->members ?? collect();
                foreach ($members as $member) {
                    if ($member && $member->id !== null) {
                        $studentIds[$member->id] = true;
                    }
                }
            }
            $sup->total_students_count = count($studentIds);
        }

        return view('docu-mentor.coordinators.supervisors.index', compact('supervisors'));
    }

    /** Add a single user: index number + academic year + role (student or supervisor). */
    public function store(Request $request): RedirectResponse
    {
        $admin = $this->adminUser();
        if (!$admin || !$admin->isDocuMentorCoordinator()) {
            abort(403, 'Access denied.');
        }

        $request->validate([
            'index_number' => 'required|string|max:64',
            'academic_year_id' => $request->role === 'supervisor' ? 'nullable|exists:academic_years,id' : 'required|exists:academic_years,id',
            'role' => 'required|in:student,supervisor',
            'name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
        ]);

        $academicYearId = $request->filled('academic_year_id') ? (int) $request->academic_year_id : null;
        if ($academicYearId && $request->role !== 'supervisor') {
            $this->ensureAcademicYearInScope($admin, $academicYearId);
        } elseif ($academicYearId) {
            $this->ensureAcademicYearInScope($admin, $academicYearId);
        }

        $indexNumber = trim($request->index_number);
        if ($indexNumber === '') {
            return redirect()->route('dashboard.coordinators.students.index')
                ->withInput()
                ->with('error', 'Index number is required.');
        }

        $hash = Student::hashIndexNumber($indexNumber);
        $student = Student::firstOrCreate(
            ['index_number_hash' => $hash],
            ['index_number' => $indexNumber, 'index_number_hash' => $hash]
        );
        $name = $request->filled('name') ? trim($request->name) : ($student->student_name ?? $indexNumber);
        $phone = $request->filled('phone') ? Student::normalizePhoneForStorage(trim($request->phone)) : $student->phone_contact;
        if ($name !== null) {
            $student->student_name = $name;
        }
        if ($phone !== null) {
            $student->phone_contact = $phone;
        }
        $student->save();

        $isSupervisor = $request->role === 'supervisor';
        $dmRole = $isSupervisor ? User::DM_ROLE_SUPERVISOR : User::DM_ROLE_STUDENT;
        $roleName = $isSupervisor ? 'supervisor' : 'student';
        $roleModel = $this->getRoleForCoordinatorDepartment($admin, $roleName);
        $deptId = $admin->coordinatorDepartmentId();
        $academicYearId = $request->filled('academic_year_id') ? (int) $request->academic_year_id : null;

        $existingUser = User::whereRaw('UPPER(TRIM(index_number)) = ?', [strtoupper($indexNumber)])
            ->whereIn('role', [User::DM_ROLE_STUDENT, User::DM_ROLE_LEADER, User::DM_ROLE_SUPERVISOR])
            ->first();
        if ($existingUser) {
            $update = [
                'role' => $dmRole,
                'name' => $name ?? $existingUser->name,
            ];
            if (Schema::hasColumn('users', 'academic_year_id')) {
                $update['academic_year_id'] = $academicYearId;
            }
            if ($roleModel) {
                $update['role_id'] = $roleModel->id;
                $update['department_id'] = $roleModel->department_id;
            } else {
                $update['department_id'] = $existingUser->department_id ?? $deptId;
            }
            $existingUser->update($update);
            if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'phone')) {
                $existingUser->phone = $phone ?? $existingUser->phone;
                $existingUser->save();
            }
            if ($isSupervisor) {
                Supervisor::firstOrCreate(['user_id' => $existingUser->id]);
            }
            $redirect = $this->redirectAfterStore($academicYearId, $request->role);
            return redirect($redirect)->with('success', ($existingUser->name ?: $indexNumber) . ' updated with selected academic year and role.');
        }

        $username = 'idx_' . preg_replace('/[^a-zA-Z0-9]/', '_', $indexNumber);
        if (User::where('username', $username)->exists()) {
            $username = $username . '_' . substr(uniqid(), -4);
        }
        $userData = [
            'username' => $username,
            'index_number' => $indexNumber,
            'name' => $name,
            'phone' => $phone,
            'role' => $dmRole,
            'department_id' => $roleModel ? $roleModel->department_id : $deptId,
            'is_active' => true,
            'password' => \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(32)),
        ];
        if (Schema::hasColumn('users', 'academic_year_id')) {
            $userData['academic_year_id'] = $academicYearId;
        }
        if ($roleModel) {
            $userData['role_id'] = $roleModel->id;
        }
        $user = User::create($userData);
        if ($isSupervisor) {
            Supervisor::firstOrCreate(['user_id' => $user->id]);
        }
        $redirect = $this->redirectAfterStore($academicYearId, $request->role);
        return redirect($redirect)->with('success', 'User added: ' . ($name ?: $indexNumber) . ' (' . $request->role . ').');
    }

    /** Redirect to year-scoped students/supervisors page when academic_year_id present; supervisors without year → supervisors index. */
    private function redirectAfterStore(?int $academicYearId, string $role): string
    {
        if ($role === 'supervisor' && (!$academicYearId || $academicYearId <= 0)) {
            return route('dashboard.coordinators.supervisors.index');
        }
        if ($academicYearId && $academicYearId > 0) {
            return $role === 'supervisor'
                ? route('dashboard.coordinators.academic-years.supervisors', $academicYearId)
                : route('dashboard.coordinators.academic-years.students', $academicYearId);
        }
        return route('dashboard.coordinators.students.index');
    }

    /** Upload users from Excel/CSV: academic year + role required; index numbers, optionally name and phone. */
    public function upload(Request $request): RedirectResponse
    {
        $admin = $this->adminUser();
        if (!$admin || !$admin->isDocuMentorCoordinator()) {
            abort(403, 'Access denied.');
        }

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
            'academic_year_id' => $request->role === 'supervisor' ? 'nullable|exists:academic_years,id' : 'required|exists:academic_years,id',
            'role' => 'required|in:student,supervisor',
        ]);

        $academicYearId = $request->filled('academic_year_id') ? (int) $request->academic_year_id : null;
        if ($academicYearId) {
            $this->ensureAcademicYearInScope($admin, $academicYearId);
        }

        $file = $request->file('file');
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();
        $header = array_shift($rows);
        $indexCol = 0;
        $nameCol = null;
        $phoneCol = null;
        foreach ($header as $i => $h) {
            $h = is_string($h) ? strtolower($h) : '';
            if (str_contains($h, 'index') || $i === 0) {
                $indexCol = $i;
            }
            if (str_contains($h, 'name') || str_contains($h, 'student') || str_contains($h, 'user')) {
                $nameCol = $nameCol ?? $i;
            }
            if (str_contains($h, 'phone') || str_contains($h, 'mobile') || str_contains($h, 'tel')) {
                $phoneCol = $phoneCol ?? $i;
            }
        }
        $isSupervisor = $request->role === 'supervisor';
        $dmRole = $isSupervisor ? User::DM_ROLE_SUPERVISOR : User::DM_ROLE_STUDENT;
        $roleName = $isSupervisor ? 'supervisor' : 'student';
        $roleModel = $this->getRoleForCoordinatorDepartment($admin, $roleName);
        $deptId = $admin->coordinatorDepartmentId();
        $added = 0;
        $updated = 0;
        foreach ($rows as $row) {
            $index = trim((string) ($row[$indexCol] ?? ''));
            if ($index === '') {
                continue;
            }
            $name = $nameCol !== null && isset($row[$nameCol]) ? trim((string) $row[$nameCol]) : null;
            $phone = $phoneCol !== null && isset($row[$phoneCol]) ? trim((string) $row[$phoneCol]) : null;
            if ($phone !== null && $phone === '') {
                $phone = null;
            }
            if ($phone !== null) {
                $phone = Student::normalizePhoneForStorage($phone);
            }

            $hash = Student::hashIndexNumber($index);
            $student = Student::firstOrCreate(
                ['index_number_hash' => $hash],
                ['index_number' => $index, 'index_number_hash' => $hash]
            );
            if ($student->wasRecentlyCreated) {
                $added++;
            } else {
                $updated++;
            }
            if ($name !== null) {
                $student->student_name = $name;
            }
            if ($phone !== null) {
                $student->phone_contact = $phone;
            }
            $student->save();

            $existingUser = User::whereRaw('UPPER(TRIM(index_number)) = ?', [strtoupper($index)])
                ->whereIn('role', [User::DM_ROLE_STUDENT, User::DM_ROLE_LEADER, User::DM_ROLE_SUPERVISOR])
                ->first();
            if ($existingUser) {
                $update = [
                    'role' => $dmRole,
                    'name' => $name ?? $existingUser->name,
                ];
                if (Schema::hasColumn('users', 'academic_year_id')) {
                    $update['academic_year_id'] = $academicYearId;
                }
                if ($roleModel) {
                    $update['role_id'] = $roleModel->id;
                    $update['department_id'] = $roleModel->department_id;
                } else {
                    $update['department_id'] = $existingUser->department_id ?? $deptId;
                }
                $existingUser->update($update);
                if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'phone') && $phone !== null) {
                    $existingUser->phone = $phone;
                    $existingUser->save();
                }
                if ($isSupervisor) {
                    Supervisor::firstOrCreate(['user_id' => $existingUser->id]);
                }
            } else {
                $username = 'idx_' . preg_replace('/[^a-zA-Z0-9]/', '_', $index);
                if (User::where('username', $username)->exists()) {
                    $username = $username . '_' . substr(uniqid(), -4);
                }
                $userData = [
                    'username' => $username,
                    'index_number' => $index,
                    'name' => $name ?? $index,
                    'phone' => $phone,
                    'role' => $dmRole,
                    'department_id' => $roleModel ? $roleModel->department_id : $deptId,
                    'is_active' => true,
                    'password' => \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(32)),
                ];
                if (Schema::hasColumn('users', 'academic_year_id')) {
                    $userData['academic_year_id'] = $academicYearId;
                }
                if ($roleModel) {
                    $userData['role_id'] = $roleModel->id;
                }
                $user = User::create($userData);
                if ($isSupervisor) {
                    Supervisor::firstOrCreate(['user_id' => $user->id]);
                }
            }
        }

        $total = $added + $updated;
        $message = $total === 0
            ? 'No valid rows (Index Number required).'
            : 'Added ' . $added . ' and updated ' . $updated . ' user(s) for ' . ($request->role === 'supervisor' ? 'supervisors' : 'students') . ' in the selected academic year.';

        $redirect = $this->redirectAfterStore($academicYearId, $request->role);
        return redirect($redirect)->with('success', $message);
    }

    /**
     * Get role for coordinator's department by name (student or supervisor).
     * Returns null if no role exists (caller can fall back to legacy role string + department_id).
     */
    private function getRoleForCoordinatorDepartment(User $coordinator, string $roleName): ?Role
    {
        $deptId = $coordinator->coordinatorDepartmentId();
        if ($deptId === null) {
            return null;
        }
        return Role::where('department_id', $deptId)->where('name', $roleName)->first();
    }

    /**
     * Ensure the academic year is in coordinator's scope (same department or global).
     */
    private function ensureAcademicYearInScope(User $coordinator, int $academicYearId): void
    {
        $ay = AcademicYear::find($academicYearId);
        if (! $ay) {
            abort(404, 'Academic year not found.');
        }
        $deptId = $coordinator->coordinatorDepartmentId();
        if ($deptId !== null && $ay->department_id !== null && (int) $ay->department_id !== $deptId) {
            abort(403, 'You can only add users to academic years in your department.');
        }
    }

    private function computeStats(User $user, array $classGroupIds): array
    {
        $total = 0;
        $withPhoneCount = 0;

        if (Schema::hasTable('students')) {
            $total = (int) Student::count();
            $withPhoneCount = (int) Student::whereNotNull('phone_contact')->where('phone_contact', '!=', '')->count();
        }

        return [
            'total' => $total,
            'class_group_students' => 0,
            'docu_mentor_students' => 0,
            'with_phone' => $withPhoneCount,
            'class_groups' => count($classGroupIds),
        ];
    }

    private function fetchStudents(Request $request, array $ids, User $user): JsonResponse
    {
        $items = collect();
        $nextPageUrl = null;

        // Prefer students table (uploaded users). If coordinator has class groups, also show class_group_students.
        $useStudentsTable = Schema::hasTable('students');
        if ($useStudentsTable) {
            $perPage = max(10, min(50, (int) $request->query('per_page', 20)));
            $query = Student::query()->orderBy('index_number');
            $search = trim((string) $request->query('search'));
            if ($search !== '') {
                $term = '%' . $search . '%';
                $query->where(function ($q) use ($term) {
                    $q->where('index_number', 'like', $term)
                        ->orWhere('student_name', 'like', $term)
                        ->orWhere('phone_contact', 'like', $term);
                });
            }
            $paginator = $query->simplePaginate($perPage);
            foreach ($paginator->items() as $s) {
                $items->push([
                    'index_number' => $s->index_number,
                    'encoded_index' => self::encodeIndex($s->index_number),
                    'student_name' => $s->student_name ?: $s->index_number,
                    'phone_contact' => $s->phone_contact ?? null,
                    'qualification_type' => null,
                    'institution' => null,
                    'faculty' => null,
                    'department' => null,
                    'year_group' => null,
                    'source' => 'docu_mentor',
                ]);
            }
            $nextPageUrl = $paginator->hasMorePages() ? $paginator->nextPageUrl() : null;
        }

        if (! $useStudentsTable && !empty($ids) && \Illuminate\Support\Facades\Schema::hasTable('class_group_students')) {
            $query = ClassGroupStudent::query()
                ->select(
                    'class_group_students.index_number',
                    DB::raw('COALESCE(MAX(students.student_name), MAX(class_group_students.student_name)) as student_name'),
                    DB::raw('MAX(students.phone_contact) as phone_contact'),
                    DB::raw('MAX(class_groups.academic_year_id) as academic_year_id'),
                    DB::raw('MAX(departments.name) as department_name'),
                    DB::raw('MAX(faculties.name) as faculty_name'),
                    DB::raw('MAX(schools.name) as school_name'),
                    DB::raw('MAX(academic_years.year) as year_group')
                )
                ->join('class_groups', 'class_group_students.class_group_id', '=', 'class_groups.id')
                ->leftJoin('users as supervisors', 'class_groups.supervisor_id', '=', 'supervisors.id')
                ->leftJoin('departments', 'supervisors.department_id', '=', 'departments.id')
                ->leftJoin('faculties', 'supervisors.faculty_id', '=', 'faculties.id')
                ->leftJoin('schools', 'departments.school_id', '=', 'schools.id')
                ->leftJoin('academic_years', 'class_groups.academic_year_id', '=', 'academic_years.id')
                ->leftJoin('students', 'class_group_students.index_number', '=', 'students.index_number')
                ->whereIn('class_groups.id', $ids);

            $academicYearId = $request->query('academic_year_id');
            if ($academicYearId) {
                $query->where('class_groups.academic_year_id', $academicYearId);
            }

            $academicClassId = $request->query('academic_class_id');
            if ($academicClassId) {
                $query->where('class_groups.academic_class_id', $academicClassId);
            }

            $classGroupId = $request->query('class_group_id');
            if ($classGroupId) {
                $query->where('class_groups.id', $classGroupId);
            }

            $semesterId = $request->query('semester_id');
            if ($semesterId && \Illuminate\Support\Facades\Schema::hasColumn('class_groups', 'semester_id')) {
                $query->where('class_groups.semester_id', $semesterId);
            }

            $search = trim((string) $request->query('search'));
            if ($search !== '') {
                $term = '%' . $search . '%';
                $query->where(function ($q) use ($term) {
                    $q->where('class_group_students.index_number', 'like', $term)
                        ->orWhere('class_group_students.student_name', 'like', $term)
                        ->orWhere('students.student_name', 'like', $term)
                        ->orWhere('students.index_number', 'like', $term);
                });
            }

            $query->groupBy('class_group_students.index_number')
                ->orderBy('class_group_students.index_number');

            $perPage = (int) $request->query('per_page', 20);
            $perPage = max(10, min(50, $perPage));
            $paginator = $query->simplePaginate($perPage);

            $items = collect($paginator->items())->map(function ($row) {
                return [
                    'index_number' => $row->index_number,
                    'encoded_index' => self::encodeIndex($row->index_number),
                    'student_name' => trim($row->student_name ?? '') ?: $row->index_number,
                    'phone_contact' => $row->phone_contact,
                    'qualification_type' => null,
                    'institution' => $row->school_name ?? null,
                    'faculty' => $row->faculty_name ?? null,
                    'department' => $row->department_name ?? null,
                    'year_group' => $row->year_group ?? null,
                    'source' => 'class_group',
                ];
            });

            $nextPageUrl = $paginator->hasMorePages() ? $paginator->nextPageUrl() : null;
        } else {
            $nextPageUrl = null;
        }

        return response()->json([
            'data' => $items->values()->all(),
            'next_page_url' => $nextPageUrl,
            'has_more' => (bool) $nextPageUrl,
        ]);
    }

    public function show(string $encodedIndex): View
    {
        $user = $this->adminUser();
        if (!$user || !$user->isDocuMentorCoordinator()) {
            abort(403, 'Access denied.');
        }
        [$indexNumber, $classGroupIds] = $this->resolveStudentByIndex($encodedIndex, $user);

        $cgStudents = collect();
        $studentAccount = Student::where('index_number_hash', Student::hashIndexNumber($indexNumber))->first();
        $dmUser = $user->docuMentorStudentsInScope()
            ->whereRaw('UPPER(TRIM(index_number)) = ?', [strtoupper(trim($indexNumber))])
            ->with('department', 'department.school')
            ->first();

        $displayName = $dmUser?->name ?? $studentAccount?->student_name ?? $indexNumber;
        $phone = $dmUser?->phone ?? $studentAccount?->phone_contact ?? null;

        $institution = $dmUser?->department?->school?->name ?? null;
        $faculty = $dmUser?->department?->faculty?->name ?? null;
        $department = $dmUser?->department?->name ?? null;
        $yearGroup = null;
        $qualificationType = null;

        if (!empty($classGroupIds) && \Illuminate\Support\Facades\Schema::hasTable('class_group_students')) {
            $cgStudents = ClassGroupStudent::whereIn('class_group_id', $classGroupIds)
                ->whereRaw('UPPER(TRIM(index_number)) = ?', [strtoupper(trim($indexNumber))])
                ->with(['classGroup' => fn ($q) => $q->with(['academicYear'])])
                ->get();
            $displayName = $studentAccount?->student_name ?? $cgStudents->first()?->student_name ?? $displayName;
            $phone = $studentAccount?->phone_contact ?? $phone;
            foreach ($cgStudents as $cgs) {
                $cg = $cgs->classGroup;
                if ($cg && $cg->supervisor_id) {
                    $supervisor = User::with(['department', 'department.school'])->find($cg->supervisor_id);
                    if ($supervisor) {
                        $institution = $institution ?? $supervisor->department?->school?->name;
                        $department = $department ?? $supervisor->department?->name;
                    }
                }
                if ($cg) {
                    $yearGroup = $yearGroup ?? $cg->academicYear?->year;
                }
            }
        }

        $isGroupLeader = $dmUser && ($dmUser->group_leader ?? false);
        if (!$isGroupLeader && \Illuminate\Support\Facades\Schema::hasColumn('users', 'group_leader')) {
            $isGroupLeader = User::whereIn('role', [User::DM_ROLE_STUDENT, User::DM_ROLE_LEADER])
                ->whereRaw('UPPER(TRIM(index_number)) = ?', [strtoupper(trim($indexNumber))])
                ->where('group_leader', true)
                ->exists();
        }

        return view('docu-mentor.coordinators.students.show', compact(
            'indexNumber', 'encodedIndex', 'displayName', 'phone', 'cgStudents', 'studentAccount',
            'institution', 'faculty', 'department', 'yearGroup', 'qualificationType',
            'isGroupLeader', 'dmUser'
        ));
    }

    public function toggleGroupLeader(Request $request, string $encodedIndex): RedirectResponse
    {
        $user = $this->adminUser();
        if (!$user || !$user->isDocuMentorCoordinator()) {
            abort(403, 'Access denied.');
        }

        $classGroupIds = [];
        $indexNumber = null;
        $explicitUser = null;

        if ($request->filled('user_id')) {
            $explicitUser = User::whereIn('role', [User::DM_ROLE_STUDENT, User::DM_ROLE_LEADER])
                ->find((int) $request->input('user_id'));
            if ($explicitUser) {
                $indexNumber = $explicitUser->index_number;
            }
        }

        if ($indexNumber === null || trim((string) $indexNumber) === '') {
            [$indexNumber, $classGroupIds] = $this->resolveStudentByIndex($encodedIndex, $user);
        }

        $redirectTo = function (string $message, string $type = 'success') use ($request, $encodedIndex) {
            if ($request->filled('return_url') && \Illuminate\Support\Str::startsWith($request->return_url, url('/'))) {
                return redirect($request->return_url)->with($type, $message);
            }
            return redirect()->route('dashboard.coordinators.students.show', ['encodedIndex' => $encodedIndex])->with($type, $message);
        };

        if (!\Illuminate\Support\Facades\Schema::hasColumn('users', 'group_leader')) {
            return $redirectTo('Database migration required.', 'error');
        }

        $dmUser = $explicitUser
            ?: $user->docuMentorStudentsInScope()
                ->whereRaw('UPPER(TRIM(index_number)) = ?', [strtoupper(trim($indexNumber))])
                ->first();

        // If not in coordinator scope, check for existing User by index (e.g. created by student login).
        // Update that same account so the student sees group-leader features when they log in.
        if (!$dmUser) {
            $dmUser = User::whereIn('role', [User::DM_ROLE_STUDENT, User::DM_ROLE_LEADER])
                ->whereRaw('UPPER(TRIM(index_number)) = ?', [strtoupper(trim($indexNumber))])
                ->first();
            if ($dmUser) {
                $dmUser->update([
                    'group_leader' => !($dmUser->group_leader ?? false),
                    'department_id' => $dmUser->department_id ?? $user->department_id,
                ]);
                $label = ($dmUser->group_leader ?? false) ? 'Group leader assigned.' : 'Group leader removed.';
                return $redirectTo($label);
            }
        }

        if (!$dmUser && !empty($classGroupIds) && \Illuminate\Support\Facades\Schema::hasTable('class_group_students')) {
            $cgStudent = ClassGroupStudent::whereIn('class_group_id', $classGroupIds)
                ->whereRaw('UPPER(TRIM(index_number)) = ?', [strtoupper(trim($indexNumber))])
                ->first();
            $name = $cgStudent?->student_name ?? null;
            $studentRecord = Student::where('index_number_hash', Student::hashIndexNumber($indexNumber))->first();
            $name = $name ?? $studentRecord?->student_name ?? $indexNumber;
            $username = 'idx_' . preg_replace('/[^a-zA-Z0-9]/', '_', $indexNumber);
            if (User::where('username', $username)->exists()) {
                $username = $username . '_' . substr(uniqid(), -4);
            }
            $dmUser = User::create([
                'username' => $username,
                'index_number' => $indexNumber,
                'name' => $name,
                'role' => User::DM_ROLE_STUDENT,
                'group_leader' => true,
                'department_id' => $user->department_id,
                'password' => \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(32)),
            ]);
            $label = 'Student set as group leader.';
        } elseif (!$dmUser) {
            $studentRecord = Student::where('index_number_hash', Student::hashIndexNumber($indexNumber))->first();
            $name = $studentRecord?->student_name ?? $indexNumber;
            $username = 'idx_' . preg_replace('/[^a-zA-Z0-9]/', '_', $indexNumber);
            if (User::where('username', $username)->exists()) {
                $username = $username . '_' . substr(uniqid(), -4);
            }
            $dmUser = User::create([
                'username' => $username,
                'index_number' => $indexNumber,
                'name' => $name,
                'role' => User::DM_ROLE_STUDENT,
                'group_leader' => true,
                'department_id' => $user->department_id,
                'password' => \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(32)),
            ]);
            $label = 'Student set as group leader.';
        } else {
            $newValue = !($dmUser->group_leader ?? false);
            // If assigning (true), prefer the account the student actually logs in with (same index, no department).
            $loginUser = null;
            if ($newValue) {
                $loginUser = User::whereIn('role', [User::DM_ROLE_STUDENT, User::DM_ROLE_LEADER])
                    ->whereRaw('UPPER(TRIM(index_number)) = ?', [strtoupper(trim($indexNumber))])
                    ->where('id', '!=', $dmUser->id)
                    ->whereNull('department_id')
                    ->first();
            }
            if ($loginUser) {
                $loginUser->update(['group_leader' => true, 'department_id' => $loginUser->department_id ?? $user->department_id]);
                $dmUser->update(['group_leader' => false]);
                $label = 'Group leader assigned (student will see it when they log in).';
            } else {
                $dmUser->update(['group_leader' => $newValue]);
                if (!$newValue) {
                    // Unassign: clear group_leader on any other user with this index so student loses leader status.
                    User::whereIn('role', [User::DM_ROLE_STUDENT, User::DM_ROLE_LEADER])
                        ->whereRaw('UPPER(TRIM(index_number)) = ?', [strtoupper(trim($indexNumber))])
                        ->where('id', '!=', $dmUser->id)
                        ->update(['group_leader' => false]);
                }
                $label = $newValue ? 'Group leader assigned.' : 'Group leader removed.';
            }
        }

        return $redirectTo($label);
    }

    public function edit(string $encodedIndex): View
    {
        $user = $this->adminUser();
        if (!$user || !$user->isDocuMentorCoordinator()) {
            abort(403, 'Access denied.');
        }
        [$indexNumber, $classGroupIds] = $this->resolveStudentByIndex($encodedIndex, $user);

        $cgStudents = collect();
        $studentAccount = Student::where('index_number_hash', Student::hashIndexNumber($indexNumber))->first();
        $dmUser = $user->docuMentorStudentsInScope()
            ->whereRaw('UPPER(TRIM(index_number)) = ?', [strtoupper(trim($indexNumber))])
            ->first();
        $displayName = $dmUser?->name ?? $studentAccount?->student_name ?? '';
        $phone = $dmUser?->phone ?? $studentAccount?->phone_contact ?? null;

        $yearGroup = null;
        $qualificationType = null;
        if (!empty($classGroupIds) && \Illuminate\Support\Facades\Schema::hasTable('class_group_students')) {
            $cgStudents = ClassGroupStudent::whereIn('class_group_id', $classGroupIds)
                ->whereRaw('UPPER(TRIM(index_number)) = ?', [strtoupper(trim($indexNumber))])
                ->with(['classGroup' => fn ($q) => $q->with(['academicYear'])])
                ->get();
            $displayName = $studentAccount?->student_name ?? $cgStudents->first()?->student_name ?? $displayName;
            $phone = $studentAccount?->phone_contact ?? $phone;
            foreach ($cgStudents as $cgs) {
                $cg = $cgs->classGroup;
                if ($cg) {
                    $yearGroup = $yearGroup ?? $cg->academicYear?->year;
                }
            }
        }
        // Year group from Docu Mentor group membership when no class group context
        if ($yearGroup === null && $dmUser && \Illuminate\Support\Facades\Schema::hasTable('group_members')) {
            $docuGroup = $dmUser->docuMentorGroups()->with('academicYear')->first();
            $yearGroup = $docuGroup?->academicYear?->year;
        }

        return view('docu-mentor.coordinators.students.edit', compact(
            'indexNumber', 'encodedIndex', 'studentAccount', 'displayName', 'phone',
            'cgStudents', 'yearGroup', 'qualificationType'
        ));
    }

    public function update(Request $request, string $encodedIndex): RedirectResponse
    {
        $user = $this->adminUser();
        if (!$user || !$user->isDocuMentorCoordinator()) {
            abort(403, 'Access denied.');
        }
        [$indexNumber, $classGroupIds] = $this->resolveStudentByIndex($encodedIndex, $user);

        $request->validate([
            'student_name' => 'nullable|string|max:255',
            'phone_contact' => 'nullable|string|max:20',
        ]);

        $existingStudent = Student::where('index_number_hash', Student::hashIndexNumber($indexNumber))->first();
        $currentName = $existingStudent?->student_name ?? null;
        $name = $request->has('student_name') ? (trim((string) $request->student_name) ?: null) : $currentName;
        $phoneRaw = $request->filled('phone_contact') ? trim($request->phone_contact) : null;
        $phone = $phoneRaw ? Student::normalizePhoneForStorage($phoneRaw) : null;

        if (!empty($classGroupIds) && \Illuminate\Support\Facades\Schema::hasTable('class_group_students')) {
            ClassGroupStudent::whereIn('class_group_id', $classGroupIds)
                ->whereRaw('UPPER(TRIM(index_number)) = ?', [strtoupper(trim($indexNumber))])
                ->update(['student_name' => $name]);
        }

        $studentAccount = Student::firstOrCreate(
            ['index_number_hash' => Student::hashIndexNumber($indexNumber)],
            ['index_number' => $indexNumber, 'student_name' => $name]
        );
        $studentAccount->student_name = $name;
        if ($phone !== null) {
            $other = Student::where('phone_contact', $phone)->where('id', '!=', $studentAccount->id)->first();
            if ($other) {
                return redirect()->route('dashboard.coordinators.students.edit', ['encodedIndex' => $encodedIndex])
                    ->withInput()
                    ->with('error', 'This phone number is already in use by another student.');
            }
        }
        $studentAccount->phone_contact = $phone;
        $studentAccount->save();

        $dmUser = $user->docuMentorStudentsInScope()
            ->whereRaw('UPPER(TRIM(index_number)) = ?', [strtoupper(trim($indexNumber))])
            ->first();
        if ($dmUser) {
            $dmUser->name = $name ?? $dmUser->name;
            if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'phone')) {
                $dmUser->phone = $phone;
            }
            $dmUser->save();
        }

        return redirect()->route('dashboard.coordinators.students.show', ['encodedIndex' => $encodedIndex])
            ->with('success', 'Student details updated.');
    }

    public function destroy(string $encodedIndex): RedirectResponse
    {
        $user = $this->adminUser();
        if (!$user || !$user->isDocuMentorCoordinator()) {
            abort(403, 'Access denied.');
        }
        [$indexNumber, $classGroupIds] = $this->resolveStudentByIndex($encodedIndex, $user);

        $indexUpper = strtoupper(trim($indexNumber));

        if (!empty($classGroupIds) && \Illuminate\Support\Facades\Schema::hasTable('class_group_students')) {
            ClassGroupStudent::whereIn('class_group_id', $classGroupIds)
                ->whereRaw('UPPER(TRIM(index_number)) = ?', [$indexUpper])
                ->delete();
        }

        $dmUser = $user->docuMentorStudentsInScope()
            ->whereRaw('UPPER(TRIM(index_number)) = ?', [$indexUpper])
            ->first();
        if ($dmUser) {
            $dmUser->delete();
        }

        Student::deleteEverywhereByIndex($indexNumber);

        return redirect()->route('dashboard.coordinators.students.index')
            ->with('success', 'Student removed.');
    }

    /**
     * Bulk delete all Docu Mentor students: all users with role student/leader and all students table records.
     * Removes group memberships and clears group leaders first.
     */
    public function bulkDestroy(): RedirectResponse
    {
        $user = $this->adminUser();
        if (!$user || !$user->isDocuMentorCoordinator()) {
            abort(403, 'Access denied.');
        }

        $studentLeaderIds = User::whereIn('role', [User::DM_ROLE_STUDENT, User::DM_ROLE_LEADER])->pluck('id');

        \Illuminate\Support\Facades\DB::transaction(function () use ($studentLeaderIds) {
            if ($studentLeaderIds->isNotEmpty()) {
                if (\Illuminate\Support\Facades\Schema::hasTable('group_members')) {
                    \Illuminate\Support\Facades\DB::table('group_members')
                        ->whereIn('user_id', $studentLeaderIds)
                        ->delete();
                }
                \App\Models\DocuMentor\ProjectGroup::whereIn('leader_id', $studentLeaderIds)->update(['leader_id' => null]);
                User::whereIn('id', $studentLeaderIds)->delete();
            }
            if (\Illuminate\Support\Facades\Schema::hasTable('students')) {
                Student::query()->delete();
            }
            if (\Illuminate\Support\Facades\Schema::hasTable('otps')) {
                \App\Models\Otp::query()->delete();
            }
        });

        $count = $studentLeaderIds->count();
        return redirect()->route('dashboard.coordinators.students.index')
            ->with('success', $count > 0
                ? "All {$count} student user(s) and all student records have been deleted."
                : 'All student records have been cleared.');
    }

    /**
     * Bulk delete selected students (by user id). Redirects back to year page if academic_year_id provided.
     */
    public function bulkDestroySelected(Request $request): RedirectResponse
    {
        $user = $this->adminUser();
        if (!$user || !$user->isDocuMentorCoordinator()) {
            abort(403, 'Access denied.');
        }

        $ids = $request->input('student_ids', []);
        if (!is_array($ids)) {
            $ids = [];
        }
        $ids = array_filter(array_map('intval', $ids));

        if (empty($ids)) {
            $redirect = $request->filled('academic_year_id')
                ? route('dashboard.coordinators.academic-years.students', (int) $request->academic_year_id)
                : route('dashboard.coordinators.students.index');
            return redirect($redirect)->with('error', 'No students selected.');
        }

        $deptId = $user->coordinatorDepartmentId();
        $query = User::whereIn('id', $ids)->whereIn('role', [User::DM_ROLE_STUDENT, User::DM_ROLE_LEADER]);
        if ($deptId !== null) {
            $query->where('department_id', $deptId);
        }
        $toDelete = $query->get();
        $indexNumbers = $toDelete->pluck('index_number')->filter()->all();

        DB::transaction(function () use ($toDelete, $indexNumbers) {
            $ids = $toDelete->pluck('id')->all();
            if (!empty($ids)) {
                if (Schema::hasTable('group_members')) {
                    DB::table('group_members')->whereIn('user_id', $ids)->delete();
                }
                \App\Models\DocuMentor\ProjectGroup::whereIn('leader_id', $ids)->update(['leader_id' => null]);
                User::whereIn('id', $ids)->delete();
            }
            foreach ($indexNumbers as $index) {
                if ($index !== null && $index !== '') {
                    Student::deleteEverywhereByIndex($index);
                }
            }
        });

        $count = $toDelete->count();
        $redirect = $request->filled('academic_year_id')
            ? route('dashboard.coordinators.academic-years.students', (int) $request->academic_year_id)
            : route('dashboard.coordinators.students.index');
        return redirect($redirect)->with('success', "{$count} student(s) removed.");
    }
}
