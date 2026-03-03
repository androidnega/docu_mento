<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DocuMentorAuth
{
    /**
     * Require authenticated user with Docu Mentor access (student, group_leader, supervisor, coordinator, or admin).
     * Sets dm_user on request for backward compatibility.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            return redirect()->route('login')
                ->with('error', 'Please log in to access Docu Mentor.');
        }

        $user = auth()->user();
        if (! $user instanceof User) {
            return redirect()->route('login')
                ->with('error', 'Invalid session.');
        }

        $allowed = $user->isDocuMentorStudent()
            || $user->isDocuMentorSupervisor()
            || $user->isDocuMentorCoordinator()
            || $user->isStudentRole();
        if (! $allowed) {
            return redirect()->route('login')
                ->with('error', 'You do not have access to Docu Mentor.');
        }

        $request->attributes->set('dm_user', $user);

        return $next($request);
    }
}
