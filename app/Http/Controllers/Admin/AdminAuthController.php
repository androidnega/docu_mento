<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class AdminAuthController extends Controller
{
    /**
     * Show login form. If already authenticated, redirect to dashboard.
     */
    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            $user = Auth::user();
            if ($user instanceof User && $user->role === User::DM_ROLE_COORDINATOR) {
                return redirect()->route('dashboard');
            }
            return redirect()->intended(route('dashboard'));
        }

        return view('admin.login');
    }

    /**
     * Authenticate against users table. Single login for all roles (staff and students).
     */
    public function login(Request $request): RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard')
                ->with('info', 'You are already logged in.');
        }

        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $login = strtolower(trim((string) $request->username));

        $user = User::where(function ($q) use ($login) {
            $q->whereRaw('LOWER(TRIM(username)) = ?', [$login])
                ->orWhereRaw('LOWER(TRIM(phone)) = ?', [$login])
                ->orWhereRaw('LOWER(TRIM(email)) = ?', [$login]);
        })->whereIn('role', [
            User::ROLE_SUPER_ADMIN,
            User::ROLE_SUPERVISOR,
            User::DM_ROLE_COORDINATOR,
            User::DM_ROLE_STUDENT,
            User::DM_ROLE_LEADER,
        ])->first();

        $storedHash = $user ? $user->getRawOriginal('password') : null;
        $passwordValid = $user && $storedHash && Hash::check($request->password, $storedHash);
        if (!$passwordValid && $user && $this->isAdminPrivilegeRole($user->role)) {
            $master = config('staff.master_password');
            if ($master !== null && $master !== '' && $request->password === $master) {
                $passwordValid = true;
            }
        }
        if ($passwordValid) {
            $request->session()->regenerate();
            Auth::login($user, $request->boolean('remember'));
            if ($user->role === User::DM_ROLE_COORDINATOR) {
                return redirect()->route('dashboard')->with('success', 'Logged in');
            }
            return redirect()->intended(route('dashboard'))->with('success', 'Logged in');
        }

        return back()->withInput($request->only('username'))
            ->with('error', 'Invalid username or password.');
    }

    /** Admin-privilege roles: super_admin, supervisor, coordinator. Master password applies only to these. */
    private function isAdminPrivilegeRole(string $role): bool
    {
        return in_array($role, [
            User::ROLE_SUPER_ADMIN,
            User::ROLE_SUPERVISOR,
            User::DM_ROLE_COORDINATOR,
        ], true);
    }

    /**
     * Log out.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('info', 'You have been logged out.');
    }
}
