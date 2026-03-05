<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Concerns\InteractsWithAdminSession;
use App\Models\DocuMentor\Chapter;
use App\Models\DocuMentor\Submission;
use App\Models\DocuMentor\AcademicYear;
use App\Models\DocuMentor\Project;
use App\Models\Setting;
use App\Models\User;
use App\Services\CloudinaryService;
use Illuminate\Support\Facades\DB;
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
            // Staff = Super Admin, Supervisors, Coordinators (no students)
            'users' => User::whereIn('role', [User::ROLE_SUPER_ADMIN, User::ROLE_SUPERVISOR, User::DM_ROLE_COORDINATOR])->count(),
            'students' => User::whereIn('role', [User::DM_ROLE_STUDENT, User::DM_ROLE_LEADER])->count(),
            'schools' => \App\Models\School::count(),
        ];
        $cloudinary_configured = CloudinaryService::isConfigured();
        $update_mode = Setting::getValue(Setting::KEY_UPDATE_MODE, '0') === '1';
        $update_started_at = $update_mode ? Setting::getValue(Setting::KEY_UPDATE_STARTED_AT) : null;
        $update_estimated_end = $update_mode ? Setting::getValue(Setting::KEY_UPDATE_ESTIMATED_END) : null;

        // Simple trend for admin dashboard: Docu Mentor activity over the last 7 days
        $trendDays = 7;
        $trendEnd = now();
        $trendStart = (clone $trendEnd)->subDays($trendDays - 1)->startOfDay();

        $studentRoles = [User::DM_ROLE_STUDENT, User::DM_ROLE_LEADER];
        $studentCountsByDate = User::whereIn('role', $studentRoles)
            ->whereBetween('created_at', [$trendStart, $trendEnd])
            ->selectRaw('DATE(created_at) as d, COUNT(*) as total')
            ->groupBy('d')
            ->pluck('total', 'd')
            ->all();

        $projectCountsByDate = Project::whereBetween('created_at', [$trendStart, $trendEnd])
            ->selectRaw('DATE(created_at) as d, COUNT(*) as total')
            ->groupBy('d')
            ->pluck('total', 'd')
            ->all();

        $submissionCountsByDate = Submission::whereBetween('created_at', [$trendStart, $trendEnd])
            ->selectRaw('DATE(created_at) as d, COUNT(*) as total')
            ->groupBy('d')
            ->pluck('total', 'd')
            ->all();

        $trendPoints = [];
        $trendMax = 0;
        for ($i = 0; $i < $trendDays; $i++) {
            $day = (clone $trendStart)->addDays($i);
            $key = $day->toDateString();
            $students = (int) ($studentCountsByDate[$key] ?? 0);
            $projects = (int) ($projectCountsByDate[$key] ?? 0);
            $submissions = (int) ($submissionCountsByDate[$key] ?? 0);
            $total = $students + $projects + $submissions;
            $trendPoints[] = [
                'label' => $day->format('D'),
                'date' => $day->format('d M'),
                'students' => $students,
                'projects' => $projects,
                'submissions' => $submissions,
                'total' => $total,
            ];
            if ($total > $trendMax) {
                $trendMax = $total;
            }
        }

        $adminTrend = [
            'points' => $trendPoints,
            'max' => max(1, $trendMax),
        ];

        // Extra Docu Mentor overview metrics for Super Admin
        $dmOverview = [
            'coordinators' => User::where('role', User::DM_ROLE_COORDINATOR)->count(),
            'supervisors' => User::where('role', User::ROLE_SUPERVISOR)->count(),
            'active_academic_years' => AcademicYear::active() ? 1 : 0,
            'projects' => Project::count(),
            'submissions' => Submission::count(),
        ];

        // Database health card: connection + tables + migrations overview
        $dbMeta = [
            'connected' => false,
            'driver' => null,
            'database' => null,
            'tables' => null,
            'migrations_total' => null,
            'last_migration' => null,
        ];

        try {
            $connection = DB::connection();
            $connection->getPdo(); // will throw if not connected
            $dbMeta['connected'] = true;
            $dbMeta['driver'] = $connection->getDriverName();
            $dbMeta['database'] = $connection->getDatabaseName();

            // Basic table count per driver
            if ($dbMeta['driver'] === 'mysql') {
                $dbName = $dbMeta['database'];
                $tableCount = DB::table('information_schema.tables')
                    ->where('table_schema', $dbName)
                    ->count();
                $dbMeta['tables'] = $tableCount;
            } elseif ($dbMeta['driver'] === 'sqlite') {
                $tableCount = DB::selectOne("SELECT COUNT(*) AS c FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'");
                $dbMeta['tables'] = (int) ($tableCount->c ?? 0);
            }

            // Migrations summary (when schema was last changed)
            if (DB::getSchemaBuilder()->hasTable('migrations')) {
                $dbMeta['migrations_total'] = DB::table('migrations')->count();
                $last = DB::table('migrations')->orderByDesc('batch')->orderByDesc('id')->first();
                if ($last) {
                    $dbMeta['last_migration'] = $last->migration . ' (batch ' . $last->batch . ')';
                }
            }
        } catch (\Throwable $e) {
            // Leave defaults; dashboard should still render
        }

        return view('admin.dashboard-admin', compact(
            'overview',
            'cloudinary_configured',
            'update_mode',
            'update_started_at',
            'update_estimated_end',
            'adminTrend',
            'dmOverview',
            'dbMeta'
        ));
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
