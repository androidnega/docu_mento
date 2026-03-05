@extends('layouts.dashboard')

@section('title', 'Student details')
@section('dashboard_heading', 'Student details')

@section('dashboard_content')
<div class="w-full space-y-6">
    @if(session('success'))
        <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800">{{ session('success') }}</div>
    @endif

    <a href="{{ route('dashboard.coordinators.students.index') }}" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-gray-700">
        <i class="fas fa-arrow-left"></i> Back to students
    </a>

    <div class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-sm overflow-hidden">
        <div class="px-5 sm:px-6 py-4 border-b border-gray-100 dark:border-slate-800 bg-gray-50/80 dark:bg-slate-900/60 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-900 text-amber-300">
                    <i class="fas fa-user-graduate text-sm"></i>
                </span>
                <div class="min-w-0">
                    <h2 class="text-sm sm:text-base font-semibold text-gray-900 dark:text-slate-50">
                        {{ $displayName ?: 'Student details' }}
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-slate-300">
                        Index: <span class="font-mono">{{ $indexNumber }}</span>
                    </p>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <form action="{{ route('dashboard.coordinators.students.toggle-leader', ['encodedIndex' => $encodedIndex]) }}" method="post" class="inline">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-full border {{ ($isGroupLeader ?? false) ? 'border-amber-200 bg-amber-50 text-amber-800 hover:bg-amber-100' : 'border-slate-300 bg-white text-gray-700 hover:bg-slate-50' }} px-3 py-1.5 text-xs sm:text-sm font-medium focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1">
                        <i class="fas {{ ($isGroupLeader ?? false) ? 'fa-user-minus' : 'fa-crown' }} text-xs"></i>
                        <span>{{ ($isGroupLeader ?? false) ? 'Remove leader' : 'Set as leader' }}</span>
                    </button>
                </form>
                <a href="{{ route('dashboard.coordinators.students.edit', ['encodedIndex' => $encodedIndex]) }}" class="inline-flex items-center justify-center h-9 w-9 rounded-full border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-gray-600 dark:text-slate-200 hover:bg-gray-50 dark:hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1" title="Edit" aria-label="Edit">
                    <i class="fas fa-pen text-sm"></i>
                </a>
                <form action="{{ route('dashboard.coordinators.students.destroy', ['encodedIndex' => $encodedIndex]) }}" method="post" class="inline" onsubmit="return confirm('Remove this student?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center justify-center h-9 w-9 rounded-full border border-red-200 bg-red-50 text-red-700 hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-1" title="Remove" aria-label="Remove">
                        <i class="fas fa-trash text-sm"></i>
                    </button>
                </form>
            </div>
        </div>

        <div class="px-5 sm:px-6 py-5 space-y-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-5">
                <div class="rounded-xl bg-gray-50/70 dark:bg-slate-800/70 p-4">
                    <p class="text-xs font-medium text-gray-500 dark:text-slate-300 uppercase tracking-wide mb-1">Index number</p>
                    <p class="text-sm font-mono font-semibold text-gray-900 dark:text-slate-50">{{ $indexNumber }}</p>
                </div>
                <div class="rounded-xl bg-gray-50/70 dark:bg-slate-800/70 p-4">
                    <p class="text-xs font-medium text-gray-500 dark:text-slate-300 uppercase tracking-wide mb-1">Name</p>
                    <p class="text-sm font-medium text-gray-900 dark:text-slate-50">{{ $displayName ?: '—' }}</p>
                </div>
                <div class="rounded-xl bg-gray-50/70 dark:bg-slate-800/70 p-4">
                    <p class="text-xs font-medium text-gray-500 dark:text-slate-300 uppercase tracking-wide mb-1">Phone number</p>
                    <p class="text-sm text-gray-900 dark:text-slate-50">{{ $phone ?: '—' }}</p>
                </div>
                <div class="rounded-xl bg-gray-50/70 dark:bg-slate-800/70 p-4">
                    <p class="text-xs font-medium text-gray-500 dark:text-slate-300 uppercase tracking-wide mb-1">Group leader</p>
                    @if($isGroupLeader ?? false)
                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 text-amber-800 px-2.5 py-0.5 text-xs font-medium">
                            <i class="fas fa-crown text-[11px]"></i>
                            <span>Leader</span>
                        </span>
                    @else
                        <span class="text-sm text-gray-600 dark:text-slate-300">No</span>
                    @endif
                </div>
                @if($institution || $faculty || $department)
                <div class="sm:col-span-2 rounded-xl bg-gray-50/70 dark:bg-slate-800/70 p-4">
                    <p class="text-xs font-medium text-gray-500 dark:text-slate-300 uppercase tracking-wide mb-1">Institution / Faculty / Department</p>
                    <p class="text-sm text-gray-900 dark:text-slate-50">{{ implode(' · ', array_filter([$institution, $faculty, $department])) ?: '—' }}</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
