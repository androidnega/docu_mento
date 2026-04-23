<?php

namespace App\Http\Middleware;

use App\Models\Student;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * After OTP login, students must submit first + last name once (students.legal_name_completed_at).
 */
class EnsureStudentLegalNameCompleted
{
    public function handle(Request $request, Closure $next): Response
    {
        $routeName = $request->route()?->getName();
        if (is_string($routeName) && str_starts_with($routeName, 'student.passkey.')) {
            return $next($request);
        }

        if ($request->routeIs(
            'student.account.legal-name',
            'student.account.legal-name.store',
            'student.account.logout',
        )) {
            return $next($request);
        }

        $user = auth()->user();
        if (! $user instanceof User) {
            return $next($request);
        }

        if (! $user->isDocuMentorStudent() && ! $user->isStudentRole()) {
            return $next($request);
        }

        $student = Student::findForDocuMentorUser($user);
        if (! $student || $student->legal_name_completed_at !== null) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Please complete your name to continue.',
                'redirect' => route('student.account.legal-name'),
            ], 422);
        }

        return redirect()->route('student.account.legal-name');
    }
}
