<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;
use Illuminate\Contracts\View\View;

class StudentDashboardController extends Controller
{
    /**
     * Student / Leader dashboard. Single route: load user, academic year, group, project (if any).
     * Decision tree: No group → Create group; Group, no project → Create project; Project exists → overview (status, supervisors, deadline, chapters, proposals).
     */
    public function index(): View
    {
        $user = auth()->user();
        $displayName = $user && method_exists($user, 'name') ? ($user->name ?: $user->email ?? 'User') : 'User';
        $student = null;
        if ($user && method_exists($user, 'index_number') && trim((string) ($user->index_number ?? '')) !== '') {
            $student = Student::where('index_number_hash', Student::hashIndexNumber($user->index_number))->first();
        }

        $hasProjectAccess = false;
        $isGroupLeader = false;
        $leaderWithoutGroup = false;
        $leaderHasProject = false;
        $docuMentorGroup = null;
        $leaderProject = null;
        $academicYear = null;
        $projectDeadline = null;

        if ($user instanceof User) {
            $isGroupLeader = (bool) User::where('id', $user->id)->value('group_leader');
            $hasProjectAccess = $user->isDocuMentorStudent() || $user->isStudentRole() || $isGroupLeader;
            $leaderWithoutGroup = $isGroupLeader && $user->ledDocuMentorGroups()->doesntExist();
            if ($isGroupLeader) {
                $leaderHasProject = $user->ledDocuMentorGroups()->whereHas('project')->exists();
            }
            $leaderGroup = $user->ledDocuMentorGroups()->with(['project', 'academicYear', 'members'])->first();
            $memberGroup = $user->docuMentorGroups()->with(['project', 'academicYear', 'members'])->first();
            $docuMentorGroup = $leaderGroup ?: $memberGroup;

            // Leader project overview: project (if exists) with supervisors, chapters (and submissions for progress), proposals for dashboard
            if ($leaderGroup && $leaderGroup->project) {
                $leaderProject = $leaderGroup->project;
                $leaderProject->load(['supervisors', 'chapters' => fn ($q) => $q->orderBy('order')->with('submissions'), 'academicYear', 'proposals', 'category']);
                $academicYear = $leaderProject->academicYear ?? $leaderGroup->academicYear;
                $projectDeadline = $leaderProject->submission_deadline
                    ?? ($academicYear ? $academicYear->effective_deadline : null);
            } elseif ($docuMentorGroup) {
                $academicYear = $docuMentorGroup->academicYear;
            }
        }

        return view('student.dashboard.index', [
            'student' => $student,
            'displayName' => $displayName,
            'hasProjectAccess' => $hasProjectAccess,
            'isGroupLeader' => $isGroupLeader,
            'leaderWithoutGroup' => $leaderWithoutGroup,
            'leaderHasProject' => $leaderHasProject,
            'docuMentorGroup' => $docuMentorGroup,
            'leaderProject' => $leaderProject,
            'academicYear' => $academicYear,
            'projectDeadline' => $projectDeadline,
        ]);
    }

    public function profile(): View
    {
        $user = auth()->user();
        $student = $user && method_exists($user, 'index_number') ? Student::where('index_number_hash', Student::hashIndexNumber($user->index_number ?? ''))->first() : null;
        if (! $user) {
            abort(403, 'Not logged in.');
        }
        return view('student.dashboard.profile', compact('student'));
    }

    public function updateProfile(\Illuminate\Http\Request $request)
    {
        $user = auth()->user();
        $student = $user && method_exists($user, 'index_number') ? Student::where('index_number_hash', Student::hashIndexNumber($user->index_number ?? ''))->first() : null;
        if (! $user) {
            abort(403, 'Not logged in.');
        }
        if (! $student) {
            return redirect()->route('dashboard')->with('info', 'Profile is managed from your user account.');
        }
        $request->validate(['student_name' => 'nullable|string|max:255']);
        $student->student_name = $request->filled('student_name') ? ucwords(strtolower(trim($request->student_name))) : $student->student_name;
        $student->save();
        return redirect()->route('dashboard.my-profile')->with('success', 'Profile updated.');
    }

    public function calendar(): View
    {
        $user = auth()->user();
        $student = $user && method_exists($user, 'index_number') ? Student::where('index_number_hash', Student::hashIndexNumber($user->index_number ?? ''))->first() : null;
        if (! $user) {
            abort(403, 'Not logged in.');
        }
        return view('student.dashboard.calendar', compact('student'));
    }

    public function courseMaterials(): View
    {
        $user = auth()->user();
        if (! $user) {
            abort(403, 'Not logged in.');
        }
        return view('student.dashboard.materials');
    }
}
