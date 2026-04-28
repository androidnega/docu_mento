@extends('layouts.dashboard')

@section('title', 'Supervisor Dashboard – Docu Mentor')
@section('dashboard_heading', 'Supervisor Dashboard')

@section('dashboard_content')
@php
    $assignedProjectsCount = $projects->count();
    $approvedProjectsCount = $projects->where('approved', true)->count();
    $pendingProjectsCount = $assignedProjectsCount - $approvedProjectsCount;
    $completedProjectsCount = $projects->filter(fn ($project) => $project->completedChaptersCount() >= 6)->count();
@endphp
<div class="max-w-6xl mx-auto w-full pt-4 sm:pt-6">
<h1 class="text-2xl font-bold text-slate-900 mb-2">Supervisor Dashboard</h1>
<p class="text-slate-500 text-sm mb-6">Overview of your supervision workload, progress, and assigned projects.</p>

<section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
    <article class="rounded-xl border border-slate-200 bg-white p-4">
        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Assigned projects</p>
        <p class="mt-2 text-2xl font-bold text-slate-900 tabular-nums">{{ $assignedProjectsCount }}</p>
    </article>
    <article class="rounded-xl border border-slate-200 bg-white p-4">
        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Total students</p>
        <p class="mt-2 text-2xl font-bold text-slate-900 tabular-nums">{{ (int) ($totalStudentsAcrossProjects ?? 0) }}</p>
    </article>
    <article class="rounded-xl border border-slate-200 bg-white p-4">
        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Approved projects</p>
        <p class="mt-2 text-2xl font-bold text-emerald-700 tabular-nums">{{ $approvedProjectsCount }}</p>
    </article>
    <article class="rounded-xl border border-slate-200 bg-white p-4">
        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Pending projects</p>
        <p class="mt-2 text-2xl font-bold text-amber-700 tabular-nums">{{ $pendingProjectsCount }}</p>
    </article>
</section>

<section class="rounded-xl border border-slate-200 bg-white p-4 sm:p-5 mb-6">
    <h2 class="text-sm font-semibold text-slate-900">Performance snapshot</h2>
    <div class="mt-3 grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm">
        <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
            <p class="text-slate-500 text-xs uppercase tracking-wide">Completed (6/6)</p>
            <p class="mt-1 font-semibold text-slate-900 tabular-nums">{{ $completedProjectsCount }} / {{ max(1, $assignedProjectsCount) }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
            <p class="text-slate-500 text-xs uppercase tracking-wide">Tagged projects</p>
            <p class="mt-1 font-semibold text-slate-900 tabular-nums">{{ $projects->whereNotNull('parent_project_id')->count() }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
            <p class="text-slate-500 text-xs uppercase tracking-wide">Pending reviews view</p>
            <p class="mt-1 font-semibold text-slate-900">{{ request()->boolean('pending') ? 'Active' : 'Inactive' }}</p>
        </div>
    </div>
</section>

@if($projects->isEmpty())
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-12 text-center">
        <p class="text-slate-600">No projects assigned to you yet.</p>
        <p class="text-slate-500 text-sm mt-2">A coordinator can assign you as a supervisor to projects.</p>
    </div>
@else
    <section>
        <div class="flex items-center justify-between gap-3 mb-3">
            <h2 class="text-sm font-semibold text-slate-900">Project portfolio</h2>
            <span class="text-xs text-slate-500">{{ $assignedProjectsCount }} project{{ $assignedProjectsCount === 1 ? '' : 's' }}</span>
        </div>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        @foreach($projects as $project)
            @php
                $chaptersDone = $project->completedChaptersCount();
                $memberCount = $project->group ? $project->groupMembersVisibleToStaff()->count() : 0;
            @endphp
            <a href="{{ route('dashboard.docu-mentor.projects.show', $project) }}" class="block bg-white rounded-xl border border-slate-200 p-5 hover:border-indigo-300 transition">
                <h2 class="text-base font-semibold text-slate-900">{{ $project->title }}</h2>
                <p class="text-sm text-slate-500 mt-1">Group: {{ $project->group?->name }} · {{ $project->academicYear?->year ?? '—' }}</p>
                @if($project->description)
                    <p class="text-sm text-slate-600 mt-2">{{ Str::limit($project->description, 120) }}</p>
                @endif
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <span class="px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800">
                        Progress: {{ $chaptersDone }}/6 completed
                    </span>
                    <span class="px-2 py-0.5 rounded text-xs {{ $project->approved ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                        {{ $project->approved ? 'Approved' : 'Pending' }}
                    </span>
                    <span class="px-2 py-0.5 rounded text-xs font-medium bg-sky-100 text-sky-900" title="Students listed for supervisors (profile complete)">{{ $memberCount }} student{{ $memberCount === 1 ? '' : 's' }}</span>
                    @if($project->parent_project_id)
                        <span class="px-2 py-0.5 rounded text-xs bg-slate-100 text-slate-700" title="Tagged to previous project">Tagged</span>
                    @endif
                </div>
            </a>
        @endforeach
    </div>
    </section>
@endif

</div>
@endsection
