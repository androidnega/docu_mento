@extends('layouts.dashboard')

@section('title', 'Dashboard')
@section('dashboard_heading', 'Dashboard')

@section('dashboard_content')
@php
    $coordinatorName = ($user ?? auth()->user())->name ?? ($user ?? auth()->user())->username ?? 'Coordinator';
@endphp
<div class="w-full space-y-6">
    {{-- Top bar: Academic Year, Welcome Coordinator --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            @if($activeAcademicYear ?? null)
                <p class="text-sm text-slate-500">Academic Year: <strong class="text-slate-700">{{ $activeAcademicYear->year }}</strong></p>
            @else
                <p class="text-sm text-slate-500">Academic Year: <span class="text-amber-600">Not set</span></p>
            @endif
            <p class="text-slate-800 font-medium mt-0.5">Welcome, {{ $coordinatorName }}</p>
        </div>
    </div>

    @if(!($activeAcademicYear ?? null))
    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm font-medium text-amber-800">No active academic year. Students cannot create groups or projects until one is set.</p>
        <a href="{{ route('dashboard.coordinators.academic-years.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-1">
            Create academic year
        </a>
    </div>
    @endif

    {{-- Statistics cards: clean, different colors, Font Awesome, no shadow --}}
    <div>
        <h2 class="text-sm font-semibold text-slate-800 mb-3">Statistics</h2>
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
            <a href="{{ route('dashboard.coordinators.projects.index', $activeAcademicYear ? ['academic_year_id' => $activeAcademicYear->id] : []) }}" class="rounded-xl border border-blue-200 bg-blue-50 p-5 transition-colors hover:bg-blue-100 no-underline flex items-center gap-4">
                <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-100 text-blue-600 text-xl"><i class="fas fa-folder-open"></i></span>
                <div class="min-w-0">
                    <p class="text-xs font-medium text-blue-700 uppercase tracking-wide">Total projects</p>
                    <p class="mt-0.5 text-2xl font-bold tabular-nums text-blue-900">{{ $overview['projects'] ?? 0 }}</p>
                </div>
            </a>
            <a href="{{ route('dashboard.coordinators.projects.index', $activeAcademicYear ? ['academic_year_id' => $activeAcademicYear->id] : []) }}?pending=1" class="rounded-xl border border-amber-200 bg-amber-50 p-5 transition-colors hover:bg-amber-100 no-underline flex items-center gap-4">
                <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-100 text-amber-600 text-xl"><i class="fas fa-clock"></i></span>
                <div class="min-w-0">
                    <p class="text-xs font-medium text-amber-700 uppercase tracking-wide">Pending approval</p>
                    <p class="mt-0.5 text-2xl font-bold tabular-nums text-amber-900">{{ $projectsPendingApprovalCount ?? 0 }}</p>
                </div>
            </a>
            <a href="{{ route('dashboard.coordinators.projects.index', $activeAcademicYear ? ['academic_year_id' => $activeAcademicYear->id] : []) }}" class="rounded-xl border border-emerald-200 bg-emerald-50 p-5 transition-colors hover:bg-emerald-100 no-underline flex items-center gap-4">
                <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600 text-xl"><i class="fas fa-check-circle"></i></span>
                <div class="min-w-0">
                    <p class="text-xs font-medium text-emerald-700 uppercase tracking-wide">Approved</p>
                    <p class="mt-0.5 text-2xl font-bold tabular-nums text-emerald-900">{{ $overview['projects_approved'] ?? 0 }}</p>
                </div>
            </a>
            <a href="{{ route('dashboard.coordinators.projects.index', $activeAcademicYear ? ['academic_year_id' => $activeAcademicYear->id] : []) }}" class="rounded-xl border border-red-200 bg-red-50 p-5 transition-colors hover:bg-red-100 no-underline flex items-center gap-4">
                <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-red-100 text-red-600 text-xl"><i class="fas fa-times-circle"></i></span>
                <div class="min-w-0">
                    <p class="text-xs font-medium text-red-700 uppercase tracking-wide">Rejected</p>
                    <p class="mt-0.5 text-2xl font-bold tabular-nums text-red-900">{{ $rejectedCount ?? 0 }}</p>
                </div>
            </a>
            <a href="{{ route('dashboard.coordinators.groups.index') }}" class="rounded-xl border border-violet-200 bg-violet-50 p-5 transition-colors hover:bg-violet-100 no-underline flex items-center gap-4">
                <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-violet-100 text-violet-600 text-xl"><i class="fas fa-users"></i></span>
                <div class="min-w-0">
                    <p class="text-xs font-medium text-violet-700 uppercase tracking-wide">Active groups</p>
                    <p class="mt-0.5 text-2xl font-bold tabular-nums text-violet-900">{{ $overview['groups'] ?? 0 }}</p>
                </div>
            </a>
        </div>
    </div>

    <details class="rounded-lg border border-gray-200 bg-white shadow-sm group">
        <summary class="flex cursor-pointer items-center justify-between px-4 py-3 text-sm font-medium text-gray-800 hover:bg-gray-50 rounded-t-lg list-none [&::-webkit-details-marker]:hidden">
            <span>Setup &amp; reports</span>
            <svg class="h-5 w-5 text-gray-400 shrink-0 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </summary>
        <div class="border-t border-gray-100 px-4 py-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div class="rounded-lg border border-violet-100 bg-violet-50/80 px-3 py-2.5">
                <p class="text-xs font-medium text-violet-700 uppercase tracking-wide mb-2">Setup</p>
                <div class="flex flex-wrap gap-x-1.5 gap-y-1 text-sm">
                    <a href="{{ route('dashboard.coordinators.academic-years.index') }}" class="text-violet-800 hover:text-violet-900 hover:underline">Academic Years</a>
                    <span class="text-violet-300" aria-hidden="true">·</span>
                    <a href="{{ route('dashboard.coordinators.categories.index') }}" class="text-violet-800 hover:text-violet-900 hover:underline">Categories</a>
                    <span class="text-violet-300" aria-hidden="true">·</span>
                    <a href="{{ route('dashboard.coordinators.groups.index') }}" class="text-violet-800 hover:text-violet-900 hover:underline">Groups</a>
                    <span class="text-violet-300" aria-hidden="true">·</span>
                    <a href="{{ route('dashboard.coordinators.assign-group-leaders.index') }}" class="text-violet-800 hover:text-violet-900 hover:underline">Group Leaders</a>
                    <span class="text-violet-300" aria-hidden="true">·</span>
                    <a href="{{ route('dashboard.coordinators.workload') }}" class="text-violet-800 hover:text-violet-900 hover:underline">Workload</a>
                </div>
            </div>
            <div class="rounded-lg border border-emerald-100 bg-emerald-50/80 px-3 py-2.5">
                <p class="text-xs font-medium text-emerald-700 uppercase tracking-wide mb-2">Reports</p>
                <a href="{{ route('dashboard.coordinators.export-report') }}" class="text-sm text-emerald-800 hover:text-emerald-900 hover:underline font-medium">Export CSV</a>
            </div>
        </div>
    </details>
</div>
@endsection
