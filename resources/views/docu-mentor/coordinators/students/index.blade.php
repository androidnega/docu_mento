@extends('layouts.dashboard')

@section('title', 'Students')
@section('dashboard_heading', 'Students')

@section('dashboard_content')
<div class="w-full space-y-6">
    {{-- Header summary --}}
    <div class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-4 py-3 sm:px-5 sm:py-4 flex flex-wrap items-center justify-between gap-3 shadow-sm">
        <div class="min-w-0">
            <h1 class="text-base sm:text-lg font-semibold text-slate-900 dark:text-slate-50">
                Students — {{ $coordinatorDepartmentName ?? 'Department' }}
            </h1>
            <p class="mt-0.5 text-xs sm:text-sm text-slate-500 dark:text-slate-300">
                Choose an academic year to view and manage its students.
            </p>
        </div>
        @if(!empty($stats))
            <div class="flex flex-wrap items-center gap-2 sm:gap-3 text-xs">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 dark:bg-slate-800 px-2.5 py-1 font-medium text-slate-700 dark:text-slate-100">
                    <i class="fas fa-users text-[11px]"></i>
                    <span>Total records: <span class="tabular-nums">{{ $stats['total'] ?? 0 }}</span></span>
                </span>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 dark:bg-emerald-900/40 px-2.5 py-1 font-medium text-emerald-700 dark:text-emerald-100">
                    <i class="fas fa-phone text-[11px]"></i>
                    <span>With phone: <span class="tabular-nums">{{ $stats['with_phone'] ?? 0 }}</span></span>
                </span>
            </div>
        @endif
    </div>

    @if(empty($academicYearCards) || $academicYearCards->isEmpty())
        <div class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-8 text-center shadow-sm">
            <p class="text-sm text-slate-700 dark:text-slate-100">No academic years in your department yet.</p>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-300">
                Set up at least one academic year before assigning students.
            </p>
            <a href="{{ route('dashboard.coordinators.academic-years.index') }}" class="mt-4 inline-flex items-center gap-1.5 rounded-full bg-amber-600 px-4 py-2 text-xs sm:text-sm font-semibold text-white hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 focus:ring-offset-slate-50 dark:focus:ring-offset-slate-900">
                <i class="fas fa-calendar-plus text-[11px]"></i>
                <span>Manage academic years</span>
            </a>
        </div>
    @else
        @php
            $cardPalettes = [
                ['border' => 'border-sky-200', 'icon' => 'bg-sky-50 text-sky-700', 'chip' => 'bg-sky-100 text-sky-700'],
                ['border' => 'border-emerald-200', 'icon' => 'bg-emerald-50 text-emerald-700', 'chip' => 'bg-emerald-100 text-emerald-700'],
                ['border' => 'border-violet-200', 'icon' => 'bg-violet-50 text-violet-700', 'chip' => 'bg-violet-100 text-violet-700'],
                ['border' => 'border-amber-200', 'icon' => 'bg-amber-50 text-amber-700', 'chip' => 'bg-amber-100 text-amber-700'],
                ['border' => 'border-rose-200', 'icon' => 'bg-rose-50 text-rose-700', 'chip' => 'bg-rose-100 text-rose-700'],
            ];
            $cardIcons = ['fa-graduation-cap', 'fa-user-graduate', 'fa-users', 'fa-book-reader', 'fa-school'];
        @endphp
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3 sm:gap-4">
            @foreach($academicYearCards as $i => $card)
                @php
                    $palette = $cardPalettes[$i % count($cardPalettes)];
                    $icon = $cardIcons[$i % count($cardIcons)];
                @endphp
                <a href="{{ route('dashboard.coordinators.academic-years.students', $card->id) }}"
                   class="rounded-2xl border {{ $palette['border'] }} bg-white dark:bg-slate-900 px-4 py-3 sm:px-4 sm:py-3.5 flex flex-col gap-2.5 shadow-[0_1px_2px_rgba(15,23,42,0.06)] hover:shadow-md transition-shadow no-underline">
                    <div class="flex items-center gap-3">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl {{ $palette['icon'] }}">
                            <i class="fas {{ $icon }} text-sm"></i>
                        </span>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-slate-900 dark:text-slate-50">
                                {{ $card->year }}
                            </p>
                            <p class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-300">
                                {{ $card->students_count }} student{{ $card->students_count !== 1 ? 's' : '' }}
                                @if($card->supervisors_count > 0)
                                    · {{ $card->supervisors_count }} supervisor{{ $card->supervisors_count !== 1 ? 's' : '' }}
                                @endif
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center justify-between gap-2 text-[11px]">
                        <span class="inline-flex items-center gap-1 rounded-full {{ $palette['chip'] }} px-2 py-0.5 font-medium">
                            <i class="fas fa-users text-[10px]"></i>
                            <span>View students</span>
                        </span>
                        <span class="inline-flex items-center gap-1 text-slate-500 dark:text-slate-300">
                            <span class="font-medium">Open</span>
                            <i class="fas fa-arrow-right text-[10px]"></i>
                        </span>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</div>
@endsection
