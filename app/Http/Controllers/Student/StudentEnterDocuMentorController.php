<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Bridge: student index/phone login → Docu Mentor.
 * Access is group-driven: student/group_leader in or leading a group in active academic year.
 */
class StudentEnterDocuMentorController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $studentId = session('student_id');
        if (!$studentId) {
            return redirect()->route('student.account.login.form')
                ->with('error', 'Please log in as a student first.');
        }

        $student = Student::find($studentId);
        if (!$student) {
            return redirect()->route('dashboard')
                ->with('error', 'Student record not found. Contact your administrator.');
        }

        $user = User::findOrCreateDocuMentorUserForStudent($student);
        if (!$user) {
            return redirect()->route('dashboard')
                ->with('error', 'Could not set up project access. Please add your phone number in your profile first.');
        }

        if (! $user->canAccessDocuMentorProjects()) {
            return redirect()->route('dashboard')
                ->with('error', 'Docu Mentor access is for project group leaders and students already in a group. Join a group or ask your coordinator to assign you as leader.');
        }

        // Ensure Docu Mentor session is active for this student
        session([
            'admin_authenticated' => true,
            'admin_user_id' => $user->id,
            'admin_role' => $user->role,
        ]);

        // Optional redirect route (e.g. dashboard.projects.index / dashboard.group.create)
        $redirect = $request->query('redirect');
        if ($redirect && is_string($redirect) && Str::startsWith($redirect, 'dashboard.')) {
            if ($redirect === 'dashboard.group.show' && $request->filled('group')) {
                return redirect()->route($redirect, ['group' => (int) $request->query('group')])
                    ->with('success', 'Welcome. You can now manage your projects.');
            }
            return redirect()->route($redirect)
                ->with('success', 'Welcome. You can now manage your projects.');
        }

        return redirect()->route('dashboard')
            ->with('success', 'Welcome. You can join a group or create a project.');
    }

}
