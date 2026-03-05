@extends('layouts.student-dashboard')

@section('title', 'Dashboard')
@php $dashboardTitle = 'Dashboard'; @endphp

@section('dashboard_content')
<header class="mb-6 sm:mb-8">
    <h1 class="text-xl sm:text-2xl font-semibold text-gray-900 dark:text-slate-50">Your dashboard</h1>
    @php
        $fallbackName = $displayName ?? ($user->name ?? $user->username ?? 'Student');
    @endphp
    @if(!empty($greeting ?? null))
        <p class="mt-1 text-sm text-gray-700 dark:text-slate-200">
            {{ $greeting }}
            @if($isGroupLeader ?? false)
                <span class="text-amber-600 font-semibold">(Leader)</span>
            @endif
        </p>
    @else
        <p class="mt-1 text-sm text-gray-700 dark:text-slate-200">
            Good day, {{ $fallbackName }}
            @if($isGroupLeader ?? false)
                <span class="text-amber-600 font-semibold">(Leader)</span>
            @endif
        </p>
    @endif
    @if(!empty($holidayBadge['message'] ?? null))
        <div class="mt-2 inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs sm:text-sm {{ $holidayBadge['bg'] ?? 'bg-emerald-50' }} {{ $holidayBadge['text'] ?? 'text-emerald-800' }}">
            @if(!empty($holidayBadge['icon'] ?? null))
                <i class="{{ $holidayBadge['icon'] }} text-xs"></i>
            @endif
            <span class="font-medium">{{ $holidayBadge['message'] }}</span>
        </div>
    @endif
    @if(isset($student) && $student)
        <p class="mt-1.5 text-xs text-gray-400 dark:text-slate-400 font-mono">Index: {{ $student->index_number }}</p>
    @endif
</header>

