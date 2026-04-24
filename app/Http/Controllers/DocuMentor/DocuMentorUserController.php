<?php

namespace App\Http\Controllers\DocuMentor;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class DocuMentorUserController extends Controller
{
    public function index(): View
    {
        $users = User::whereIn('role', [
            User::DM_ROLE_STUDENT,
            User::DM_ROLE_LEADER,
            User::ROLE_SUPERVISOR,
            User::DM_ROLE_COORDINATOR,
            User::ROLE_SUPER_ADMIN,
        ])
            ->orderBy('name')
            ->get();

        return view('docu-mentor.coordinators.users.index', compact('users'));
    }

    public function create(): View
    {
        return view('docu-mentor.coordinators.users.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $studentish = in_array($request->role, ['student', 'leader'], true);
        $nameRules = ['required', 'string', 'max:255'];
        $usernameRules = ['required', 'string', 'max:255', 'unique:users,username'];
        if ($studentish) {
            $nameRules[] = function (string $attribute, mixed $value, \Closure $fail) use ($request): void {
                $v = trim((string) $value);
                if (User::docuMentorStudentLegalNameInvalid($v, null, trim((string) $request->username))) {
                    $fail('Use a real first and last name: no digits, and not the same as the username.');
                }
            };
            $usernameRules[] = function (string $attribute, mixed $value, \Closure $fail): void {
                if (User::docuMentorStudentUsernameIsNumericOnly((string) $value)) {
                    $fail('Username cannot be numbers only. Use letters, for example firstname.lastname.');
                }
            };
        }

        $request->validate([
            'name' => $nameRules,
            'phone' => 'nullable|string|max:20',
            'username' => $usernameRules,
            'email' => 'nullable|email|max:255',
            'password' => 'required|string|min:6|confirmed',
            'role' => 'required|in:student,leader,supervisor,coordinator',
        ]);

        User::create([
            'name' => $request->name,
            'phone' => $request->phone ?: null,
            'username' => $request->username,
            'email' => $request->email ?: null,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        return redirect()->route('docu-mentor.coordinators.users.index')
            ->with('success', 'User created.');
    }

    public function edit(User $user): View
    {
        return view('docu-mentor.coordinators.users.edit', compact('user'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $studentish = in_array($request->role, ['student', 'leader'], true);
        $nameRules = ['required', 'string', 'max:255'];
        $usernameRules = ['required', 'string', 'max:255', 'unique:users,username,'.$user->id];
        if ($studentish) {
            $nameRules[] = function (string $attribute, mixed $value, \Closure $fail) use ($request): void {
                $v = trim((string) $value);
                if (User::docuMentorStudentLegalNameInvalid($v, (string) ($user->index_number ?? ''), trim((string) $request->username))) {
                    $fail('Use a real first and last name: no digits, index-style values, or the same as the username.');
                }
            };
            $usernameRules[] = function (string $attribute, mixed $value, \Closure $fail): void {
                if (User::docuMentorStudentUsernameIsNumericOnly((string) $value)) {
                    $fail('Username cannot be numbers only. Use letters, for example firstname.lastname.');
                }
            };
        }

        $request->validate([
            'name' => $nameRules,
            'phone' => 'nullable|string|max:20',
            'username' => $usernameRules,
            'email' => 'nullable|email|max:255',
            'role' => 'required|in:student,leader,supervisor,coordinator,super_admin',
        ]);

        $user->update($request->only('name', 'phone', 'username', 'email', 'role'));

        if ($request->filled('password')) {
            $request->validate(['password' => 'string|min:6|confirmed']);
            $user->update(['password' => Hash::make($request->password)]);
        }

        return redirect()->route('docu-mentor.coordinators.users.index')
            ->with('success', 'User updated.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === request()->attributes->get('dm_user')?->id) {
            return back()->with('error', 'Cannot delete yourself.');
        }
        $user->delete();

        return redirect()->route('docu-mentor.coordinators.users.index')
            ->with('success', 'User deleted.');
    }
}
