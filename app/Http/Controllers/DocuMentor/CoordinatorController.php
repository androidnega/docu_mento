<?php

namespace App\Http\Controllers\DocuMentor;

use App\Http\Controllers\Controller;
use App\Models\DocuMentor\AcademicYear;
use App\Models\DocuMentor\Category;
use App\Models\DocuMentor\Project;
use App\Models\DocuMentor\ProjectGroup;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * Coordinator dashboard: all department projects (filtered by academic year), pending approval,
 * deadlines for year, statistics per status. Coordinator can: assign supervisor, approve project,
 * set status, approval date, deadline per academic year.
 */
class CoordinatorController extends Controller
{
    public function dashboard(): View
    {
        $user = request()->attributes->get('dm_user') ?? auth()->user();
        if (! $user || ! $user->isDocuMentorCoordinator()) {
            abort(403, 'Access denied.');
        }

        // Coordinator → role → department → academic_years: only years belonging to coordinator's department
        $deptId = $user->coordinatorDepartmentId();
        $academicYearsQuery = AcademicYear::orderByDesc('year');
        if ($deptId !== null) {
            $academicYearsQuery->where(function ($q) use ($deptId) {
                $q->where('department_id', $deptId)->orWhereNull('department_id');
            });
        }
        $academicYears = $academicYearsQuery->get();

        // Active year: use is_active = true, or fall back to latest year if none is marked active
        $activeAcademicYear = AcademicYear::active();
        if (! $activeAcademicYear) {
            $activeAcademicYear = AcademicYear::orderByDesc('year')->first();
            if ($activeAcademicYear) {
                AcademicYear::query()->update(['is_active' => false]);
                $activeAcademicYear->update(['is_active' => true]);
            }
        }
        if ($activeAcademicYear && $deptId !== null && $activeAcademicYear->department_id !== null && (int) $activeAcademicYear->department_id !== $deptId) {
            $inScope = $academicYears->firstWhere('id', $activeAcademicYear->id);
            if (! $inScope) {
                $activeAcademicYear = $academicYears->first();
            }
        }

        $projectsQuery = Project::query();
        if ($activeAcademicYear) {
            $projectsQuery->where('academic_year_id', $activeAcademicYear->id);
        } elseif ($deptId !== null) {
            $projectsQuery->whereHas('academicYear', fn ($q) => $q->where('department_id', $deptId));
        }

        $departmentProjectsCount = (clone $projectsQuery)->count();
        $projectsPendingApproval = (clone $projectsQuery)->where('approved', false)
            ->with(['group', 'category', 'supervisors'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();
        $projectsForApprovalTable = (clone $projectsQuery)
            ->with(['group', 'category', 'supervisors'])
            ->orderByDesc('created_at')
            ->limit(15)
            ->get();
        $projectsPendingApprovalCount = (clone $projectsQuery)->where('approved', false)->count();

        $statsPerStatus = (clone $projectsQuery)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();
        $statusOrder = [Project::STATUS_DRAFT, Project::STATUS_SUBMITTED, Project::STATUS_APPROVED, Project::STATUS_REJECTED, Project::STATUS_IN_PROGRESS, Project::STATUS_COMPLETED, Project::STATUS_GRADED, Project::STATUS_ARCHIVED];
        $statsPerStatus = array_replace(array_fill_keys($statusOrder, 0), $statsPerStatus);

        $deadlinesForYear = [];
        if ($activeAcademicYear) {
            $deadlinesForYear['effective'] = $activeAcademicYear->effective_deadline;
            $deadlinesForYear['list'] = \Illuminate\Support\Facades\Schema::hasColumn('deadlines', 'academic_year_id')
                ? $activeAcademicYear->deadlines()->orderBy('deadline_date')->get()
                : collect();
        }

        $overview = [
            'projects' => $departmentProjectsCount,
            'projects_approved' => (clone $projectsQuery)->where('approved', true)->count(),
            'groups' => $activeAcademicYear
                ? ProjectGroup::where('academic_year_id', $activeAcademicYear->id)->count()
                : ProjectGroup::count(),
            'class_groups' => 0,
            'group_leaders' => Schema::hasColumn('users', 'group_leader')
                ? User::where('group_leader', true)->count()
                : (int) User::where('role', User::DM_ROLE_LEADER)->count(),
            'students' => $user->docuMentorStudentsInScope()->count(),
        ];

        $rejectedCount = (int) ($statsPerStatus[Project::STATUS_REJECTED] ?? 0);

        return view('docu-mentor.coordinators.dashboard', compact(
            'user',
            'overview',
            'academicYears',
            'activeAcademicYear',
            'projectsPendingApproval',
            'projectsPendingApprovalCount',
            'projectsForApprovalTable',
            'statsPerStatus',
            'deadlinesForYear',
            'rejectedCount'
        ));
    }
}