{{-- Simple, constant dashboard cards. Detailed project/group info lives on their own pages. --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 gap-4 sm:gap-5">
    {{-- ACTIVE CARDS FIRST --}}

    {{-- Projects workspace (active) --}}
    @if($hasProjectAccess ?? false)
    <a href="{{ route('dashboard.projects.index') }}" class="block rounded-lg border border-sky-700 bg-sky-600 px-5 py-5 no-underline text-left text-white">
        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-800 text-white mb-3">
            <i class="fas fa-folder-open text-lg"></i>
        </span>
        <h2 class="text-sm font-semibold">Projects workspace</h2>
        <p class="text-xs mt-1 text-slate-200">View topics, submissions and feedback.</p>
    </a>
    @endif

    {{-- My group (active) --}}
    @if($docuMentorGroup ?? null)
    <a href="{{ route('dashboard.group.show', ['group' => $docuMentorGroup->id]) }}" class="block rounded-lg border border-amber-500 bg-amber-400 px-5 py-5 no-underline text-left text-slate-900">
        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-500 text-slate-900 mb-3">
            <i class="fas fa-users text-lg"></i>
        </span>
        <h2 class="text-sm font-semibold">My group</h2>
        <p class="text-xs mt-1 truncate text-amber-900">{{ $docuMentorGroup->name }}</p>
    </a>
    @endif

    {{-- Create group (leader without group) --}}
    @if($leaderWithoutGroup ?? false)
    <a href="{{ route('dashboard.group.create') }}" class="block rounded-lg border border-emerald-700 bg-emerald-600 px-5 py-5 no-underline text-left text-white">
        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-700 text-white mb-3">
            <i class="fas fa-user-plus text-lg"></i>
        </span>
        <h2 class="text-sm font-semibold">Create group</h2>
        <p class="text-xs mt-1 text-emerald-100">Start your project group.</p>
    </a>
    @endif

    {{-- Register project (leader with group, no project yet) --}}
    @if(($isGroupLeader ?? false) && !($leaderWithoutGroup ?? false) && !($leaderHasProject ?? false))
    <a href="{{ route('dashboard.projects.create') }}" class="block rounded-lg border border-indigo-700 bg-indigo-600 px-5 py-5 no-underline text-left text-white">
        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-700 text-white mb-3">
            <i class="fas fa-plus-circle text-lg"></i>
        </span>
        <h2 class="text-sm font-semibold">Register project</h2>
        <p class="text-xs mt-1 text-indigo-100">Propose your group’s topic.</p>
    </a>
    @endif

    {{-- DISABLED CARDS (AT THE END) --}}

    {{-- Projects workspace (disabled) --}}
    @if(!($hasProjectAccess ?? false))
    <div class="rounded-lg border border-gray-300 bg-gray-50 px-5 py-5 text-left text-gray-400">
        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-gray-100 text-gray-400 mb-3">
            <i class="fas fa-folder-open text-lg"></i>
        </span>
        <h2 class="text-sm font-semibold text-gray-400">Projects workspace</h2>
        <p class="text-xs mt-1">Join a group or be set as leader to access.</p>
    </div>
    @endif

    {{-- My group (disabled) --}}
    @if(!($docuMentorGroup ?? null))
    <div class="rounded-lg border border-gray-300 bg-gray-50 px-5 py-5 text-left text-gray-400">
        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-gray-100 text-gray-400 mb-3">
            <i class="fas fa-users text-lg"></i>
        </span>
        <h2 class="text-sm font-semibold text-gray-400">My group</h2>
        <p class="text-xs mt-1">Join a group to see it here.</p>
    </div>
    @endif
</div>

{{-- Progress / overview: only when group + project exist --}}
@if(($docuMentorGroup ?? null) && ($leaderProject ?? null))
<section class="mt-6">
    <h2 class="text-sm font-semibold text-slate-800 mb-3">Project overview</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Project status --}}
        @php
            $statusLabel = $leaderProject->status === 'rejected'
                ? 'Rejected'
                : ($leaderProject->approved ? 'Approved' : 'Pending approval');
        @endphp
        <div class="rounded-lg border border-sky-100 bg-sky-50 px-4 py-4 text-sm">
            <div class="flex items-center gap-2 mb-2">
                <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-sky-100 text-sky-700 text-xs">
                    <i class="fas fa-info-circle"></i>
                </span>
                <span class="font-semibold text-slate-800">Project status</span>
            </div>
            <p class="text-slate-700">{{ $leaderProject->title }}</p>
            <p class="mt-1 text-xs font-medium
                @if($statusLabel === 'Approved') text-emerald-700
                @elseif($statusLabel === 'Rejected') text-red-700
                @else text-amber-700 @endif">
                {{ $statusLabel }}
            </p>
            @if($leaderProject->category)
                <p class="mt-1 text-xs text-slate-500">Category: {{ $leaderProject->category->name }}</p>
            @endif
        </div>

        {{-- Progress overview --}}
        @php
            $totalMilestones = 3;
            $milestonesDone = 0;
            $hasProposal = $leaderProject->proposals->isNotEmpty();
            $chaptersCompleted = $leaderProject->completedChaptersCount();
            $isFinalDone = $leaderProject->isFullyCompleted() || in_array($leaderProject->status, ['completed','graded']);
            if ($hasProposal) $milestonesDone++;
            if ($chaptersCompleted > 0) $milestonesDone++;
            if ($isFinalDone) $milestonesDone++;

            if (!$hasProposal) {
                $stageText = 'No proposal submitted yet';
            } elseif (!$chaptersCompleted) {
                $stageText = 'Proposal submitted · chapters not started';
            } elseif (!$isFinalDone) {
                $stageText = 'Chapters in progress';
            } else {
                $stageText = 'All milestones completed';
            }
        @endphp
        <div class="rounded-lg border border-emerald-100 bg-emerald-50 px-4 py-4 text-sm">
            <div class="flex items-center gap-2 mb-2">
                <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 text-xs">
                    <i class="fas fa-flag-checkered"></i>
                </span>
                <span class="font-semibold text-slate-800">Progress overview</span>
            </div>
            <p class="text-slate-700">You have completed {{ $milestonesDone }}/{{ $totalMilestones }} milestones.</p>
            <p class="mt-1 text-xs text-slate-500">{{ $stageText }}</p>
        </div>

        {{-- Supervisor feedback (if any) --}}
        @php
            $latestFeedback = $leaderProject->proposals
                ->filter(fn ($p) => !empty($p->coordinator_comment))
                ->sortByDesc('uploaded_at')
                ->first();
        @endphp
        @if($leaderProject->supervisors->isNotEmpty() || $latestFeedback)
        <div class="rounded-lg border border-violet-100 bg-violet-50 px-4 py-4 text-sm">
            <div class="flex items-center gap-2 mb-2">
                <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-violet-100 text-violet-700 text-xs">
                    <i class="fas fa-comments"></i>
                </span>
                <span class="font-semibold text-slate-800">Supervisor feedback</span>
            </div>
            @if($leaderProject->supervisors->isNotEmpty())
                <p class="text-xs text-slate-600 mb-1">
                    Supervisor: {{ $leaderProject->supervisors->map(fn($u) => $u->name ?? $u->username)->implode(', ') }}
                </p>
            @endif
            @if($latestFeedback)
                <p class="text-xs text-slate-700">
                    Latest comment:
                    {{ \Illuminate\Support\Str::limit($latestFeedback->coordinator_comment, 80) }}
                </p>
            @else
                <p class="text-xs text-slate-500">No comments yet.</p>
            @endif
        </div>
        @endif

        {{-- Deadlines --}}
        <div class="rounded-lg border border-amber-100 bg-amber-50 px-4 py-4 text-sm">
            <div class="flex items-center gap-2 mb-2">
                <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-amber-100 text-amber-700 text-xs">
                    <i class="fas fa-calendar-alt"></i>
                </span>
                <span class="font-semibold text-slate-800">Deadlines</span>
            </div>
            @php
                $finalDeadline = $projectDeadline;
            @endphp
            <p class="text-xs text-slate-600">
                Proposal: <span class="font-medium text-slate-800">—</span><br>
                Draft: <span class="font-medium text-slate-800">—</span><br>
                Final: <span class="font-medium text-slate-800">
                    {{ $finalDeadline ? \Carbon\Carbon::parse($finalDeadline)->format('d M Y') : '—' }}
                </span>
            </p>
        </div>
    </div>
</section>
@endif

{{-- Next steps / privileges: no access, or not leader --}}
@if(!($hasProjectAccess ?? false))
<div class="mt-5 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-xs text-slate-600">
    <p class="font-medium text-slate-700">Next steps</p>
    <ul class="mt-1 list-disc list-inside space-y-0.5">
        <li>Ask your coordinator to add you to a group, or</li>
        <li>Ask your coordinator to set you as a group leader so you can create a group and register a project.</li>
    </ul>
</div>
@elseif($leaderWithoutGroup ?? false)
<div class="mt-5 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-800">
    <p class="font-medium">You’re set as a group leader. Create your group to get started, then register your project.</p>
</div>
@elseif(($isGroupLeader ?? false) && !($leaderHasProject ?? false) && ($docuMentorGroup ?? null))
<div class="mt-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs text-emerald-800">
    <p class="font-medium">Your group is ready. Create a project to propose your topic and get supervisor assignment.</p>
</div>
@elseif(!($isGroupLeader ?? false))
<p class="mt-5 text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-4 py-3 max-w-2xl">
    Only students set as group leaders by your coordinator can create a group or register a project. Ask your coordinator if you need to be assigned as a leader.
</p>
@endif
@endsection
