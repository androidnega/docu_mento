@extends('layouts.dashboard')

@section('title', 'Export Report')
@section('dashboard_heading', 'Export Report')

@section('dashboard_content')
<div class="w-full min-w-0 max-w-full space-y-6">
    @if(session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">{{ session('error') }}</div>
    @endif

    {{-- Header --}}
    <div class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-4 py-3 sm:px-5 sm:py-4 flex flex-wrap items-center justify-between gap-3 shadow-sm">
        <div class="flex items-center gap-3 min-w-0">
            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-900 text-amber-300">
                <i class="fas fa-file-export text-sm"></i>
            </span>
            <div class="min-w-0">
                <h1 class="text-base sm:text-lg font-semibold text-slate-900 dark:text-slate-50">
                    Export project report
                </h1>
                <p class="mt-0.5 text-xs sm:text-sm text-gray-600 dark:text-slate-300">
                    Download a single file with projects, students, supervisors and scores for an academic year.
                </p>
            </div>
        </div>
    </div>

    {{-- Export form --}}
    <div class="rounded-2xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-5 sm:p-6 max-w-xl shadow-sm">
        <div class="grid gap-3 mb-4 text-xs sm:text-sm text-gray-600 dark:text-slate-300">
            <div class="flex items-center gap-2">
                <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-100 text-[11px]">
                    <i class="fas fa-table"></i>
                </span>
                <span>Columns: Project, Student, Phone, Supervisor(s), Doc score, System score, Final score, Academic year.</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-emerald-50 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-100 text-[11px]">
                    <i class="fas fa-lock-open"></i>
                </span>
                <span>File contains only projects you can see as coordinator.</span>
            </div>
        </div>

        <form action="{{ route('dashboard.coordinators.export-report.download') }}" method="get" class="space-y-4">
            <div>
                <label for="academic_year" class="block text-sm font-medium text-gray-700 dark:text-slate-100 mb-2">
                    Select academic year
                </label>
                <select name="academic_year" id="academic_year" required class="w-full rounded-md border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-sm text-slate-900 dark:text-slate-100">
                    <option value="">— Select —</option>
                    @foreach($academicYears as $ay)
                        <option value="{{ $ay->id }}" {{ (\App\Models\DocuMentor\AcademicYear::active()?->id ?? null) == $ay->id ? 'selected' : '' }}>
                            {{ $ay->year }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="submit" name="format" value="csv" class="inline-flex items-center gap-1.5 rounded-full bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700">
                    <i class="fas fa-file-csv text-xs"></i>
                    <span>Download CSV</span>
                </button>
                <button type="submit" name="format" value="xlsx" class="inline-flex items-center gap-1.5 rounded-full bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                    <i class="fas fa-file-excel text-xs"></i>
                    <span>Download Excel</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
