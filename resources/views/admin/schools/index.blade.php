@extends('layouts.dashboard')

@section('title', 'Schools')
@section('admin_heading', 'Schools')

@section('dashboard_content')
<div class="w-full space-y-6">
    <div class="flex items-center justify-between gap-4 flex-wrap">
        <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-slate-300">
            <a href="{{ route('dashboard') }}" class="hover:text-primary-600 dark:hover:text-primary-400">Dashboard</a>
            <svg class="w-4 h-4 text-gray-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="text-gray-900 dark:text-slate-50 font-medium">Schools</span>
        </div>
        <a href="{{ route('dashboard.schools.create') }}" class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium text-white bg-primary-600 hover:bg-primary-700">Add school</a>
    </div>

    <p class="text-gray-600 dark:text-slate-300">To <strong>add or remove departments</strong>, click <strong>Edit</strong> next to a school below. Assign staff to departments via <a href="{{ route('dashboard.users.index') }}" class="text-primary-600 hover:underline dark:text-primary-400">User management</a>.</p>

    @if(session('success'))
        <div class="rounded-lg bg-green-50 dark:bg-green-900/40 border border-green-200 dark:border-green-600 text-green-800 dark:text-green-100 px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-lg bg-red-50 dark:bg-red-900/40 border border-red-200 dark:border-red-600 text-red-800 dark:text-red-100 px-4 py-3 text-sm">{{ session('error') }}</div>
    @endif

    <div class="rounded-lg border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[520px] text-left">
                <thead class="bg-gray-50 dark:bg-slate-900/80 border-b border-gray-200 dark:border-slate-700">
                    <tr>
                        <th class="px-4 py-3 text-sm font-semibold text-gray-900 dark:text-slate-100">School</th>
                        <th class="px-4 py-3 text-sm font-semibold text-gray-900 dark:text-slate-100">Departments</th>
                        <th class="px-4 py-3 text-sm font-semibold text-gray-900 dark:text-slate-100">Status</th>
                        <th class="px-4 py-3 text-sm font-semibold text-gray-900 dark:text-slate-100 w-24">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($schools as $school)
                    <tr class="border-b border-gray-100 dark:border-slate-800 last:border-0 hover:bg-gray-50/50 dark:hover:bg-slate-800/70">
                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-slate-50 break-words">{{ $school->name }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-slate-300">{{ $school->departments_count }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-slate-200">{{ $school->is_active ?? true ? 'Active' : 'Inactive' }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('dashboard.schools.edit', $school) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium bg-primary-100 dark:bg-primary-900/40 text-primary-800 dark:text-primary-200 hover:bg-primary-200 dark:hover:bg-primary-800/60" title="Edit school and add/remove departments">Edit &amp; add departments</a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="px-4 py-6 text-sm text-gray-500 dark:text-slate-400">No schools yet. <a href="{{ route('dashboard.schools.create') }}" class="text-primary-600 dark:text-primary-400 hover:underline">Add one</a>.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
