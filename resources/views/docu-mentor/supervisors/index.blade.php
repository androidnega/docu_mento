@extends('layouts.dashboard')

@section('title', 'Supervisors')
@section('dashboard_heading', 'Supervisors')

@section('dashboard_content')
<div class="w-full min-w-0 max-w-full space-y-6">
    <div class="rounded-xl border border-slate-200 bg-white p-5">
        <h1 class="text-xl font-semibold text-slate-900">Supervisor Directory</h1>
        <p class="mt-1 text-sm text-slate-500">
            All supervisors can view this directory, including assigned student and project totals.
        </p>
    </div>

    @if($supervisors->isEmpty())
        <div class="rounded-xl border border-slate-200 bg-white p-8 text-center text-slate-500">
            No supervisors found.
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            @foreach($supervisors as $supervisor)
                @php
                    $isCurrent = isset($user) && $user && (int) $user->id === (int) $supervisor->id;
                    $displayName = trim((string) ($supervisor->name ?? '')) !== '' ? $supervisor->name : ($supervisor->username ?? '—');
                @endphp
                <article class="rounded-xl border border-slate-200 bg-white p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h2 class="truncate text-base font-semibold text-slate-900">
                                {{ $displayName }}
                            </h2>
                            <p class="mt-0.5 truncate text-xs text-slate-500">{{ $supervisor->username ?? '—' }}</p>
                        </div>
                        @if($isCurrent)
                            <span class="inline-flex items-center rounded-full border border-amber-300 bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-800">You</span>
                        @endif
                    </div>

                    <dl class="mt-4 grid grid-cols-2 gap-3">
                        <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                            <dt class="text-[11px] uppercase tracking-wide text-slate-500">Students</dt>
                            <dd class="mt-1 text-lg font-semibold text-slate-900 tabular-nums">{{ (int) ($supervisor->allocated_students_count ?? 0) }}</dd>
                        </div>
                        <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                            <dt class="text-[11px] uppercase tracking-wide text-slate-500">Projects</dt>
                            <dd class="mt-1 text-lg font-semibold text-slate-900 tabular-nums">{{ (int) ($supervisor->allocated_projects_count ?? 0) }}</dd>
                        </div>
                    </dl>

                    <p class="mt-3 text-xs text-slate-500">
                        Phone: {{ $supervisor->phone ?: '—' }}
                    </p>
                </article>
            @endforeach
        </div>
    @endif
</div>
@endsection
