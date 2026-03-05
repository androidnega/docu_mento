@extends('layouts.dashboard')

@section('title', 'Project Categories')
@section('dashboard_heading', 'Project Categories')

@section('dashboard_content')
<div class="w-full min-w-0 max-w-full space-y-6">
    {{-- Header + create button --}}
    <div class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-4 py-3 sm:px-5 sm:py-4 flex flex-wrap items-center justify-between gap-3 shadow-sm">
        <div class="flex items-center gap-3 min-w-0">
            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-900 text-amber-300">
                <i class="fas fa-folder-tree text-sm"></i>
            </span>
            <div class="min-w-0">
                <h1 class="text-base sm:text-lg font-semibold text-slate-900 dark:text-slate-50">
                    Project categories
                </h1>
                <p class="mt-0.5 text-xs sm:text-sm text-gray-600 dark:text-slate-300">
                    Organize projects into clear buckets for easier reporting and supervision.
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 dark:bg-slate-800 px-2.5 py-1 text-[11px] font-medium text-slate-700 dark:text-slate-100">
                <i class="fas fa-layer-group text-[10px]"></i>
                <span>Total: <span class="tabular-nums">{{ $categories->count() }}</span></span>
            </span>
            <a href="{{ route('dashboard.coordinators.categories.create') }}" class="inline-flex items-center justify-center shrink-0 rounded-full bg-primary-600 px-4 py-2 text-xs sm:text-sm font-semibold text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 focus:ring-offset-white dark:focus:ring-offset-slate-900">
                <i class="fas fa-plus text-[11px] mr-1"></i>
                <span>Create category</span>
            </a>
        </div>
    </div>

    <div class="rounded-2xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[400px] divide-y divide-gray-200 dark:divide-slate-800">
                <thead class="bg-gray-50 dark:bg-slate-900/70">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-slate-300 uppercase">Category</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-slate-300 uppercase">Projects</th>
                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 dark:text-slate-300 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-slate-900 divide-y divide-gray-200 dark:divide-slate-800">
                    @forelse($categories as $category)
                        <tr class="hover:bg-gray-50 dark:hover:bg-slate-800/60">
                            <td class="px-3 py-2 text-sm font-medium text-gray-900 dark:text-slate-50">
                                <span class="inline-flex items-center gap-1.5">
                                    <span class="flex h-7 w-7 items-center justify-center rounded-full bg-slate-900/5 text-slate-600 dark:bg-slate-800 dark:text-slate-200">
                                        <i class="fas fa-tag text-[11px]"></i>
                                    </span>
                                    <span>{{ $category->name }}</span>
                                </span>
                            </td>
                            <td class="px-3 py-2 text-sm text-gray-700 dark:text-slate-200">
                                <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 dark:bg-slate-800 px-2 py-0.5 text-[11px] font-medium text-slate-700 dark:text-slate-100">
                                    <i class="fas fa-diagram-project text-[10px]"></i>
                                    <span>{{ $category->projects_count }} project{{ $category->projects_count === 1 ? '' : 's' }}</span>
                                </span>
                            </td>
                            <td class="px-3 py-2 text-right">
                                <div class="inline-flex items-center gap-2">
                                    <a href="{{ route('dashboard.coordinators.categories.edit', $category) }}" class="inline-flex items-center justify-center rounded-full border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-1.5 text-xs font-medium text-primary-700 dark:text-primary-400 hover:bg-slate-50 dark:hover:bg-slate-800">
                                        <i class="fas fa-pen text-[10px] mr-1"></i>
                                        <span>Edit</span>
                                    </a>
                                    @if($category->projects_count > 0)
                                        <span class="inline-flex items-center gap-1 text-gray-400 dark:text-slate-500 text-xs ml-1" title="Cannot delete: category has {{ $category->projects_count }} project(s)">
                                            <i class="fas fa-lock text-[10px]"></i>
                                            <span>Delete</span>
                                        </span>
                                    @else
                                        <form action="{{ route('dashboard.coordinators.categories.destroy', $category) }}" method="post" class="inline" onsubmit="return confirm('Delete this category?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex items-center justify-center rounded-full border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-100">
                                                <i class="fas fa-trash text-[10px] mr-1"></i>
                                                <span>Delete</span>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-3 py-8 text-center text-gray-500 dark:text-slate-300">No categories yet. Create one to get started.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
