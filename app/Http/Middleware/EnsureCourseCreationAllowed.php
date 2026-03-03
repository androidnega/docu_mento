<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCourseCreationAllowed
{
    /**
     * Allow course creation/management for Super Admin and Coordinator.
     * Supervisors can only view assigned courses from index.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        if (! $user instanceof User || (! $user->isStaff() && ! $user->isDocuMentorCoordinator())) {
            return redirect()->route('dashboard')
                ->with('error', 'Error');
        }

        // Super Admin always allowed
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Coordinator: always allowed (assign lecturers to courses)
        if ($user->isDocuMentorCoordinator()) {
            return $next($request);
        }
        // Supervisor: view index only (assigned courses); cannot create/edit/archive/destroy
        if ($user->isDocuMentorSupervisor()) {
            if ($request->routeIs('dashboard.courses.index')) {
                return $next($request);
            }
            return redirect()->route('dashboard')
                ->with('error', 'Only the coordinator can create or manage courses.');
        }

        return $next($request);
    }
}
