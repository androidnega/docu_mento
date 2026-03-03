<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSupervisorOnlyRole
{
    /**
     * Require Supervisor role only (Docu Mentor supervisors).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        if (! $user instanceof User || ! $user->isDocuMentorSupervisor()) {
            return redirect()->route('dashboard')
                ->with('error', 'Error');
        }

        return $next($request);
    }
}
