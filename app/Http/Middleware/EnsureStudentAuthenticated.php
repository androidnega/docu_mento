<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStudentAuthenticated
{
    /**
     * Require authenticated user with student or group_leader role (single auth).
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            return redirect()->guest(route('login'))
                ->with('error', 'Please log in to access the student dashboard.');
        }

        $user = auth()->user();
        if (! $user instanceof User) {
            return redirect()->guest(route('login'))->with('error', 'Invalid session.');
        }

        if (! in_array($user->roleName(), [User::ROLE_NAME_STUDENT, User::ROLE_NAME_GROUP_LEADER], true)) {
            return redirect()->route('dashboard')
                ->with('error', 'Student access required.');
        }

        return $next($request);
    }
}
