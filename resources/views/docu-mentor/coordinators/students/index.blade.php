@extends('layouts.dashboard')

@section('title', 'Students')
@section('dashboard_heading', 'Students')

@section('dashboard_content')
<div class="w-full space-y-6">
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm p-4">
        <h1 class="text-lg font-semibold text-slate-900">Students</h1>
        <p class="text-sm text-slate-500 mt-0.5">Select an academic year to upload and manage students for that year.</p>
    </div>

    @if(empty($academicYearCards) || $academicYearCards->isEmpty())
        <div class="rounded-xl border border-slate-200 bg-white p-8 text-center shadow-sm">
            <p class="text-slate-600">No academic years in your department yet.</p>
            <a href="{{ route('dashboard.coordinators.academic-years.index') }}" class="mt-3 inline-block text-sm font-medium text-primary-600 hover:underline">Manage academic years</a>
        </div>
    @else
        @php
            $cardColors = [
                'bg-blue-500 hover:bg-blue-600 border-blue-600 text-white',
                'bg-emerald-500 hover:bg-emerald-600 border-emerald-600 text-white',
                'bg-violet-500 hover:bg-violet-600 border-violet-600 text-white',
                'bg-amber-500 hover:bg-amber-600 border-amber-600 text-white',
                'bg-rose-500 hover:bg-rose-600 border-rose-600 text-white',
                'bg-sky-500 hover:bg-sky-600 border-sky-600 text-white',
                'bg-teal-500 hover:bg-teal-600 border-teal-600 text-white',
                'bg-indigo-500 hover:bg-indigo-600 border-indigo-600 text-white',
            ];
            $cardIcons = ['fa-graduation-cap', 'fa-user-graduate', 'fa-users', 'fa-book-reader', 'fa-school', 'fa-chalkboard-teacher', 'fa-id-card', 'fa-calendar-alt'];
        @endphp
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            @foreach($academicYearCards as $i => $card)
                @php
                    $colorClass = $cardColors[$i % count($cardColors)];
                    $icon = $cardIcons[$i % count($cardIcons)];
                @endphp
                <a href="{{ route('dashboard.coordinators.academic-years.students', $card->id) }}" class="flex items-start gap-4 rounded-xl border-2 p-6 transition-colors no-underline {{ $colorClass }}">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white/20 text-2xl"><i class="fas {{ $icon }}"></i></span>
                    <div class="min-w-0 flex-1">
                        <p class="text-2xl font-bold tracking-tight">{{ $card->year }}</p>
                        <p class="mt-2 text-sm opacity-90">{{ $card->students_count }} student{{ $card->students_count !== 1 ? 's' : '' }}</p>
                        <p class="mt-3 text-sm font-medium opacity-95">Open <i class="fas fa-arrow-right ml-0.5 text-xs"></i></p>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</div>
@endsection
