<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ClassGroupStudent;
use App\Models\Otp;
use App\Models\Student;
use App\Models\ValidIndex;
use App\Services\ArkeselService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use App\Models\User;

class StudentAccountController extends Controller
{

    /**
     * Student account login form (index → phone → OTP flow).
     */
    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard')
                ->with('info', 'You are already logged in.');
        }
        return view('student.account-login');
    }

    /**
     * Step 1: Verify index number. Index must exist in the academic-year index list or as a Docu Mentor user.
     * Returns: need_phone (and student), or sends OTP and returns need_otp.
     */
    public function verifyIndex(Request $request): JsonResponse
    {
        if (Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'You are already logged in.',
            ], 422);
        }
        $request->validate(['index_number' => 'required|string|max:100']);
        $inputIndex = trim((string) $request->index_number);
        $inputNormalized = strtolower($inputIndex);

        $indexNumber = null;
        $indexHash = null;
        $cgStudent = null;
        $valid = null;
        $dmUser = null;

        // Primary source: academic-year index list (valid_indices)
        if (Schema::hasTable('valid_indices')) {
            $valid = ValidIndex::whereRaw('LOWER(TRIM(index_number)) = ?', [$inputNormalized])->first();
            if ($valid) {
                $indexNumber = strtoupper(trim($valid->index_number));
                $indexHash = Student::hashIndexNumber($indexNumber);
            }
        }

        // Fallback: class_group_students (legacy)
        if ($indexNumber === null && Schema::hasTable('class_group_students')) {
            $cgStudent = ClassGroupStudent::whereRaw('LOWER(TRIM(index_number)) = ?', [$inputNormalized])->first();
            if ($cgStudent) {
                $indexNumber = strtoupper(trim($cgStudent->index_number));
                $indexHash = Student::hashIndexNumber($cgStudent->index_number);
            }
        }

        // Fallback: existing Docu Mentor user (student/leader)
        if ($indexNumber === null && Schema::hasTable('users')) {
            $dmUser = User::whereIn('role', [User::DM_ROLE_STUDENT, User::DM_ROLE_LEADER])
                ->whereRaw('LOWER(TRIM(index_number)) = ?', [$inputNormalized])
                ->first();
            if ($dmUser) {
                $indexNumber = strtoupper(trim($dmUser->index_number ?? $inputIndex));
                $indexHash = Student::hashIndexNumber($indexNumber);
            }
        }

        // When index came from valid_indices/class_group, still try to get phone/name from users
        if ($indexNumber !== null && $dmUser === null && Schema::hasTable('users')) {
            $dmUser = User::whereIn('role', [User::DM_ROLE_STUDENT, User::DM_ROLE_LEADER])
                ->whereRaw('UPPER(TRIM(index_number)) = ?', [strtoupper($indexNumber)])
                ->first();
        }

        if ($indexNumber === null) {
            return response()->json([
                'success' => false,
                'message' => 'Index number not found.',
            ], 422);
        }

        $student = Student::firstOrCreate(
            ['index_number_hash' => $indexHash],
            [
                'index_number' => $indexNumber,
                'index_number_hash' => $indexHash,
            ]
        );
        // Enrich student record with coordinator data (name/phone) only for first-time login,
        // so subsequent manual edits (like clearing phone_contact) are respected.
        if ($student->isFirstTimeLogin()) {
            $sourceName = $student->student_name;
            if (empty($sourceName)) {
                if ($valid && !empty($valid->student_name)) {
                    $sourceName = $valid->student_name;
                } elseif ($dmUser && !empty($dmUser->name)) {
                    $sourceName = $dmUser->name;
                }
            }
            $sourcePhone = $student->phone_contact;
            if (empty($sourcePhone) && $dmUser && !empty($dmUser->phone)) {
                $sourcePhone = Student::normalizePhoneForStorage($dmUser->phone);
            }
            $dirty = false;
            if (!empty($sourceName) && empty($student->student_name)) {
                $student->student_name = ucwords(strtolower($sourceName));
                $dirty = true;
            }
            if (!empty($sourcePhone) && empty($student->phone_contact)) {
                $student->phone_contact = $sourcePhone;
                $dirty = true;
            }
            if ($dirty) {
                $student->save();
            }
        }

        $hasName = !empty($student->student_name);
        $hasPhone = $student->hasPhone();

        // If name or phone is missing, go through onboarding to capture them
        // before sending the first OTP. Only ask for what's missing.
        if (!$hasPhone || !$hasName) {
            $message = 'Enter your full name and mobile number to receive a one-time code.';
            if ($hasName && !$hasPhone) {
                $message = 'Enter your mobile number to receive a one-time code.';
            } elseif (!$hasName && $hasPhone) {
                $message = 'Enter your full name to continue.';
            }
            return response()->json([
                'success' => true,
                'step' => 'phone',
                'index_number' => $student->index_number,
                'message' => $message,
                'has_name' => $hasName,
                'has_phone' => $hasPhone,
            ]);
        }

        // Returning student with name + phone:
        // Reuse existing OTP within its 90-day window; otherwise generate a new one.
        $lastOtp = Otp::latestStudentLoginForIndex($indexHash);
        if ($lastOtp && !$lastOtp->isExpired()) {
            $daysRemaining = $lastOtp->daysRemaining();
            $dayText = $daysRemaining === 1 ? '1 day' : $daysRemaining . ' days';
            return response()->json([
                'success' => true,
                'step' => 'otp',
                'index_number' => $student->index_number,
                'message' => 'Your existing code is still valid. It expires in ' . $dayText . '.',
                'has_name' => true,
                'can_resend' => false,
                'days_remaining' => $daysRemaining,
            ]);
        }

        // No valid OTP: generate a fresh one and send.
        $smsIndexNumber = $cgStudent?->index_number ?? $indexNumber ?? $student->index_number;
        $smsOwner = $smsIndexNumber ? $this->smsOwnerForIndex($smsIndexNumber) : null;

        $code = (string) random_int(100000, 999999);
        Otp::create([
            'index_number_hash' => $indexHash,
            'type' => Otp::TYPE_STUDENT_LOGIN,
            'code' => $code,
            'expires_at' => now()->addDays(Otp::STUDENT_LOGIN_VALID_DAYS),
        ]);
        $message = 'Your Docu Mento login code is: ' . $code . '. Do not share. Valid for 90 days.';
        $result = ArkeselService::sendSms($student->phone_contact, $message);
        if (!$result['success']) {
            $msg = $result['message'] ?? 'We couldn\'t send the code.';
            if (strpos($msg, 'try again') === false && strpos($msg, 'Try again') === false) {
                $msg .= ' Please try again.';
            }
            return response()->json(['success' => false, 'message' => $msg], 422);
        }
        if ($smsOwner) {
            $smsOwner->increment('sms_used');
        }
        return response()->json([
            'success' => true,
            'step' => 'otp',
            'index_number' => $student->index_number,
            'message' => 'A code has been sent to your registered number. This code is valid for 90 days.',
            'has_name' => !empty($student->student_name),
            'can_resend' => false,
            'days_remaining' => Otp::STUDENT_LOGIN_VALID_DAYS,
        ]);
    }

    /**
     * Step 2: Send OTP to the given phone (first-time or new phone). Ties phone to account after OTP verify.
     */
    public function sendOtp(Request $request): JsonResponse
    {
        $request->validate([
            'index_number' => 'required|string|max:100',
            'phone' => 'nullable|string|max:20',
            'student_name' => 'nullable|string|max:255',
        ]);
        $inputIndex = trim((string) $request->index_number);

        $student = Student::where('index_number_hash', Student::hashIndexNumber($inputIndex))->first();
        if (!$student) {
            return response()->json(['success' => false, 'message' => 'Invalid session. Start again.'], 422);
        }
        $indexNumber = $student->index_number;
        $name = $request->filled('student_name') ? trim((string) $request->student_name) : null;
        $inputPhone = trim((string) ($request->phone ?? ''));
        $phone = Student::normalizePhoneForStorage($inputPhone);

        if (!$phone) {
            $storedNormalized = $student->phone_contact ? Student::normalizePhoneForStorage($student->phone_contact) : '';
            if ($storedNormalized) {
                // Registered students can request a new OTP without re-entering phone.
                $phone = $storedNormalized;
            }
        }
        if ($student->isFirstTimeLogin() && (empty($student->student_name) && ($name === null || $name === ''))) {
            return response()->json([
                'success' => false,
                'message' => 'Please enter your full name.',
            ], 422);
        }

        if (!$phone || strlen($phone) < 10) {
            return response()->json([
                'success' => false,
                'message' => 'Please enter a valid phone number (e.g. 0244123456, +233244123456).',
            ], 422);
        }

        // Phone must not be used by another student
        $otherStudent = Student::where('phone_contact', $phone)->where('id', '!=', $student->id)->first();
        if ($otherStudent) {
            return response()->json([
                'success' => false,
                'message' => 'This phone number is already registered to another student. Use a different number or ask your supervisor for help.',
            ], 422);
        }

        // Save name/phone for first-time onboarding
        if ($name !== null && $name !== '') {
            $student->student_name = ucwords(strtolower($name));
        }
        $student->phone_contact = $phone;
        $student->save();

        // Supervisor with SMS balance (for deducting); if none, we still send OTP so students can log in
        $smsOwner = $this->smsOwnerForIndex($student->index_number);

        $indexHash = $student->index_number_hash;

        // Resend rule: if student already has phone, only allow new OTP after current one expires
        $storedNormalized = $student->phone_contact ? Student::normalizePhoneForStorage($student->phone_contact) : '';
        if ($storedNormalized !== '') {
            $lastOtp = Otp::latestStudentLoginForIndex($indexHash);
            if ($lastOtp && !$lastOtp->isExpired()) {
                $daysRemaining = $lastOtp->daysRemaining();
                $dayText = $daysRemaining === 1 ? '1 day' : $daysRemaining . ' days';
                return response()->json([
                    'success' => false,
                    'message' => 'Your existing OTP is still valid. You can request a new code in ' . $dayText . '.',
                    'can_resend' => false,
                    'days_remaining' => $daysRemaining,
                ], 422);
            }
        }

        // Generate new OTP, save to DB, send SMS (replace old one — latest wins)
        $code = (string) random_int(100000, 999999);
        Otp::create([
            'index_number_hash' => $indexHash,
            'type' => Otp::TYPE_STUDENT_LOGIN,
            'code' => $code,
            'phone' => $phone,
            'expires_at' => now()->addDays(Otp::STUDENT_LOGIN_VALID_DAYS),
        ]);

        $message = 'Your Docu Mento login code is: ' . $code . '. Do not share. Valid for 90 days.';
        $result = ArkeselService::sendSms($phone, $message);
        if (!$result['success']) {
            $msg = $result['message'] ?? 'We couldn\'t send the code.';
            if (strpos($msg, 'try again') === false && strpos($msg, 'Try again') === false) {
                $msg .= ' Please try again.';
            }
            return response()->json(['success' => false, 'message' => $msg], 422);
        }
        if ($smsOwner) {
            $smsOwner->increment('sms_used');
        }
        return response()->json([
            'success' => true,
            'step' => 'otp',
            'index_number' => $student->index_number,
            'message' => 'A code has been sent to your number. It is valid for 90 days.',
            'has_name' => !empty($student->student_name),
            'can_resend' => false,
            'days_remaining' => Otp::STUDENT_LOGIN_VALID_DAYS,
        ]);
    }

    /** User whose SMS balance is deducted for this index: coordinator first, then class group supervisor. */
    private function smsOwnerForIndex(string $indexNumber): ?\App\Models\User
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('class_group_students')) {
            return null;
        }

        $cgStudents = ClassGroupStudent::whereRaw('UPPER(TRIM(index_number)) = ?', [strtoupper(trim($indexNumber))])
            ->with('classGroup.supervisor')
            ->get();

        foreach ($cgStudents as $cg) {
            $classGroup = $cg->classGroup;
            if ($classGroup) {
                $coordinator = \App\Models\User::coordinatorWithSmsBalanceForClassGroup($classGroup);
                if ($coordinator) {
                    return $coordinator;
                }
            }
        }

        foreach ($cgStudents as $cg) {
            $supervisor = $cg->classGroup?->supervisor;
            if ($supervisor && $supervisor->isDocuMentorSupervisor() && $supervisor->sms_remaining > 0) {
                return $supervisor;
            }
        }

        return null;
    }

    /**
     * Step 3: Verify OTP and create session. Optionally accept student_name to tie to account.
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        if (Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'You are already logged in.',
            ], 422);
        }
        $request->validate([
            'index_number' => 'required|string|max:100',
            'code' => 'required|string|size:6',
            'student_name' => 'nullable|string|max:255',
        ]);
        $inputIndex = trim((string) $request->index_number);
        $code = trim($request->code);
        $name = $request->filled('student_name') ? trim($request->student_name) : null;

        $indexHash = Student::hashIndexNumber($inputIndex);
        $student = Student::where('index_number_hash', $indexHash)->first();
        if (! $student) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid session. Start again.',
            ], 422);
        }
        $indexNumber = $student->index_number;

        // For first-time students, name is required at OTP stage only if it hasn't already been saved.
        if ($student->isFirstTimeLogin() && empty($student->student_name) && ($name === null || $name === '')) {
            return response()->json([
                'success' => false,
                'message' => 'Please enter your full name before continuing.',
            ], 422);
        }

        // Supervisor fallback: one-time use; mark used_at and invalidate immediately
        $fallbackOtp = Otp::latestValidSupervisorFallbackForIndex($indexHash);
        if ($fallbackOtp && $fallbackOtp->code === $code) {
            $fallbackOtp->used_at = now();
            $fallbackOtp->save();
            if ($student->isFirstTimeLogin()) {
                session(['student_setup_index_hash' => $indexHash, 'student_setup_index_number' => $student->index_number]);
                return response()->json([
                    'success' => true,
                    'redirect' => route('student.account.setup'),
                ]);
            }
            $this->completeStudentLogin($student, null, $name);
            return response()->json([
                'success' => true,
                'redirect' => $this->studentLoginRedirect($student),
            ]);
        }

        // Student login OTP: reusable until expires_at; do NOT set used_at
        $lastOtp = Otp::latestStudentLoginForIndex($indexHash);
        if (!$lastOtp || $lastOtp->isExpired() || $lastOtp->code !== $code) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired code. Please request a new one.',
            ], 422);
        }

        $phone = $lastOtp->phone ? (Student::normalizePhoneForStorage($lastOtp->phone) ?? $lastOtp->phone) : null;
        if ($phone) {
            $otherStudent = Student::where('phone_contact', $phone)->where('id', '!=', $student->id)->first();
            if ($otherStudent) {
                return response()->json([
                    'success' => false,
                    'message' => 'This phone number is already registered to another student. Use a different number.',
                ], 422);
            }
        }

        // First-time: redirect to Account Setup (do not log in yet). Attach phone from OTP for setup form.
        if ($student->isFirstTimeLogin()) {
            if ($phone) {
                $student->phone_contact = $phone;
                $student->save();
            }
            session(['student_setup_index_hash' => $indexHash, 'student_setup_index_number' => $student->index_number]);
            return response()->json([
                'success' => true,
                'redirect' => route('student.account.setup'),
            ]);
        }

        $this->completeStudentLogin($student, $phone ?? null, $name);
        return response()->json([
            'success' => true,
            'redirect' => $this->studentLoginRedirect($student),
        ]);
    }

    private function completeStudentLogin(Student $student, ?string $phone, ?string $name): void
    {
        if ($phone) {
            $student->phone_contact = $phone;
        }
        if ($name !== null && $name !== '') {
            $student->student_name = ucwords(strtolower(trim($name)));
        }
        $student->save();

        $user = User::findOrCreateDocuMentorUserForStudent($student);
        if (! $user && trim((string) ($student->index_number ?? '')) !== '') {
            $user = User::createDocuMentorUserForStudent($student);
        }
        if ($user) {
            request()->session()->regenerate();
            Auth::login($user, false);
        }
    }

    private function studentLoginRedirect(Student $student): string
    {
        if (session()->has('legacy_activity_id')) {
            session()->forget('legacy_activity_id');
            return route('student.proctoring.capture');
        }
        return route('dashboard');
    }

    /**
     * Account Setup (first-time only). Show form: Full Name, Phone, Password.
     * Allowed only when session has student_setup_index_hash from successful OTP verify.
     */
    public function showAccountSetup(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard')->with('info', 'You are already logged in.');
        }
        $indexHash = session('student_setup_index_hash');
        if (!$indexHash) {
            return redirect()->route('student.account.login.form')->with('info', 'Please sign in with your index number and verify the code sent to your phone first.');
        }
        $student = Student::where('index_number_hash', $indexHash)->first();
        if (!$student || !$student->isFirstTimeLogin()) {
            session()->forget(['student_setup_index_hash', 'student_setup_index_number']);
            return redirect()->route('student.account.login.form')->with('info', 'Invalid or expired setup session. Please sign in again.');
        }
        return view('student.account-setup', [
            'index_number' => session('student_setup_index_number', $student->index_number),
            'student' => $student,
        ]);
    }

    /**
     * Store account setup: validate Full Name, Phone, Password; hash password; set first_time_login = false, is_active = true; log in; redirect to dashboard.
     */
    public function storeAccountSetup(Request $request): RedirectResponse
    {
        $indexHash = session('student_setup_index_hash');
        if (!$indexHash) {
            return redirect()->route('student.account.login.form')->with('error', 'Session expired. Please sign in again.');
        }
        $student = Student::where('index_number_hash', $indexHash)->first();
        if (!$student || !$student->isFirstTimeLogin()) {
            session()->forget(['student_setup_index_hash', 'student_setup_index_number']);
            return redirect()->route('student.account.login.form')->with('error', 'Invalid setup session.');
        }

        $request->validate([
            'student_name' => 'required|string|max:255',
            'phone_contact' => 'required|string|max:20',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $phone = Student::normalizePhoneForStorage(trim((string) $request->phone_contact));
        if (!$phone || strlen($phone) < 10) {
            return redirect()->back()->withInput()->with('error', 'Please enter a valid phone number (e.g. 0244123456).');
        }
        $otherStudent = Student::where('phone_contact', $phone)->where('id', '!=', $student->id)->first();
        if ($otherStudent) {
            return redirect()->back()->withInput()->with('error', 'This phone number is already registered to another student.');
        }

        $student->student_name = ucwords(strtolower(trim($request->student_name)));
        $student->phone_contact = $phone;
        $student->password = Hash::make($request->password);
        $student->first_time_login = false;
        $student->is_active = true;
        $student->save();

        session()->forget(['student_setup_index_hash', 'student_setup_index_number']);
        $user = User::findOrCreateDocuMentorUserForStudent($student);
        if ($user) {
            $user->update(['password' => Hash::make($request->password), 'name' => $student->student_name, 'phone' => $phone]);
            request()->session()->regenerate();
            Auth::login($user, false);
        }
        return redirect()->to($this->studentLoginRedirect($student))->with('success', 'Account set up. Welcome!');
    }

    /**
     * Subsequent login: verify password, then log in as User.
     */
    public function loginWithPassword(Request $request): JsonResponse
    {
        if (Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'You are already logged in.',
            ], 422);
        }

        $request->validate([
            'index_number' => 'required|string|max:100',
            'password' => 'required|string',
        ]);

        $indexHash = Student::hashIndexNumber(trim($request->index_number));
        $student = Student::where('index_number_hash', $indexHash)->first();
        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid session. Start again.',
            ], 422);
        }
        if ($student->isFirstTimeLogin()) {
            return response()->json([
                'success' => false,
                'message' => 'Please complete account setup first using the code sent to your phone.',
            ], 422);
        }
        if (!$student->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Account is not active. Contact your coordinator.',
            ], 422);
        }
        if (!Hash::check($request->password, $student->getAuthPassword())) {
            return response()->json([
                'success' => false,
                'message' => 'Incorrect password.',
            ], 422);
        }

        $user = User::findOrCreateDocuMentorUserForStudent($student);
        if ($user) {
            request()->session()->regenerate();
            Auth::login($user, false);
        }
        return response()->json([
            'success' => true,
            'redirect' => $this->studentLoginRedirect($student),
        ]);
    }

    /**
     * Log out (Laravel Auth).
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        // After student logout, send them to the public landing page instead of the staff login
        return redirect()->route('student.landing')->with('success', 'Logged out.');
    }
}
