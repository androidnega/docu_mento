@extends('layouts.dashboard')

@section('title', 'Dashboard')
@section('dashboard_heading', 'Dashboard')

@section('dashboard_content')
@php
    $coordinatorName = ($user ?? auth()->user())->name ?? ($user ?? auth()->user())->username ?? 'Coordinator';
@endphp
<div class="w-full space-y-6">
    {{-- Top bar: quick context + primary shortcuts (no welcome text block) --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2 text-xs sm:text-sm text-slate-500">
            @if($activeAcademicYear ?? null)
                <span class="inline-flex items-center gap-1 rounded-full bg-slate-900 text-amber-300 px-2.5 py-1 text-[11px] sm:text-xs font-medium">
                    <i class="fas fa-calendar-alt text-[10px]"></i>
                    <span>{{ $activeAcademicYear->year }}</span>
                </span>
            @else
                <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 text-amber-700 px-2.5 py-1 text-[11px] sm:text-xs font-medium">
                    <i class="fas fa-calendar-times text-[10px]"></i>
                    <span>Academic year not set</span>
                </span>
            @endif
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('dashboard.coordinators.academic-years.index') }}"
               class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs sm:text-sm font-medium text-slate-700 hover:bg-slate-50">
                <i class="fas fa-calendar-days text-[11px] sm:text-xs text-slate-500"></i>
                <span>Academic years</span>
            </a>
            <a href="{{ route('dashboard.coordinators.students.index') }}"
               class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs sm:text-sm font-medium text-slate-700 hover:bg-slate-50">
                <i class="fas fa-user-graduate text-[11px] sm:text-xs text-slate-500"></i>
                <span>Students</span>
            </a>
        </div>
    </div>

    @if(!($activeAcademicYear ?? null))
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 sm:px-5 sm:py-4 flex flex-wrap items-center justify-between gap-3">
            <p class="text-xs sm:text-sm font-medium text-amber-800">
                No active academic year. Students cannot create groups or projects until one is set.
            </p>
            <a href="{{ route('dashboard.coordinators.academic-years.create') }}"
               class="inline-flex items-center gap-2 rounded-full bg-amber-600 px-4 py-2 text-xs sm:text-sm font-semibold text-white shadow-sm hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-1">
                <i class="fas fa-plus-circle text-xs"></i>
                <span>Create academic year</span>
            </a>
        </div>
    @endif

    {{-- Command bar: search + quick actions --}}
    <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 sm:px-5 sm:py-4 shadow-sm flex flex-wrap items-center gap-3 sm:gap-4">
        <form class="flex-1 min-w-[200px]" onsubmit="return false;">
            <label for="coordinator-dashboard-search" class="sr-only">Search on this dashboard</label>
            <div class="flex items-center gap-2">
                <div class="relative flex-1">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2.5 sm:pl-3 text-slate-400">
                        <i class="fas fa-magnifying-glass text-xs sm:text-sm"></i>
                    </span>
                    <input
                        id="coordinator-dashboard-search"
                        type="search"
                        autocomplete="off"
                        placeholder="Filter approvals by project, group, category or year"
                        class="block w-full rounded-xl border border-slate-200 bg-slate-50 pl-8 sm:pl-9 pr-3 py-2 text-xs sm:text-sm text-slate-900 placeholder:text-slate-400 focus:bg-white focus:border-amber-500 focus:ring-2 focus:ring-amber-500/50"
                        data-dashboard-search-input="true"
                    >
                </div>
                <button
                    type="button"
                    class="inline-flex items-center justify-center h-9 w-9 rounded-xl bg-amber-600 text-white text-xs hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 focus:ring-offset-white"
                    title="Search approvals"
                    data-dashboard-search-trigger="true"
                >
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </form>
        <div class="flex items-center flex-wrap gap-2 sm:gap-2.5">
            <a href="{{ route('dashboard.coordinators.projects.index', ($activeAcademicYear ? ['academic_year_id' => $activeAcademicYear->id] : []) + ['pending' => 1]) }}"
               class="inline-flex items-center gap-1.5 rounded-full border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs sm:text-sm font-medium text-amber-900 hover:bg-amber-100">
                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-amber-100 text-amber-700">
                    <i class="fas fa-inbox text-[11px]"></i>
                </span>
                <span>Pending approvals</span>
                <span class="ml-1 rounded-full bg-amber-900/10 px-1.5 py-0.5 text-[11px] font-semibold tabular-nums">
                    {{ $projectsPendingApprovalCount ?? 0 }}
                </span>
            </a>
            <a href="{{ route('dashboard.coordinators.workload') }}"
               class="inline-flex items-center justify-center h-8 w-8 rounded-full border border-sky-100 bg-sky-50 text-sky-600 hover:bg-sky-100"
               title="Supervisor workload">
                <i class="fas fa-chart-area text-xs"></i>
            </a>
            <a href="{{ route('dashboard.coordinators.export-report') }}"
               class="inline-flex items-center justify-center h-8 w-8 rounded-full border border-emerald-100 bg-emerald-50 text-emerald-600 hover:bg-emerald-100"
               title="Export CSV report">
                <i class="fas fa-file-download text-xs"></i>
            </a>
        </div>
    </div>

    {{-- Key metrics (cards in a responsive grid) --}}
    <div>
        <h2 class="text-xs sm:text-sm font-semibold text-slate-800 mb-2 sm:mb-3">Department overview</h2>
        <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-3 sm:gap-4">
            <a href="{{ route('dashboard.coordinators.projects.index', $activeAcademicYear ? ['academic_year_id' => $activeAcademicYear->id] : []) }}"
               class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-5 no-underline flex items-center justify-between gap-3 shadow-sm hover:shadow-md transition-shadow">
                <div class="min-w-0">
                    <p class="text-[11px] sm:text-xs font-medium uppercase tracking-wide text-slate-500">Projects this year</p>
                    <p class="mt-1 text-xl sm:text-2xl font-bold tabular-nums text-slate-900">
                        {{ $overview['projects'] ?? 0 }}
                    </p>
                    @if($activeAcademicYear ?? null)
                        <p class="mt-0.5 text-[11px] text-slate-400">Academic year {{ $activeAcademicYear->year }}</p>
                    @endif
                </div>
                <span class="flex h-10 w-10 sm:h-11 sm:w-11 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                    <i class="fas fa-diagram-project text-sm sm:text-base"></i>
                </span>
            </a>

            <a href="{{ route('dashboard.coordinators.projects.index', ($activeAcademicYear ? ['academic_year_id' => $activeAcademicYear->id] : []) + ['pending' => 1]) }}"
               class="rounded-2xl border border-amber-200 bg-amber-50/80 p-4 sm:p-5 no-underline flex items-center justify-between gap-3 shadow-sm hover:shadow-md transition-shadow">
                <div class="min-w-0">
                    <p class="text-[11px] sm:text-xs font-medium uppercase tracking-wide text-amber-700">Pending approvals</p>
                    <p class="mt-1 text-xl sm:text-2xl font-bold tabular-nums text-amber-900">
                        {{ $projectsPendingApprovalCount ?? 0 }}
                    </p>
                    <p class="mt-0.5 text-[11px] text-amber-800/80">Waiting for coordinator decision</p>
                </div>
                <span class="flex h-10 w-10 sm:h-11 sm:w-11 items-center justify-center rounded-xl bg-amber-100 text-amber-700">
                    <i class="fas fa-clock-rotate-left text-sm sm:text-base"></i>
                </span>
            </a>

            <a href="{{ route('dashboard.coordinators.projects.index', $activeAcademicYear ? ['academic_year_id' => $activeAcademicYear->id] : []) }}"
               class="rounded-2xl border border-emerald-200 bg-emerald-50/80 p-4 sm:p-5 no-underline flex items-center justify-between gap-3 shadow-sm hover:shadow-md transition-shadow">
                <div class="min-w-0">
                    <p class="text-[11px] sm:text-xs font-medium uppercase tracking-wide text-emerald-700">Approved projects</p>
                    <p class="mt-1 text-xl sm:text-2xl font-bold tabular-nums text-emerald-900">
                        {{ $overview['projects_approved'] ?? 0 }}
                    </p>
                    <p class="mt-0.5 text-[11px] text-emerald-700/80">Ready for supervision</p>
                </div>
                <span class="flex h-10 w-10 sm:h-11 sm:w-11 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">
                    <i class="fas fa-circle-check text-sm sm:text-base"></i>
                </span>
            </a>

            <a href="{{ route('dashboard.coordinators.groups.index') }}"
               class="rounded-2xl border border-sky-200 bg-sky-50/80 p-4 sm:p-5 no-underline flex items-center justify-between gap-3 shadow-sm hover:shadow-md transition-shadow">
                <div class="min-w-0">
                    <p class="text-[11px] sm:text-xs font-medium uppercase tracking-wide text-sky-700">Active groups</p>
                    <p class="mt-1 text-xl sm:text-2xl font-bold tabular-nums text-sky-900">
                        {{ $overview['groups'] ?? 0 }}
                    </p>
                    <p class="mt-0.5 text-[11px] text-sky-700/80">With at least one project or member</p>
                </div>
                <span class="flex h-10 w-10 sm:h-11 sm:w-11 items-center justify-center rounded-xl bg-sky-100 text-sky-700">
                    <i class="fas fa-people-group text-sm sm:text-base"></i>
                </span>
            </a>

            <a href="{{ route('dashboard.coordinators.students.index') }}"
               class="rounded-2xl border border-violet-200 bg-violet-50/80 p-4 sm:p-5 no-underline flex items-center justify-between gap-3 shadow-sm hover:shadow-md transition-shadow">
                <div class="min-w-0">
                    <p class="text-[11px] sm:text-xs font-medium uppercase tracking-wide text-violet-700">Students in scope</p>
                    <p class="mt-1 text-xl sm:text-2xl font-bold tabular-nums text-violet-900">
                        {{ $overview['students'] ?? 0 }}
                    </p>
                    <p class="mt-0.5 text-[11px] text-violet-700/80">Department students in Docu Mentor</p>
                </div>
                <span class="flex h-10 w-10 sm:h-11 sm:w-11 items-center justify-center rounded-xl bg-violet-100 text-violet-700">
                    <i class="fas fa-user-graduate text-sm sm:text-base"></i>
                </span>
            </a>

            <a href="{{ route('dashboard.coordinators.supervisors.index') }}"
               class="rounded-2xl border border-slate-200 bg-slate-50 p-4 sm:p-5 no-underline flex items-center justify-between gap-3 shadow-sm hover:shadow-md transition-shadow">
                <div class="min-w-0">
                    <p class="text-[11px] sm:text-xs font-medium uppercase tracking-wide text-slate-700">Supervisors</p>
                    <p class="mt-1 text-xl sm:text-2xl font-bold tabular-nums text-slate-900">
                        {{ $overview['supervisors'] ?? 0 }}
                    </p>
                    <p class="mt-0.5 text-[11px] text-slate-500">Available for project assignments</p>
                </div>
                <span class="flex h-10 w-10 sm:h-11 sm:w-11 items-center justify-center rounded-xl bg-slate-900 text-amber-400">
                    <i class="fas fa-user-tie text-sm sm:text-base"></i>
                </span>
            </a>
        </div>
    </div>

    {{-- Main content: approvals, pipeline and deadlines --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-5">
        {{-- Pending approvals list --}}
        <section class="lg:col-span-2 rounded-2xl border border-slate-200 bg-white shadow-sm p-4 sm:p-5">
            <div class="flex flex-wrap items-center justify-between gap-2 sm:gap-3">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">Next approvals</h2>
                    <p class="mt-0.5 text-xs sm:text-sm text-slate-500">
                        Most recent projects waiting for your approval.
                    </p>
                </div>
                <a href="{{ route('dashboard.coordinators.projects.index', ($activeAcademicYear ? ['academic_year_id' => $activeAcademicYear->id] : []) + ['pending' => 1]) }}"
                   class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 px-3 py-1.5 text-xs sm:text-sm font-medium text-slate-700 hover:bg-slate-50">
                    <span>View all</span>
                    <i class="fas fa-arrow-up-right-from-square text-[11px]"></i>
                </a>
            </div>

            @if(($projectsPendingApproval ?? collect())->isEmpty())
                <p class="mt-4 text-xs sm:text-sm text-slate-500">
                    There are no projects waiting for approval right now.
                </p>
            @else
                <div class="mt-4 -mx-2 overflow-x-auto">
                    <table class="min-w-full text-left text-xs sm:text-sm">
                        <thead class="border-b border-slate-100 bg-slate-50/80">
                            <tr>
                                <th class="px-2 sm:px-3 py-2 font-semibold text-slate-600 whitespace-nowrap">Project</th>
                                <th class="px-2 sm:px-3 py-2 font-semibold text-slate-600 whitespace-nowrap">Group</th>
                                <th class="hidden md:table-cell px-2 sm:px-3 py-2 font-semibold text-slate-600 whitespace-nowrap">Category</th>
                                <th class="hidden sm:table-cell px-2 sm:px-3 py-2 font-semibold text-slate-600 whitespace-nowrap">Year</th>
                                <th class="px-2 sm:px-3 py-2 font-semibold text-right text-slate-600 whitespace-nowrap">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($projectsPendingApproval as $project)
                                <tr
                                    class="hover:bg-slate-50/80"
                                    data-dashboard-search-row="true"
                                    data-dashboard-search-text="{{ strtolower($project->title . ' ' . ($project->group->name ?? '') . ' ' . ($project->category->name ?? '') . ' ' . ($project->academicYear->year ?? '')) }}"
                                >
                                    <td class="px-2 sm:px-3 py-2 align-top max-w-xs">
                                        <div class="flex flex-col gap-0.5">
                                            <span class="font-medium text-slate-900 truncate" title="{{ $project->title }}">
                                                {{ \Illuminate\Support\Str::limit($project->title, 60) }}
                                            </span>
                                            <span class="text-[11px] text-slate-500">
                                                {{ $project->created_at?->format('d M Y') ?? '—' }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-2 sm:px-3 py-2 align-top whitespace-nowrap text-slate-700">
                                        {{ $project->group?->name ?? '—' }}
                                    </td>
                                    <td class="hidden md:table-cell px-2 sm:px-3 py-2 align-top whitespace-nowrap text-slate-700">
                                        {{ $project->category?->name ?? '—' }}
                                    </td>
                                    <td class="hidden sm:table-cell px-2 sm:px-3 py-2 align-top whitespace-nowrap text-slate-700">
                                        {{ $project->academicYear?->year ?? '—' }}
                                    </td>
                                    <td class="px-2 sm:px-3 py-2 align-top text-right">
                                        <div class="inline-flex items-center gap-1.5">
                                            <a href="{{ route('dashboard.coordinators.projects.show', $project) }}"
                                               class="inline-flex items-center justify-center rounded-full border border-slate-200 bg-white px-2 py-1 text-[11px] font-medium text-slate-700 hover:bg-slate-50">
                                                <i class="fas fa-eye mr-1 text-[10px]"></i>
                                                <span>Open</span>
                                            </a>
                                            <form action="{{ route('dashboard.coordinators.projects.approve', $project) }}" method="post">
                                                @csrf
                                                <button type="submit"
                                                        class="inline-flex items-center justify-center rounded-full bg-emerald-600 px-2.5 py-1 text-[11px] font-semibold text-white hover:bg-emerald-700">
                                                    <i class="fas fa-check mr-1 text-[10px]"></i>
                                                    <span>Approve</span>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="mt-3 text-[11px] text-slate-500">
                    Showing up to 10 most recent pending projects.
                </p>
            @endif
        </section>

        {{-- Right column: pipeline + deadlines + trend --}}
        <section class="space-y-4 sm:space-y-5">
            {{-- Pipeline --}}
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm p-4 sm:p-5">
                <div class="flex items-center justify-between gap-2">
                    <h2 class="text-sm font-semibold text-slate-900">Project pipeline</h2>
                    <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                        Live
                    </span>
                </div>
                @php
                    $pipelineStatuses = [
                        \App\Models\DocuMentor\Project::STATUS_SUBMITTED => ['label' => 'Submitted', 'color' => 'text-sky-700', 'bg' => 'bg-sky-50'],
                        \App\Models\DocuMentor\Project::STATUS_IN_PROGRESS => ['label' => 'In progress', 'color' => 'text-amber-700', 'bg' => 'bg-amber-50'],
                        \App\Models\DocuMentor\Project::STATUS_COMPLETED => ['label' => 'Completed', 'color' => 'text-emerald-700', 'bg' => 'bg-emerald-50'],
                        \App\Models\DocuMentor\Project::STATUS_GRADED => ['label' => 'Graded', 'color' => 'text-violet-700', 'bg' => 'bg-violet-50'],
                        \App\Models\DocuMentor\Project::STATUS_REJECTED => ['label' => 'Rejected', 'color' => 'text-rose-700', 'bg' => 'bg-rose-50'],
                    ];
                @endphp
                <div class="mt-3 grid grid-cols-2 gap-2 sm:gap-3">
                    @foreach($pipelineStatuses as $statusKey => $meta)
                        @php $count = (int) ($statsPerStatus[$statusKey] ?? 0); @endphp
                        <div class="rounded-xl border border-slate-100 {{ $meta['bg'] }} px-3 py-2.5 flex flex-col gap-1">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-[11px] font-medium uppercase tracking-wide {{ $meta['color'] }}">
                                    {{ $meta['label'] }}
                                </p>
                                <span class="text-[11px] font-semibold text-slate-500 tabular-nums">
                                    {{ $count }}
                                </span>
                            </div>
                            <div class="h-1.5 w-full rounded-full bg-white/70 overflow-hidden">
                                @php
                                    $totalForBar = max(1, array_sum($statsPerStatus));
                                    $percent = (int) round(($count / $totalForBar) * 100);
                                @endphp
                                <div class="h-1.5 rounded-full @if($statusKey === \App\Models\DocuMentor\Project::STATUS_REJECTED) bg-rose-500 @elseif($statusKey === \App\Models\DocuMentor\Project::STATUS_COMPLETED) bg-emerald-500 @elseif($statusKey === \App\Models\DocuMentor\Project::STATUS_GRADED) bg-violet-500 @elseif($statusKey === \App\Models\DocuMentor\Project::STATUS_IN_PROGRESS) bg-amber-500 @else bg-sky-500 @endif"
                                     style="width: {{ $percent }}%;"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Deadlines --}}
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm p-4 sm:p-5">
                <div class="flex items-center justify-between gap-2">
                    <h2 class="text-sm font-semibold text-slate-900">Deadlines</h2>
                    @if($activeAcademicYear ?? null)
                        <span class="text-[11px] text-slate-500">Year {{ $activeAcademicYear->year }}</span>
                    @endif
                </div>
                @php
                    $deadlineList = $deadlinesForYear['list'] ?? collect();
                    $effectiveDeadline = $deadlinesForYear['effective'] ?? null;
                @endphp
                @if(!$activeAcademicYear)
                    <p class="mt-3 text-xs text-slate-500">
                        Set an active academic year to start configuring project deadlines.
                    </p>
                @elseif($deadlineList->isEmpty() && !$effectiveDeadline)
                    <p class="mt-3 text-xs text-slate-500">
                        No deadlines have been configured for this academic year yet.
                    </p>
                @else
                    <ul class="mt-3 space-y-2">
                        @if($effectiveDeadline)
                            <li class="flex items-center justify-between gap-2 rounded-xl bg-amber-50 border border-amber-100 px-3 py-2.5">
                                <div class="flex items-center gap-2 min-w-0">
                                    <span class="flex h-7 w-7 items-center justify-center rounded-full bg-amber-100 text-amber-700">
                                        <i class="fas fa-flag-checkered text-xs"></i>
                                    </span>
                                    <div class="min-w-0">
                                        <p class="text-xs font-semibold text-amber-900">Final submission</p>
                                        <p class="text-[11px] text-amber-800/80">
                                            {{ \Carbon\Carbon::parse($effectiveDeadline)->format('d M Y') }}
                                        </p>
                                    </div>
                                </div>
                            </li>
                        @endif
                        @foreach($deadlineList as $deadline)
                            <li class="flex items-center justify-between gap-2 rounded-xl bg-slate-50 border border-slate-100 px-3 py-2.5">
                                <div class="min-w-0">
                                    <p class="text-xs font-medium text-slate-900 truncate">
                                        {{ $deadline->name ?? 'Milestone' }}
                                    </p>
                                    <p class="text-[11px] text-slate-500">
                                        {{ $deadline->deadline_date?->format('d M Y') ?? '—' }}
                                    </p>
                                </div>
                                <span class="text-[11px] font-medium text-slate-500 whitespace-nowrap">
                                    {{ ucfirst($deadline->type ?? 'project') }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
                <a href="{{ route('dashboard.coordinators.academic-years.edit', $activeAcademicYear ?? ($academicYears->first() ?? null)) }}"
                   class="mt-3 inline-flex items-center gap-1.5 text-[11px] font-medium text-amber-700 hover:text-amber-800 @if(!($activeAcademicYear ?? null)) pointer-events-none opacity-60 @endif">
                    <i class="fas fa-pen-to-square text-[10px]"></i>
                    <span>Manage deadlines</span>
                </a>
            </div>

            {{-- Activity bubble trend --}}
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm p-4 sm:p-5 relative" data-activity-bubbles="true">
                <div class="flex items-center justify-between gap-2 mb-3">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">Student activity trend</h2>
                        <p class="mt-0.5 text-[11px] text-slate-500">New projects and chapter submissions (last 7 days).</p>
                    </div>
                </div>
                @php
                    $trendPoints = $activityTrend['points'] ?? [];
                    $trendMax = max(1, $activityTrend['max'] ?? 1);
                @endphp
                @if(empty($trendPoints))
                    <p class="text-xs text-slate-500">No recent activity recorded yet.</p>
                @else
                    <style>
                        @keyframes dm-bubble-float {
                            0%, 100% { transform: translateY(0); }
                            50% { transform: translateY(-6px); }
                        }
                    </style>
                    <div class="flex items-end gap-2 sm:gap-3 h-32">
                        @foreach($trendPoints as $index => $point)
                            @php
                                $value = (int) ($point['value'] ?? 0);
                                $ratio = $trendMax > 0 ? $value / $trendMax : 0;
                                $sizeRem = 1.4 + ($ratio * 2.1);
                                $sizeRem = max(1.4, min($sizeRem, 3.1));
                                $offset = (int) round(($ratio - 0.5) * 10);
                                $bubblePalettes = [
                                    'bg-sky-50 text-sky-700 border-sky-100',
                                    'bg-emerald-50 text-emerald-700 border-emerald-100',
                                    'bg-violet-50 text-violet-700 border-violet-100',
                                    'bg-amber-50 text-amber-700 border-amber-100',
                                    'bg-rose-50 text-rose-700 border-rose-100',
                                ];
                                $paletteClass = $bubblePalettes[$index % count($bubblePalettes)];
                            @endphp
                            <div class="flex-1 flex flex-col items-center justify-end gap-1"
                                 data-activity-bubble="true"
                                 data-label="{{ $point['label'] }}"
                                 data-date="{{ $point['date'] }}"
                                 data-value="{{ $value }}">
                                <div class="flex items-end justify-center h-24">
                                    <span class="inline-flex items-center justify-center rounded-full border shadow-sm dm-bubble-floating {{ $paletteClass }}"
                                          style="width: {{ $sizeRem }}rem; height: {{ $sizeRem }}rem; animation: dm-bubble-float 6s ease-in-out infinite; animation-delay: {{ $index * 0.35 }}s; transform: translateY({{ $offset }}px);">
                                        <span class="text-[11px] font-semibold tabular-nums">{{ $value }}</span>
                                    </span>
                                </div>
                                <p class="text-[10px] font-medium text-slate-600 leading-tight">{{ $point['label'] }}</p>
                                <p class="text-[9px] text-slate-400 leading-tight">{{ $point['date'] }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var input = document.querySelector('[data-dashboard-search-input]');
    var trigger = document.querySelector('[data-dashboard-search-trigger]');
    var rows = [].slice.call(document.querySelectorAll('[data-dashboard-search-row]'));
    if (!input || !rows.length) return;

    function normalize(text) {
        return (text || '').toString().toLowerCase();
    }

    function handleSearch() {
        var term = normalize(input.value);
        var parts = term.split(/\s+/).filter(function (t) { return t.length > 0; });
        var visibleCount = 0;
        rows.forEach(function (row) {
            var haystack = normalize(row.getAttribute('data-dashboard-search-text') || '');
            var match = !parts.length || parts.every(function (p) { return haystack.indexOf(p) !== -1; });
            row.style.display = match ? '' : 'none';
            if (match) visibleCount++;
        });
        input.setAttribute('data-result-count', String(visibleCount));
    }

    input.addEventListener('input', handleSearch);
    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            handleSearch();
        }
    });
    if (trigger) {
        trigger.addEventListener('click', function (e) {
            e.preventDefault();
            handleSearch();
        });
    }
})();

