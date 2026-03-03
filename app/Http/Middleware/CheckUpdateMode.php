<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUpdateMode
{
    /**
     * When update mode is on: only allow staff login routes and already-logged-in staff.
     * Everyone else sees the maintenance page.
     * If the database is unavailable (e.g. wrong .env), allow the request so maintenance URLs still work.
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $updateMode = Setting::getValue(Setting::KEY_UPDATE_MODE, '0') === '1';
        } catch (\Throwable $e) {
            // Database missing or misconfigured: allow request so /clear-cache, /run-migrations etc. can run
            return $next($request);
        }

        if (! $updateMode) {
            return $next($request);
        }

        $path = $request->path();
        $allowedPaths = ['up', 'login', 'password/forgot', 'password/reset', 'migrate-sqlite-to-mysql', 'run-migrations', 'migration', 'themigration', 'fix-pull', 'thekey', 'clear-cache', 'maintenance'];
        foreach ($allowedPaths as $allowed) {
            if ($path === $allowed || str_starts_with($path, $allowed . '/')) {
                return $next($request);
            }
        }

        if (auth()->check()) {
            $user = auth()->user();
            if ($user instanceof User && $user->isStaff()) {
                return $next($request);
            }
        }

        try {
            $startedAt = Setting::getValue(Setting::KEY_UPDATE_STARTED_AT);
            $estimatedEnd = Setting::getValue(Setting::KEY_UPDATE_ESTIMATED_END);
        } catch (\Throwable $e) {
            $startedAt = null;
            $estimatedEnd = null;
        }
        return response()->view('maintenance', [
            'update_started_at' => $startedAt ? \Carbon\Carbon::parse($startedAt) : null,
            'update_estimated_end' => $estimatedEnd ? \Carbon\Carbon::parse($estimatedEnd) : null,
        ], 503);
    }
}
