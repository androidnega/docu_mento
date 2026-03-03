<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DocuMentorStudent
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->attributes->get('dm_user') ?? $request->user();
        $isStudent = $user && (
            (method_exists($user, 'isDocuMentorStudent') && $user->isDocuMentorStudent())
            || (method_exists($user, 'isStudentRole') && $user->isStudentRole())
        );
        if (! $isStudent) {
            abort(403, 'Student access required.');
        }
        return $next($request);
    }
}
