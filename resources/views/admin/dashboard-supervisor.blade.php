@extends('layouts.dashboard')

@section('title', 'Dashboard')
@section('dashboard_heading', 'Dashboard')

@section('dashboard_content')
@php
    $user = auth()->user();
    $showSmsForUser = $user && $user->isDocuMentorCoordinator();
    $smsRemaining = $showSmsForUser ? $user->sms_remaining : 0;
    $showLowSmsWarning = $showSmsForUser && $smsRemaining < 100 && $smsRemaining > 0;
    $supervisorName = $user ? ($user->name ?? $user->username ?? 'Supervisor') : 'Supervisor';
@endphp

    <div class="w-full space-y-6 min-w-0 overflow-x-hidden">
    {{-- Top bar: Academic Year + Welcome --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            @if(isset($activeAcademicYear) && $activeAcademicYear)
                <p class="text-sm text-slate-500 dark:text-slate-300">Academic Year: <strong class="text-slate-700 dark:text-slate-100">{{ $activeAcademicYear->year }}</strong></p>
            @endif
            <p class="text-slate-800 dark:text-slate-50 font-medium mt-0.5">Welcome, {{ $supervisorName }}</p>
        </div>
    </div>

    {{-- Faculty/Department Notice --}}
    @if(isset($needsFacultyDepartment) && $needsFacultyDepartment)
    <div id="faculty-department-notice" class="rounded-xl border border-amber-200 bg-amber-50 dark:border-amber-500/70 dark:bg-amber-900/40 p-4 flex items-start gap-3" role="alert">
        <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-amber-900 dark:text-amber-100">Complete Your Profile</p>
            <p class="mt-1 text-sm text-amber-800 dark:text-amber-50">Please select your faculty and department. <a href="{{ route('dashboard.users.edit', ['user' => $user, 'complete_profile' => 1]) }}" class="font-semibold underline hover:text-amber-900 dark:hover:text-amber-200">Update profile</a>.</p>
        </div>
        <button type="button" onclick="dismissFacultyDepartmentNotice()" class="flex-shrink-0 text-amber-600 dark:text-amber-200 hover:text-amber-800 dark:hover:text-amber-100" aria-label="Dismiss"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
    </div>
    @endif

    @if($showLowSmsWarning)
    <div id="low-sms-warning" class="rounded-xl border border-red-200 bg-red-100 dark:border-red-500/70 dark:bg-red-900/40 p-4 flex items-start gap-3" role="alert">
        <p class="text-sm text-red-900 dark:text-red-100 flex-1">Low SMS balance: <strong>{{ $smsRemaining }}</strong> remaining.</p>
        <button type="button" onclick="dismissLowSmsWarning()" class="flex-shrink-0 text-red-600 dark:text-red-200 hover:text-red-800 dark:hover:text-red-100" aria-label="Dismiss"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
    </div>
    @endif

    {{-- STATS ROW --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow p-5">
            <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wide">Assigned projects</p>
            <p class="mt-1 text-2xl font-bold tabular-nums text-slate-900 dark:text-slate-50">{{ $assignedProjects->count() ?? 0 }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow p-5">
            <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wide">Pending reviews</p>
            <p class="mt-1 text-2xl font-bold tabular-nums text-amber-700 dark:text-amber-300">{{ $pendingSubmissionsCount ?? 0 }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow p-5">
            <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wide">Reviewed chapters</p>
            <p class="mt-1 text-2xl font-bold tabular-nums text-green-700 dark:text-green-300">{{ $commentsFollowUpCount ?? 0 }}</p>
        </div>
    </div>

    {{-- ASSIGNED PROJECTS TABLE --}}
    <section class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700/80">
            <h2 class="text-sm font-semibold text-slate-800 dark:text-slate-100">Assigned projects</h2>
            <p class="text-xs text-slate-500 dark:text-slate-300 mt-0.5">Projects assigned to you via project_supervisors. View submissions, add comments, mark reviewed. You cannot approve the final project.</p>
        </div>
        @if($assignedProjects->isEmpty())
            <div class="p-8 text-center text-slate-500 dark:text-slate-300">
                <p>No projects assigned to you yet.</p>
                <p class="text-sm mt-1">A coordinator can assign you as a supervisor to projects.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                    <thead class="bg-slate-50 dark:bg-slate-900/80">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Project title</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Group</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800 bg-white dark:bg-slate-900">
                        @foreach($assignedProjects as $project)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/70">
                                <td class="px-4 py-3 text-sm font-medium text-slate-900 dark:text-slate-100">{{ $project->title }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">{{ $project->group?->name ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    @php
                                        $st = $project->approved ? 'approved' : 'pending';
                                        $stLabel = $project->approved ? 'Active' : 'Pending';
                                    @endphp
                                    <x-status-badge :status="$st" :label="$stLabel" />
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('dashboard.docu-mentor.projects.show', $project) }}" class="inline-flex items-center rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-1.5 text-sm font-medium text-slate-700 dark:text-slate-100 hover:bg-slate-50 dark:hover:bg-slate-800 no-underline">View</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    {{-- Your role --}}
    <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow p-5">
        <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-100">Your role</h3>
        <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
            <div>
                <p class="font-medium text-slate-700 dark:text-slate-200">You can</p>
                <ul class="mt-1 list-disc list-inside text-slate-600 dark:text-slate-300 space-y-0.5">
                    <li>View submissions</li>
                    <li>Add comments</li>
                    <li>Mark chapter as reviewed (open/close chapters)</li>
                </ul>
            </div>
            <div>
                <p class="font-medium text-slate-700 dark:text-slate-200">You cannot</p>
                <ul class="mt-1 list-disc list-inside text-slate-600 dark:text-slate-300 space-y-0.5">
                    <li>Create a project</li>
                    <li>Approve final project (coordinator only)</li>
                </ul>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function() {
    const WARNING_KEY = 'low_sms_warning_dismissed';
    const DISMISS_HOURS = 24;

    function dismissLowSmsWarning() {
        const warning = document.getElementById('low-sms-warning');
        if (warning) {
            warning.style.display = 'none';
            const dismissUntil = Date.now() + (DISMISS_HOURS * 60 * 60 * 1000);
            localStorage.setItem(WARNING_KEY, dismissUntil.toString());
        }
    }

    function shouldShowWarning() {
        const dismissed = localStorage.getItem(WARNING_KEY);
        if (!dismissed) return true;
        const dismissUntil = parseInt(dismissed, 10);
        return Date.now() > dismissUntil;
    }

    const warning = document.getElementById('low-sms-warning');
    if (warning && !shouldShowWarning()) {
        warning.style.display = 'none';
    }

    const FACULTY_NOTICE_KEY = 'faculty_department_notice_dismissed';
    const FACULTY_DISMISS_HOURS = 24;

    function dismissFacultyDepartmentNotice() {
        const notice = document.getElementById('faculty-department-notice');
        if (notice) {
            notice.style.display = 'none';
            const dismissUntil = Date.now() + (FACULTY_DISMISS_HOURS * 60 * 60 * 1000);
            localStorage.setItem(FACULTY_NOTICE_KEY, dismissUntil.toString());
        }
    }

    function shouldShowFacultyNotice() {
        const dismissed = localStorage.getItem(FACULTY_NOTICE_KEY);
        if (!dismissed) return true;
        const dismissUntil = parseInt(dismissed, 10);
        return Date.now() > dismissUntil;
    }

    const facultyNotice = document.getElementById('faculty-department-notice');
    if (facultyNotice && !shouldShowFacultyNotice()) {
        facultyNotice.style.display = 'none';
    }

    @if(!$needsFacultyDepartment)
        localStorage.removeItem(FACULTY_NOTICE_KEY);
    @endif

    window.dismissLowSmsWarning = dismissLowSmsWarning;
    window.dismissFacultyDepartmentNotice = dismissFacultyDepartmentNotice;
})();
</script>
@endpush
@endsection
