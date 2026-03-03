<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restrict access to users whose roleName() is in the allowed list.
 * Usage: ->middleware('role:student,group_leader') or ->middleware('role:coordinator').
 * Requires auth middleware to run first (auth()->user() must be set).
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$allowedRoles): Response
    {
        if (! $request->user()) {
            return redirect()->guest(route('login'))
                ->with('error', 'Please log in to access this page.');
        }

        $user = $request->user();
        if (! method_exists($user, 'roleName')) {
            abort(403, 'Invalid user type.');
        }

        $roleName = $user->roleName();
        $allowed = array_map('strtolower', $allowedRoles);
        if (! in_array($roleName, $allowed, true)) {
            abort(403, 'You do not have access to this area.');
        }

        return $next($request);
    }
}