(function () {
    var card = document.querySelector('[data-activity-bubbles]');
    if (!card) return;

    var bubbles = [].slice.call(card.querySelectorAll('[data-activity-bubble]'));
    if (!bubbles.length) return;

    var tooltip = document.createElement('div');
    tooltip.className = 'absolute z-20 px-3 py-2 rounded-lg bg-slate-900 text-white text-[11px] shadow-lg pointer-events-none hidden';
    tooltip.style.minWidth = '120px';
    card.appendChild(tooltip);

    function showTooltip(bubble, event) {
        var label = bubble.getAttribute('data-label') || '';
        var date = bubble.getAttribute('data-date') || '';
        var value = bubble.getAttribute('data-value') || '0';
        tooltip.innerHTML = '<div class="font-semibold mb-0.5">' + label + ' · ' + date + '</div><div class="text-[10px] text-slate-200">Submissions + projects: <span class="font-semibold">' + value + '</span></div>';
        tooltip.classList.remove('hidden');

        var rect = card.getBoundingClientRect();
        var bubbleRect = bubble.getBoundingClientRect();
        var top = bubbleRect.top - rect.top - 8;
        var left = bubbleRect.left - rect.left;
        tooltip.style.top = top + 'px';
        tooltip.style.left = (left - tooltip.offsetWidth / 2 + bubbleRect.width / 2) + 'px';
    }

    function hideTooltip() {
        tooltip.classList.add('hidden');
    }

    bubbles.forEach(function (bubble) {
        bubble.addEventListener('mouseenter', function (e) {
            showTooltip(bubble, e);
        });
        bubble.addEventListener('mouseleave', hideTooltip);
    });
})();
</script>
@endpush
