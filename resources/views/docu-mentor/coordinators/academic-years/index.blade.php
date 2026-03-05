@extends('layouts.dashboard')

@section('title', 'Academic Years')
@section('dashboard_heading', 'Academic Years')

@section('dashboard_content')
<div class="w-full min-w-0 max-w-full space-y-6">
    {{-- Header strip with quick context --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-col gap-1 min-w-0">
            <p class="text-xs sm:text-sm font-semibold text-slate-900 dark:text-slate-100">
                Academic years in your department
            </p>
            <p class="text-xs text-slate-500 dark:text-slate-300">
                {{ $years->count() }} year{{ $years->count() === 1 ? '' : 's' }} configured ·
                @php $activeYear = $years->firstWhere('is_active', true); @endphp
                @if($activeYear)
                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 text-emerald-700 px-2 py-0.5 text-[11px] font-medium">
                        <i class="fas fa-circle-check text-[9px]"></i>
                        <span>Active: {{ $activeYear->year }}</span>
                    </span>
                @else
                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 text-amber-700 px-2 py-0.5 text-[11px] font-medium">
                        <i class="fas fa-exclamation-circle text-[9px]"></i>
                        <span>No active year</span>
                    </span>
                @endif
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('dashboard.coordinators.academic-years.create') }}"
               class="inline-flex items-center gap-1.5 rounded-full bg-amber-600 px-4 py-2 text-xs sm:text-sm font-semibold text-white shadow-sm hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 focus:ring-offset-white dark:focus:ring-offset-slate-900">
                <i class="fas fa-plus-circle text-xs"></i>
                <span>Add academic year</span>
            </a>
        </div>
    </div>

    {{-- Stacked year cards (column, not grid) --}}
    @if($years->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/40 px-4 py-8 text-center">
            <p class="text-sm font-medium text-slate-700 dark:text-slate-100">No academic years yet.</p>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-300">
                Create the first academic year to unlock project creation and deadlines.
            </p>
            <div class="mt-4">
                <a href="{{ route('dashboard.coordinators.academic-years.create') }}"
                   class="inline-flex items-center gap-1.5 rounded-full bg-amber-600 px-4 py-2 text-xs sm:text-sm font-semibold text-white shadow-sm hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 focus:ring-offset-slate-50 dark:focus:ring-offset-slate-900">
                    <i class="fas fa-calendar-plus text-xs"></i>
                    <span>Create academic year</span>
                </a>
            </div>
        </div>
    @else
        <div class="space-y-2.5 sm:space-y-3">
            @foreach($years as $year)
                @php
                    $deadlineText = $year->submission_deadline
                        ? $year->submission_deadline->format('d M Y')
                        : 'Sept 30 (default)';
                    $isActive = (bool) $year->is_active;
                @endphp
                <article class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-4 py-2.5 sm:px-4 sm:py-3 flex flex-wrap items-center justify-between gap-2.5 shadow-[0_1px_2px_rgba(15,23,42,0.06)]">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <span class="flex h-8 w-8 sm:h-9 sm:w-9 items-center justify-center rounded-xl bg-slate-900 text-amber-300 text-xs sm:text-sm">
                            <i class="fas fa-calendar-alt"></i>
                        </span>
                        <div class="min-w-0">
                            <p class="text-sm sm:text-base font-semibold text-slate-900 dark:text-slate-50">
                                {{ $year->year }}
                            </p>
                            <p class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-300">
                                Submission deadline:
                                <span class="font-medium text-slate-800 dark:text-slate-100">{{ $deadlineText }}</span>
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3 sm:gap-4">
                        <div class="flex flex-col items-start sm:items-end text-xs">
                            @if($isActive)
                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 text-emerald-700 px-2.5 py-1 font-medium">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                    Active year
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 text-slate-600 px-2.5 py-1 font-medium">
                                    <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                                    Inactive
                                </span>
                            @endif
                            @if(!$year->submission_deadline)
                                <span class="mt-1 text-[11px] text-slate-400 dark:text-slate-400">
                                    Uses default Sept 30 deadline
                                </span>
                            @endif
                        </div>

                        <div class="flex items-center gap-1.5 sm:gap-2">
                            <a href="{{ route('dashboard.coordinators.academic-years.edit', $year) }}"
                               class="inline-flex items-center justify-center rounded-full border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-1.5 text-xs font-medium text-slate-700 dark:text-slate-100 hover:bg-slate-50 dark:hover:bg-slate-800">
                                <i class="fas fa-pen text-[10px] mr-1"></i>
                                <span>Edit</span>
                            </a>
                            <form action="{{ route('dashboard.coordinators.academic-years.destroy', $year) }}"
                                  method="post"
                                  onsubmit="return confirm('Delete this academic year?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="inline-flex items-center justify-center rounded-full border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-100">
                                    <i class="fas fa-trash text-[10px] mr-1"></i>
                                    <span>Delete</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</div>
@endsection
