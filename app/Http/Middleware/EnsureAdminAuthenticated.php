<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminAuthenticated
{
    /**
     * Require authenticated user (Laravel Auth). Coordinator restricted to coordinator/supervisor/student routes only.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->routeIs('login') || $request->routeIs('login.post')) {
            return $next($request);
        }

        if (! auth()->check()) {
            return redirect()->guest(route('login'))
                ->with('error', 'Please log in.');
        }

        $user = auth()->user();
        if (! $user instanceof User) {
            auth()->logout();
            return redirect()->guest(route('login'))
                ->with('error', 'Session invalid. Please log in again.');
        }

        $user->load('institution');

        // Coordinators may only access dashboard, coordinators.*, docu-mentor.*, profile, logout
        $coordinatorAllowed = $request->routeIs('dashboard')
            || $request->routeIs('dashboard.ping')
            || $request->routeIs('dashboard.profile.*')
            || $request->routeIs('dashboard.coordinators.*')
            || $request->routeIs('dashboard.docu-mentor.*')
            || $request->routeIs('dashboard.departments.by-school')
            || $request->routeIs('logout') || $request->routeIs('logout.get');
        if ($user->role === User::DM_ROLE_COORDINATOR && ! $coordinatorAllowed) {
            return redirect()->route('dashboard')
                ->with('error', 'You do not have access to the admin area. This section is for administrators and supervisors only.');
        }

        return $next($request);
    }
}
