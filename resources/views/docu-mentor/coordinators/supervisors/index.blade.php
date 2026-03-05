@extends('layouts.dashboard')

@section('title', 'Supervisors')
@section('dashboard_heading', 'Supervisors')

@section('dashboard_content')
<div class="w-full space-y-6">
    {{-- Header summary --}}
    <div class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-4 py-3 sm:px-5 sm:py-4 flex flex-wrap items-center justify-between gap-3 shadow-sm">
        <div class="flex items-center gap-3 min-w-0">
            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-900 text-amber-300">
                <i class="fas fa-user-tie text-sm"></i>
            </span>
            <div class="min-w-0">
                <h1 class="text-base sm:text-lg font-semibold text-slate-900 dark:text-slate-50">
                    Supervisors
                </h1>
                <p class="mt-0.5 text-xs sm:text-sm text-slate-500 dark:text-slate-300">
                    Upload or add supervisors, then assign them to projects from the project review page.
                </p>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2 sm:gap-3 text-xs">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 dark:bg-slate-800 px-2.5 py-1 font-medium text-slate-700 dark:text-slate-100">
                <i class="fas fa-users text-[11px]"></i>
                <span>Total: <span class="tabular-nums">{{ $supervisors->count() }}</span></span>
            </span>
        </div>
    </div>

    @if(session('success'))<div class="rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-800">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">{{ session('error') }}</div>@endif

    {{-- Upload + Add single --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-800 dark:text-slate-100 mb-2 flex items-center gap-2">
                <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-100">
                    <i class="fas fa-file-upload text-xs"></i>
                </span>
                <span>Upload supervisors (CSV)</span>
            </h2>
            <form action="{{ route('dashboard.coordinators.students.upload') }}" method="post" enctype="multipart/form-data" class="space-y-3">
                @csrf
                <input type="hidden" name="role" value="supervisor">
                <input type="file" name="file" accept=".xlsx,.xls,.csv" required class="block w-full text-sm text-slate-600 dark:text-slate-200 file:mr-2 file:rounded-lg file:border-0 file:bg-primary-50 file:px-3 file:py-2 file:text-primary-700">
                <button type="submit" class="inline-flex items-center gap-1.5 rounded-full bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700">
                    <i class="fas fa-cloud-arrow-up text-xs"></i>
                    <span>Upload CSV</span>
                </button>
            </form>
        </div>
        <div class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-800 dark:text-slate-100 mb-2 flex items-center gap-2">
                <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-emerald-50 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-100">
                    <i class="fas fa-user-plus text-xs"></i>
                </span>
                <span>Add single supervisor</span>
            </h2>
            <form action="{{ route('dashboard.coordinators.students.store') }}" method="post" class="space-y-3">
                @csrf
                <input type="hidden" name="role" value="supervisor">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div class="min-w-0">
                        <label for="index_number" class="block text-xs font-medium text-slate-600 mb-0.5">Index number <span class="text-red-500">*</span></label>
                        <input type="text" name="index_number" id="index_number" required maxlength="64" value="{{ old('index_number') }}" class="block w-full rounded-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-900 dark:text-slate-100">
                    </div>
                    <div class="min-w-0">
                        <label for="name" class="block text-xs font-medium text-slate-600 mb-0.5">Name</label>
                        <input type="text" name="name" id="name" maxlength="255" value="{{ old('name') }}" class="block w-full rounded-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-900 dark:text-slate-100">
                    </div>
                    <div class="min-w-0">
                        <label for="phone" class="block text-xs font-medium text-slate-600 mb-0.5">Phone</label>
                        <input type="text" name="phone" id="phone" maxlength="20" value="{{ old('phone') }}" class="block w-full rounded-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-900 dark:text-slate-100">
                    </div>
                </div>
                <button type="submit" class="inline-flex items-center gap-1.5 rounded-full bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700">
                    <i class="fas fa-user-check text-xs"></i>
                    <span>Add supervisor</span>
                </button>
            </form>
        </div>
    </div>

    {{-- Table: Name | Email | Assigned projects --}}
    <section class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/60 flex items-center justify-between gap-2">
            <h2 class="text-sm font-semibold text-slate-800 dark:text-slate-100">All supervisors</h2>
            <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 dark:bg-slate-800 px-2 py-0.5 text-[11px] font-medium text-slate-600 dark:text-slate-300">
                <i class="fas fa-diagram-project text-[10px]"></i>
                <span>Projects per supervisor</span>
            </span>
        </div>
        @if($supervisors->isEmpty())
            <div class="p-8 text-center text-slate-500">
                <p>No supervisors yet. Upload a CSV or add one above.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                    <thead class="bg-slate-50 dark:bg-slate-900/70">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Name</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Email</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Assigned projects</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800 bg-white dark:bg-slate-900">
                        @foreach($supervisors as $u)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/60">
                                <td class="px-4 py-3 text-sm font-medium text-slate-900 dark:text-slate-50">
                                    <span class="inline-flex items-center gap-1.5">
                                        <i class="fas fa-user-circle text-slate-400"></i>
                                        <span>{{ $u->name ?? '—' }}</span>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">{{ $u->email ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm tabular-nums text-slate-700 dark:text-slate-200">{{ $u->supervised_projects_count ?? 0 }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <p class="text-xs text-slate-500 dark:text-slate-300">Assign or unassign supervisors to projects from <a href="{{ route('dashboard.coordinators.projects.index') }}" class="text-primary-600 hover:underline">Projects</a> → open a project → Review.</p>
</div>
@endsection
