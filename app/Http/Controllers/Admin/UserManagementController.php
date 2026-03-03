<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Concerns\InteractsWithAdminSession;
use App\Models\Department;
use App\Models\Setting;
use App\Models\School;
use App\Models\User;
use App\Services\ArkeselService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    use InteractsWithAdminSession;

    /** Normalize phone to international digits (e.g. 0544919953 → 233544919953) for storage and uniqueness check. */
    private static function normalizePhone(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }
        $phone = preg_replace('/\D/', '', trim($value));
        if ($phone === '') {
            return null;
        }
        if (strlen($phone) >= 10 && substr($phone, 0, 1) === '0') {
            $phone = '233' . substr($phone, 1);
        } elseif (strlen($phone) >= 9 && substr($phone, 0, 3) !== '233') {
            $phone = '233' . $phone;
        }
        return $phone;
    }

    public function index(): View
    {
        $user = $this->adminUser();
        $isSuperAdmin = $user && $user->isSuperAdmin();

        // Super Admin sees only staff-style users (Super Admin, Supervisors, Docu Mentor Coordinators).
        // Docu Mentor students/leaders are technical bridge accounts and should not appear here.
        $query = User::query();

        if (!$isSuperAdmin && $user) {
            // Supervisor (or non-super admin): only themselves
            $query->where('id', $user->id);
        } elseif ($isSuperAdmin && $user) {
            // Any super admin: see all core staff accounts (super admins, supervisors, coordinators)
            $query->whereIn('role', [
                User::ROLE_SUPER_ADMIN,
                User::ROLE_SUPERVISOR,
                User::DM_ROLE_COORDINATOR,
            ]);
        }
        
        $users = $query->with(['department', 'department.school'])
            ->orderBy('role')
            ->orderBy('username')
            ->paginate(20);

        return view('admin.users.index', compact('users', 'isSuperAdmin'));
    }

    public function create(): View
    {
        $user = $this->adminUser();
        $isSuperAdmin = $user && $user->isSuperAdmin();
        
        // Only Super Admin can create users
        if (!$isSuperAdmin) {
            abort(403, 'Only Super Administrators can create users.');
        }
        // Any super admin can create a secondary super admin (show "Super Admin (secondary)" in role dropdown)
        $canCreateSuperAdmin = $isSuperAdmin && $user;

        $schools = School::orderBy('name')->get();
        $departments = collect();
        $sendSmsOnStaffCreation = Setting::getValue(Setting::KEY_SEND_SMS_ON_STAFF_CREATION, '0') === '1';
        return view('admin.users.create', compact('schools', 'departments', 'isSuperAdmin', 'canCreateSuperAdmin', 'sendSmsOnStaffCreation'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $this->adminUser();
        $isSuperAdmin = $user && $user->isSuperAdmin();
        
        // Only Super Admin can create users
        if (!$isSuperAdmin) {
            abort(403, 'Only Super Administrators can create users.');
        }
        $canCreateSuperAdmin = $isSuperAdmin && $user;
        
        $role = $request->role;
        $isStaffRole = in_array($role, [User::ROLE_SUPERVISOR, User::DM_ROLE_COORDINATOR], true);
        $sendSmsOnStaffCreation = Setting::getValue(Setting::KEY_SEND_SMS_ON_STAFF_CREATION, '0') === '1';
        $useSmsFlow = $isStaffRole && $sendSmsOnStaffCreation && ArkeselService::hasApiKey();

        $rules = [
            'username' => 'required|string|max:255|unique:users,username',
            'email' => 'nullable|email|max:255',
            'name' => 'nullable|string|max:255',
            'role' => $canCreateSuperAdmin
                ? 'required|in:super_admin,supervisor,coordinator'
                : 'required|in:supervisor,coordinator',
        ];
        if ($useSmsFlow) {
            $rules['phone'] = 'required|string|max:20';
            $rules['password'] = 'nullable';
            $rules['password_confirmation'] = 'nullable';
        } else {
            $rules['password'] = ['required', 'confirmed', Password::min(8)->letters()->numbers()];
            $rules['phone'] = 'nullable|string|max:20';
        }
        if ($request->filled('phone')) {
            $rules['phone_normalized'] = [Rule::unique('users', 'phone')];
        }
        if ($isSuperAdmin) {
            $rules['department_id'] = 'nullable|exists:departments,id';
            $rules['school_id'] = 'nullable|exists:schools,id';
            $rules['sms_allocation'] = 'nullable|integer|min:0';
            $rules['ai_tokens_allocation'] = 'nullable|integer|min:0';
            if ($isStaffRole) {
                $rules['department_id'] = 'required|exists:departments,id';
                $rules['school_id'] = 'nullable|exists:schools,id';
            }
        }

        // Normalize phone for uniqueness check (DB stores normalized format); do not overwrite phone so old() keeps user input
        if ($request->filled('phone')) {
            $request->merge(['phone_normalized' => self::normalizePhone($request->phone)]);
        }
        $request->validate($rules, [
            'password.required' => 'A password is required.',
            'password.confirmed' => 'The password confirmation does not match.',
            'password.min' => 'The password must be at least 8 characters.',
            'password.letters' => 'The password must contain at least one letter.',
            'password.numbers' => 'The password must contain at least one number.',
            'phone.required' => 'Phone is required when sending login credentials by SMS (Settings → Send SMS on staff creation).',
            'phone_normalized.unique' => 'This phone number is already used by another account. Please use a different number.',
            'department_id.required' => 'Department is required for supervisors and coordinators.',
        ]);

        $plainPassword = null;
        if ($useSmsFlow) {
            $plainPassword = Str::password(10);
        } else {
            $plainPassword = $request->password;
        }

        $attrs = [
            'username' => $request->username,
            'name' => $request->name ?: $request->username,
            'role' => $role,
            'password' => Hash::make($plainPassword),
        ];
        if (Schema::hasColumn('users', 'email')) {
            $attrs['email'] = $request->filled('email') ? trim($request->email) : null;
        }
        if (Schema::hasColumn('users', 'phone')) {
            $phone = $request->filled('phone') ? preg_replace('/\D/', '', trim($request->phone)) : null;
            if ($phone !== null && $phone !== '') {
                if (strlen($phone) >= 10 && substr($phone, 0, 1) === '0') {
                    $phone = '233' . substr($phone, 1);
                } elseif (strlen($phone) >= 9 && substr($phone, 0, 3) !== '233') {
                    $phone = '233' . $phone;
                }
                $attrs['phone'] = $phone;
            }
        }
        if ($request->filled('department_id')) {
            $attrs['department_id'] = (int) $request->department_id;
        }
        if ($isSuperAdmin && $request->has('sms_allocation') && $request->input('sms_allocation') !== null && $request->input('sms_allocation') !== '') {
            $attrs['sms_allocation'] = max(0, (int) $request->sms_allocation);
        }
        if ($isSuperAdmin && in_array($role, [User::ROLE_SUPERVISOR, User::DM_ROLE_COORDINATOR], true)) {
            $defaultAllocation = $role === User::DM_ROLE_COORDINATOR ? 3 : 10;
            $attrs['ai_tokens_allocation'] = max(0, (int) ($request->ai_tokens_allocation ?? $defaultAllocation));
        }
        $newUser = User::create($attrs);

        if ($useSmsFlow && $newUser->phone) {
            $loginUrl = url('/login');
            $message = sprintf(
                "Docu Mento login. URL: %s Username: %s Password: %s",
                $loginUrl,
                $newUser->username,
                $plainPassword
            );
            $result = ArkeselService::sendSms($newUser->phone, $message);
            if ($result['success']) {
                return redirect()->route('dashboard.users.index')
                    ->with('success', "Account created! We've sent the login details by SMS — they're all set.");
            }
            return redirect()->route('dashboard.users.index')
                ->with('sms_failed', $result['message'] ?? 'SMS could not be sent.')
                ->with('generated_password', $plainPassword)
                ->with('created_username', $newUser->username);
        }

        return redirect()->route('dashboard.users.index')
            ->with('success', "Account created! They can log in with the password you set.");
    }

    public function edit(Request $request, User $user): View|RedirectResponse
    {
        $currentUser = $this->adminUser();
        $isSuperAdmin = $currentUser && $currentUser->isSuperAdmin();
        
        $allowedRoles = [User::ROLE_SUPER_ADMIN, User::ROLE_SUPERVISOR, User::DM_ROLE_COORDINATOR, User::DM_ROLE_STUDENT, User::DM_ROLE_LEADER];
        if (! in_array($user->role, $allowedRoles, true)) {
            return redirect()->route('dashboard.users.index')
                ->with('error', 'Error');
        }
        
        // Supervisors can only edit themselves
        if (!$isSuperAdmin && $currentUser && $currentUser->id !== $user->id) {
            abort(403, 'You can only edit your own profile.');
        }
        
        $user->load(['department', 'department.school']);
        $schools = School::orderBy('name')->get();
        $departments = collect();
        $selectedSchoolId = $request->get('school_id', $user->department?->school_id);
        if ($selectedSchoolId) {
            $departments = Department::where('school_id', $selectedSchoolId)->orderBy('name')->get();
        }
        
        // Check if this is a profile completion flow (supervisor editing themselves and missing faculty/department)
        $isProfileCompletion = $request->has('complete_profile') && 
                               !$isSuperAdmin && 
                               $currentUser && 
                               $currentUser->id === $user->id &&
                               (!$user->department_id);

        return view('admin.users.edit', compact('user', 'schools', 'departments', 'isSuperAdmin', 'isProfileCompletion', 'selectedSchoolId'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $currentUser = $this->adminUser();
        $isSuperAdmin = $currentUser && $currentUser->isSuperAdmin();
        
        $allowedRoles = [User::ROLE_SUPER_ADMIN, User::ROLE_SUPERVISOR, User::DM_ROLE_COORDINATOR, User::DM_ROLE_STUDENT, User::DM_ROLE_LEADER];
        if (! in_array($user->role, $allowedRoles, true)) {
            return redirect()->route('dashboard.users.index')
                ->with('error', 'Error');
        }
        
        // Supervisors can only edit themselves
        if (!$isSuperAdmin && $currentUser && $currentUser->id !== $user->id) {
            abort(403, 'You can only edit your own profile.');
        }
        
        // Supervisors cannot change their role
        $rules = [
            'username' => 'required|string|max:255|unique:users,username,' . $user->id,
            'email' => 'nullable|email|max:255',
            'name' => 'nullable|string|max:255',
        ];
        
        // Only Super Admin can change roles
        if ($isSuperAdmin) {
            $rules['role'] = 'required|in:super_admin,supervisor,coordinator,student,leader';
        }
        
        // Only Super Admin can assign institution, faculty, department, and SMS allocation (no courses)
        if ($isSuperAdmin) {
            $rules['department_id'] = 'nullable|exists:departments,id';
            $rules['school_id'] = 'nullable|exists:schools,id';
            $rules['sms_allocation'] = 'nullable|integer|min:0';
            $rules['ai_tokens_allocation'] = 'nullable|integer|min:0';
        }

        // Super Admin can set/reset password for any staff (super_admin or supervisor).
        if ($request->filled('password')) {
            $rules['password'] = ['required', 'confirmed', Password::min(8)->letters()->numbers()];
        }
        
        $request->validate($rules, [
            'password.required' => 'A password is required.',
            'password.confirmed' => 'The password confirmation does not match.',
            'password.min' => 'The password must be at least 8 characters.',
            'password.letters' => 'The password must contain at least one letter.',
            'password.numbers' => 'The password must contain at least one number.',
        ]);

        $user->username = $request->username;
        if (Schema::hasColumn('users', 'email')) {
            $user->email = $request->filled('email') ? trim($request->email) : null;
        }
        $user->name = $request->name ?: $user->username;
        
        if ($isSuperAdmin && $request->has('role')) {
            $user->role = $request->role;
        }
        
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }
        if ($isSuperAdmin) {
            if ($request->has('sms_allocation') && $request->input('sms_allocation') !== null && $request->input('sms_allocation') !== '') {
                $user->sms_allocation = max(0, (int) $request->sms_allocation);
                // Do not reset sms_used: remaining = allocation - used (top-up behavior)
            }
            if (($user->isDocuMentorSupervisor() || $user->role === User::DM_ROLE_COORDINATOR) && $request->has('ai_tokens_allocation')) {
                $defaultAllocation = $user->role === User::DM_ROLE_COORDINATOR ? 3 : 10;
                $user->ai_tokens_allocation = max(0, (int) ($request->ai_tokens_allocation ?? $defaultAllocation));
            }
        }

        if ($request->filled('department_id')) {
            $user->department_id = (int) $request->department_id;
        } elseif ($request->has('department_id') && $request->department_id === '') {
            $user->department_id = null;
        }
        
        $user->save();

        // If supervisor is updating their own profile, redirect to profile page
        if (!$isSuperAdmin && $currentUser && $currentUser->id === $user->id) {
            return redirect()->route('dashboard.profile.show')
                ->with('success', 'Saved');
        }

        return redirect()->route('dashboard.users.index')
            ->with('success', 'Saved');
    }

    /**
     * Show password prompt to view/reset supervisor password.
     */
    public function showPasswordForm(User $user): View|RedirectResponse
    {
        $currentUser = $this->adminUser();
        $isSuperAdmin = $currentUser && $currentUser->isSuperAdmin();
        
        // Only Super Admin can view/reset passwords
        if (!$isSuperAdmin) {
            abort(403, 'Only Super Administrators can view user passwords.');
        }
        
        return view('admin.users.view-password', compact('user'));
    }

    /**
     * Verify admin password and generate/reset supervisor password.
     * Generates a temporary password that can be viewed once.
     */
    public function viewPassword(Request $request, User $user): RedirectResponse|View
    {
        $currentUser = $this->adminUser();
        $isSuperAdmin = $currentUser && $currentUser->isSuperAdmin();
        
        // Only Super Admin can view/reset passwords
        if (!$isSuperAdmin) {
            abort(403, 'Only Super Administrators can view user passwords.');
        }
        
        $request->validate([
            'admin_password' => 'required|string',
            'action' => 'nullable|in:generate,reset',
            'new_password' => 'nullable|string|min:8',
            'new_password_confirmation' => 'nullable|required_with:new_password|same:new_password',
        ]);
        
        // Verify admin's password
        if (!Hash::check($request->admin_password, $currentUser->password)) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Invalid password');
        }
        
        // Generate a random password
        if ($request->input('action') === 'generate') {
            $temporaryPassword = $this->generateTemporaryPassword();
            $user->password = Hash::make($temporaryPassword);
            $user->save();
            
            return view('admin.users.view-password', [
                'user' => $user,
                'password_verified' => true,
                'temporary_password' => $temporaryPassword,
                'message' => 'A new temporary password has been generated. Copy it now - it will not be shown again!',
            ]);
        }
        
        // Reset with custom password
        if ($request->filled('new_password')) {
            $user->password = Hash::make($request->new_password);
            $user->save();
            
            return redirect()->route('dashboard.users.index')
                ->with('success', 'Reset');
        }
        
        // Show password reset form
        return view('admin.users.view-password', [
            'user' => $user,
            'password_verified' => true,
            'message' => 'Password is set. You cannot view the original password (it\'s encrypted), but you can generate a new temporary password or set a custom one below.',
        ]);
    }

    /**
     * Reset supervisor password directly (without admin password verification).
     * Generates a temporary password and shows it to the admin.
     */
    public function resetPassword(User $user): RedirectResponse|View
    {
        $currentUser = $this->adminUser();
        $isSuperAdmin = $currentUser && $currentUser->isSuperAdmin();
        
        // Only Super Admin can reset passwords
        if (!$isSuperAdmin) {
            abort(403, 'Only Super Administrators can reset user passwords.');
        }
        
        $allowedForReset = [User::ROLE_SUPERVISOR, User::DM_ROLE_COORDINATOR, User::DM_ROLE_STUDENT, User::DM_ROLE_LEADER];
        if ($user->role === User::ROLE_SUPER_ADMIN) {
            // Any super admin can reset another super admin's password (including their own)
        } elseif (!in_array($user->role, $allowedForReset, true)) {
            return redirect()->route('dashboard.users.index')
                ->with('error', 'Cannot reset password for this role.');
        }
        
        // Generate a temporary password
        $temporaryPassword = $this->generateTemporaryPassword();
        $user->password = Hash::make($temporaryPassword);
        $user->save();
        
        // Revoke existing sessions
        $user->remember_token = null;
        $user->save();
        
        if (config('session.driver') === 'database' && Schema::hasColumn(config('session.table', 'sessions'), 'user_id')) {
            \Illuminate\Support\Facades\DB::table(config('session.table', 'sessions'))
                ->where('user_id', $user->id)
                ->delete();
        }
        
        return redirect()->route('dashboard.users.index')
            ->with('success', 'Reset')
            ->with('temp_password', $temporaryPassword)
            ->with('reset_user_id', $user->id);
    }

    /**
     * Revoke user access: clear remember_token and sessions so they must log in again.
     */
    public function revoke(User $user): RedirectResponse
    {
        $currentUser = $this->adminUser();
        $isSuperAdmin = $currentUser && $currentUser->isSuperAdmin();

        if (!$isSuperAdmin) {
            abort(403, 'Only Super Administrators can revoke user access.');
        }

        $allowedRoles = [User::ROLE_SUPER_ADMIN, User::ROLE_SUPERVISOR, User::DM_ROLE_COORDINATOR, User::DM_ROLE_STUDENT, User::DM_ROLE_LEADER];
        if (! in_array($user->role, $allowedRoles, true)) {
            return redirect()->route('dashboard.users.index')
                ->with('error', 'Error');
        }

        $user->remember_token = null;
        $user->save();

        // Delete sessions for this user (Laravel database driver)
        if (config('session.driver') === 'database' && Schema::hasColumn(config('session.table', 'sessions'), 'user_id')) {
            \Illuminate\Support\Facades\DB::table(config('session.table', 'sessions'))
                ->where('user_id', $user->id)
                ->delete();
        }

        return redirect()->route('dashboard.users.index')
            ->with('success', 'Revoked');
    }

    /**
     * Delete a supervisor user. Cannot delete super admins or yourself.
     */
    public function destroy(User $user): RedirectResponse
    {
        $currentUser = $this->adminUser();
        $isSuperAdmin = $currentUser && $currentUser->isSuperAdmin();

        if (!$isSuperAdmin) {
            abort(403, 'Only Super Administrators can delete users.');
        }

        if ($user->role === User::ROLE_SUPER_ADMIN) {
            return redirect()->route('dashboard.users.index')
                ->with('error', 'Cannot delete super admin.');
        }

        if ($currentUser && $currentUser->id === $user->id) {
            return redirect()->route('dashboard.users.index')
                ->with('error', 'Error');
        }

        $user->delete();

        return redirect()->route('dashboard.users.index')
            ->with('success', 'Deleted');
    }

    /**
     * Update SMS allocation for a supervisor (AJAX).
     */
    public function updateSms(Request $request): \Illuminate\Http\JsonResponse
    {
        $currentUser = $this->adminUser();
        $isSuperAdmin = $currentUser && $currentUser->isSuperAdmin();

        if (!$isSuperAdmin) {
            return response()->json([
                'success' => false,
                'message' => 'Only Super Administrators can set SMS allocation.',
            ], 403);
        }

        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'sms_allocation' => 'required|integer|min:0',
        ]);

        $user = User::findOrFail($request->user_id);

        if ($user->role !== User::ROLE_SUPERVISOR && $user->role !== User::DM_ROLE_COORDINATOR) {
            return response()->json([
                'success' => false,
                'message' => 'SMS allocation can only be set for supervisors and coordinators.',
            ], 422);
        }

        $creditsToAdd = max(0, (int) $request->sms_allocation);
        $user->sms_allocation = ($user->sms_allocation ?? 0) + $creditsToAdd;
        $user->save();

        $user->refresh();

        return response()->json([
            'success' => true,
            'allocation' => $user->sms_allocation,
            'used' => $user->sms_used ?? 0,
            'remaining' => $user->sms_remaining,
            'message' => 'SMS allocation updated successfully.',
        ]);
    }

    /**
     * Generate a secure temporary password.
     */
    private function generateTemporaryPassword(int $length = 12): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        $password = '';
        $max = strlen($chars) - 1;
        
        // Ensure at least one lowercase, one uppercase, one number, one special char
        $password .= 'abcdefghijklmnopqrstuvwxyz'[random_int(0, 25)];
        $password .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'[random_int(0, 25)];
        $password .= '0123456789'[random_int(0, 9)];
        $password .= '!@#$%^&*'[random_int(0, 7)];
        
        // Fill the rest randomly
        for ($i = strlen($password); $i < $length; $i++) {
            $password .= $chars[random_int(0, $max)];
        }
        
        // Shuffle to randomize position
        return str_shuffle($password);
    }
}
