<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Concerns\InteractsWithAdminSession;
use App\Models\DocuMentor\Chapter;
use App\Models\DocuMentor\Submission;
use App\Models\Setting;
use App\Models\User;
use App\Services\CloudinaryService;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    use InteractsWithAdminSession;

    /** Unified dashboard: show admin or supervisor content based on role. */
    public function index(): View|\Illuminate\Http\RedirectResponse
    {
        $user = $this->adminUser();
        if ($user && $user->role === User::ROLE_SUPER_ADMIN) {
            return $this->adminDashboard();
        }
        return $this->supervisorDashboard();
    }

    /** Admin (Super Admin) dashboard: stats (users, students). */
    public function adminDashboard(): View
    {
        $overview = [
            'users' => User::count(),
            'students' => User::whereIn('role', [User::DM_ROLE_STUDENT, User::DM_ROLE_LEADER])->count(),
            'schools' => \App\Models\School::count(),
        ];
        $cloudinary_configured = CloudinaryService::isConfigured();
        $update_mode = Setting::getValue(Setting::KEY_UPDATE_MODE, '0') === '1';
        $update_started_at = $update_mode ? Setting::getValue(Setting::KEY_UPDATE_STARTED_AT) : null;
        $update_estimated_end = $update_mode ? Setting::getValue(Setting::KEY_UPDATE_ESTIMATED_END) : null;
        return view('admin.dashboard-admin', compact('overview', 'cloudinary_configured', 'update_mode', 'update_started_at', 'update_estimated_end'));
    }

    /**
     * Supervisor dashboard: assigned projects (via project_supervisors), pending submissions, comments needing follow-up.
     * Supervisor can: review submissions, comment, mark reviewed. Cannot: create project, approve final project.
     */
    public function supervisorDashboard(): View
    {
        $user = $this->adminUser();
        $needsFacultyDepartment = $user && $user->isDocuMentorSupervisor() && (! $user->faculty_id || ! $user->department_id);

        $assignedProjects = collect();
        $pendingSubmissionsCount = 0;
        $commentsFollowUpCount = 0;

        if ($user && $user->isDocuMentorSupervisor()) {
            $projectIds = $user->supervisedProjects()->pluck('projects.id')->all();
            $assignedProjects = $user->supervisedProjects()
                ->with(['group', 'academicYear', 'category'])
                ->orderByDesc('created_at')
                ->get();

            if (! empty($projectIds)) {
                $chapterIds = Chapter::whereIn('project_id', $projectIds)->pluck('id')->all();
                if (! empty($chapterIds)) {
                    $baseSubmissions = Submission::whereIn('chapter_id', $chapterIds);
                    $pendingSubmissionsCount = (clone $baseSubmissions)
                        ->whereDoesntHave('comments', fn ($q) => $q->where('user_id', $user->id))
                        ->count();
                    $commentsFollowUpCount = (clone $baseSubmissions)
                        ->whereHas('comments', fn ($q) => $q->where('user_id', $user->id))
                        ->count();
                }
            }
        }

        $activeAcademicYear = \App\Models\DocuMentor\AcademicYear::active();

        return view('admin.dashboard-supervisor', compact(
            'needsFacultyDepartment',
            'assignedProjects',
            'pendingSubmissionsCount',
            'commentsFollowUpCount',
            'activeAcademicYear'
        ));
    }
}
