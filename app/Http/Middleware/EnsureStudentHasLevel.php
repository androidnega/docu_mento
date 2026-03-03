<?php

namespace App\Http\Middleware;

use App\Models\Student;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStudentHasLevel
{
    /**
     * No-op: access is now database-driven (group_members, project_groups, academic_year).
     * Kept for backward compatibility on routes that still reference it.
     */
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }
}
