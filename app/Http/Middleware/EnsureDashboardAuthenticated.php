<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDashboardAuthenticated
{
    /**
     * Require authenticated user (Laravel Auth). All dashboard users, including students, are in the users table.
     * Students are recognized by role (student or leader); no separate students table for dashboard access.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            return redirect()->route('login')->with('info', 'Please log in to access the dashboard.');
        }

        $user = auth()->user();
        if (! $user instanceof User) {
            auth()->logout();
            return redirect()->route('login')->with('error', 'Invalid session. Please log in again.');
        }

        // Student-side: role student/group_leader or group_leader flag (Docu Mento leaders)
        $allowed = $user->isStaff()
            || $user->isDocuMentorStudent()
            || $user->isDocuMentorCoordinator()
            || $user->isDocuMentorSupervisor()
            || $user->isStudentRole()
            || $user->isGroupLeader();
        if (! $allowed) {
            auth()->logout();
            return redirect()->route('login')->with('error', 'You do not have dashboard access.');
        }

        return $next($request);
    }
}
