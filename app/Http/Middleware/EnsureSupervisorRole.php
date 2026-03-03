<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSupervisorRole
{
    /**
     * Require Supervisor or Super Admin (staff). Super Admin can view all; supervisor sees only their own.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        if (! $user instanceof User || ! $user->isStaff()) {
            return redirect()->route('dashboard')
                ->with('error', 'Error');
        }

        return $next($request);
    }
}
