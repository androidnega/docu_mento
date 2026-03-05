@extends('layouts.dashboard')

@section('title', 'Project Groups')
@section('dashboard_heading', 'Project Groups')

@php
    $groupNameEmojis = [
        'Ghost API'   => '👻 ✨',
        'Eish Branch' => '🌿 🌱',
        'Wossop Git'  => '🦊 💻',
        'Demure SQL'  => '🗄️ 📊',
    ];
@endphp

@section('dashboard_content')
<div class="w-full min-w-0 max-w-full space-y-6">
    {{-- Header summary --}}
    <div class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-4 py-3 sm:px-5 sm:py-4 flex flex-wrap items-center justify-between gap-3 shadow-sm">
        <div class="flex items-center gap-3 min-w-0">
            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-900 text-amber-300">
                <i class="fas fa-people-group text-sm"></i>
            </span>
            <div class="min-w-0">
                <h1 class="text-base sm:text-lg font-semibold text-slate-900 dark:text-slate-50">
                    Project groups
                </h1>
                <p class="mt-0.5 text-xs sm:text-sm text-slate-500 dark:text-slate-300">
                    Each group links students, a leader, and a Docu Mentor project.
                </p>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2 sm:gap-3 text-xs">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 dark:bg-slate-800 px-2.5 py-1 font-medium text-slate-700 dark:text-slate-100">
                <i class="fas fa-users text-[11px]"></i>
                <span>Total groups: <span class="tabular-nums">{{ $groups->count() }}</span></span>
            </span>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/60">
            <h2 class="text-sm font-semibold text-slate-800 dark:text-slate-100">Groups overview</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[500px] divide-y divide-gray-200 dark:divide-slate-800">
                <thead class="bg-gray-50 dark:bg-slate-900/70">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-slate-300 uppercase">Name</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-slate-300 uppercase">Token</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-slate-300 uppercase">Academic Year</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-slate-300 uppercase">Leader</th>
                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 dark:text-slate-300 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-slate-900 divide-y divide-gray-200 dark:divide-slate-800">
                    @forelse($groups as $g)
                        <tr class="hover:bg-gray-50 dark:hover:bg-slate-800/60">
                            <td class="px-3 py-2 text-sm font-medium text-gray-900 dark:text-slate-50">
                                @php $emojis = $groupNameEmojis[trim($g->name)] ?? '🚀'; @endphp
                                <span class="inline-flex items-center gap-1 mr-1">{{ $emojis }}</span>
                                <span>{{ $g->name }}</span>
                            </td>
                            <td class="px-3 py-2 text-sm font-mono text-primary-600 dark:text-primary-400">{{ $g->token ?? '—' }}</td>
                            <td class="px-3 py-2 text-sm text-gray-600 dark:text-slate-300">{{ $g->academicYear?->year ?? '—' }}</td>
                            <td class="px-3 py-2 text-sm text-gray-600 dark:text-slate-300">
                                <span class="inline-flex items-center gap-1.5">
                                    <i class="fas fa-user-circle text-slate-400"></i>
                                    <span>{{ $g->leader?->name ?? $g->leader?->username ?? '—' }}</span>
                                </span>
                            </td>
                            <td class="px-3 py-2 text-right">
                                <div class="inline-flex items-center gap-2">
                                    <a href="{{ route('dashboard.coordinators.groups.show', $g) }}"
                                       class="inline-flex items-center justify-center rounded-full border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-1.5 text-xs font-medium text-primary-700 dark:text-primary-400 hover:bg-slate-50 dark:hover:bg-slate-800">
                                        <i class="fas fa-eye text-[10px] mr-1"></i>
                                        <span>View</span>
                                    </a>
                                    @can('delete', $g)
                                    <form action="{{ route('dashboard.coordinators.groups.destroy', $g) }}" method="post" onsubmit="return confirm('{{ $g->project ? 'Delete this group and its project? This cannot be undone.' : 'Delete this group?' }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center justify-center rounded-full border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-100">
                                            <i class="fas fa-trash text-[10px] mr-1"></i>
                                            <span>Delete</span>
                                        </button>
                                    </form>
                                    @else
                                    @if($g->project)
                                    <span class="text-gray-400 dark:text-slate-500 text-xs ml-1" title="Only Super Admin can delete groups that have a project. Enable in Settings → General if you are coordinator.">
                                        <i class="fas fa-lock text-[10px] mr-0.5"></i>Delete
                                    </span>
                                    @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-3 py-8 text-center text-gray-500 dark:text-slate-300">No groups yet. Create one to get started.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
