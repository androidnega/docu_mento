@extends('layouts.dashboard')

@section('title', 'Dashboard')
@section('dashboard_heading', 'Dashboard')

@section('dashboard_content')
<div class="w-full min-w-0 space-y-4 sm:space-y-6">
    <div class="min-w-0">
        <p class="text-sm sm:text-base text-slate-500 dark:text-slate-300">Users, schools, and system settings</p>
    </div>

    {{-- Update mode: structured card, no gradients --}}
    <section class="rounded-lg border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-sm px-4 py-3 min-w-0 overflow-hidden {{ ($update_mode ?? false) ? 'border-green-200 bg-green-50 dark:border-green-500/60 dark:bg-green-900/40' : '' }}">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div class="min-w-0 flex-1 flex items-center gap-2 flex-wrap">
                <h2 class="text-xs font-semibold {{ ($update_mode ?? false) ? 'text-green-900 dark:text-green-200' : 'text-gray-900 dark:text-slate-100' }}">Update mode</h2>
                <span class="text-xs font-medium px-1.5 py-0.5 rounded {{ ($update_mode ?? false) ? 'bg-green-100 text-green-800 dark:bg-green-900/60 dark:text-green-200' : 'bg-gray-200 text-gray-700 dark:bg-slate-700 dark:text-slate-100' }}">{{ ($update_mode ?? false) ? 'ON' : 'OFF' }}</span>
                @if(($update_mode ?? false) && ($update_estimated_end ?? null))
                    <span class="text-xs text-green-900 dark:text-green-100 font-semibold tabular-nums shrink-0 overflow-hidden" style="max-width:100%">Time left: <span id="update-mode-countdown">--:--</span></span>
                @endif
                <span class="text-xs {{ ($update_mode ?? false) ? 'text-green-800 dark:text-green-200' : 'text-gray-600 dark:text-slate-300' }}">Only staff at <code class="px-0.5 rounded {{ ($update_mode ?? false) ? 'bg-green-100 dark:bg-green-900/60' : 'bg-gray-200 dark:bg-slate-700' }}">/login</code></span>
            </div>
            <div class="flex items-center gap-1.5 flex-shrink-0">
                @if($update_mode ?? false)
                    <form method="post" action="{{ route('dashboard.settings.update-estimated-end') }}" class="flex items-center gap-1.5">
                        @csrf
                        <label class="sr-only">Estimated end</label>
                        <input type="datetime-local" name="estimated_end" value="{{ $update_estimated_end ? \Carbon\Carbon::parse($update_estimated_end)->format('Y-m-d\TH:i') : '' }}" class="text-xs rounded border border-green-300 px-1.5 py-0.5 min-w-0 w-36" />
                    <button type="submit" class="text-xs font-medium text-green-800 dark:text-green-200 py-0.5">Save</button>
                    </form>
                @endif
                <form method="post" action="{{ route('dashboard.settings.update-mode') }}" class="inline">
                    @csrf
                    <button type="submit" class="relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent focus:outline-none focus:ring-1 focus:ring-offset-0 {{ ($update_mode ?? false) ? 'bg-green-500 focus:ring-green-400' : 'bg-gray-300 dark:bg-slate-600 focus:ring-gray-400 dark:focus:ring-slate-500' }}" role="switch" aria-checked="{{ ($update_mode ?? false) ? 'true' : 'false' }}">
                        <span class="pointer-events-none inline-block h-4 w-4 rounded-full bg-white dark:bg-slate-100 shadow {{ ($update_mode ?? false) ? 'translate-x-4' : 'translate-x-0.5' }}"></span>
                    </button>
                </form>
            </div>
        </div>
    </section>

    {{-- Stat cards: white / dark cards, structured, clean contrast, with icons --}}
    <div class="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3 min-w-0">
        <div class="rounded-lg border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-sm p-4 sm:p-5 min-w-0 hover:shadow-md transition-shadow flex items-center gap-3">
            <div class="flex h-9 w-9 sm:h-10 sm:w-10 flex-shrink-0 items-center justify-center rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-100">
                <i class="fas fa-users-cog text-sm sm:text-base"></i>
            </div>
            <div class="min-w-0">
                <p class="text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wide truncate">Staff users</p>
                <p class="mt-0.5 text-2xl font-bold tabular-nums text-gray-900 dark:text-slate-50">{{ $overview['users'] ?? 0 }}</p>
            </div>
        </div>
        <div class="rounded-lg border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-sm p-4 sm:p-5 min-w-0 hover:shadow-md transition-shadow flex items-center gap-3">
            <div class="flex h-9 w-9 sm:h-10 sm:w-10 flex-shrink-0 items-center justify-center rounded-xl bg-amber-100 dark:bg-amber-900 text-amber-700 dark:text-amber-100">
                <i class="fas fa-school text-sm sm:text-base"></i>
            </div>
            <div class="min-w-0">
                <p class="text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wide truncate">Schools</p>
                <p class="mt-0.5 text-2xl font-bold tabular-nums text-gray-900 dark:text-slate-50">{{ $overview['schools'] ?? 0 }}</p>
            </div>
        </div>
        <div class="rounded-lg border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-sm p-4 sm:p-5 min-w-0 hover:shadow-md transition-shadow flex items-center gap-3">
            <div class="flex h-9 w-9 sm:h-10 sm:w-10 flex-shrink-0 items-center justify-center rounded-xl bg-emerald-100 dark:bg-emerald-900 text-emerald-700 dark:text-emerald-100">
                <i class="fas fa-user-graduate text-sm sm:text-base"></i>
            </div>
            <div class="min-w-0">
                <p class="text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wide truncate">Students</p>
                <p class="mt-0.5 text-2xl font-bold tabular-nums text-amber-600 dark:text-amber-300">{{ $overview['students'] ?? 0 }}</p>
            </div>
        </div>
    </div>

    {{-- Docu Mentor usage + Database health --}}
    @php
        $dm = $dmOverview ?? [];
        $db = $dbMeta ?? [];
    @endphp
    <section class="rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-sm p-4 sm:p-5 min-w-0">
        <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
            <h2 class="text-xs sm:text-sm font-semibold text-gray-900 dark:text-slate-100">Platform overview</h2>
            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 dark:bg-emerald-900/40 px-2 py-0.5 text-[10px] font-medium text-emerald-700 dark:text-emerald-200">
                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                Live overview
            </span>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4">
            <div class="rounded-lg border border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-900/60 p-3.5 sm:p-4 flex items-start gap-3 min-w-0">
                <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-sky-100 dark:bg-sky-900 text-sky-700 dark:text-sky-100">
                    <i class="fas fa-user-tie text-sm"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-[11px] font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wide">Staff roles</p>
                    <p class="mt-0.5 text-sm font-semibold tabular-nums text-slate-900 dark:text-slate-50">
                        {{ $dm['coordinators'] ?? 0 }} coordinators · {{ $dm['supervisors'] ?? 0 }} supervisors
                    </p>
                </div>
            </div>
            <div class="rounded-lg border border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-900/60 p-3.5 sm:p-4 flex items-start gap-3 min-w-0">
                <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-amber-100 dark:bg-amber-900 text-amber-700 dark:text-amber-100">
                    <i class="fas fa-calendar-check text-sm"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-[11px] font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wide">Academic years</p>
                    <p class="mt-0.5 text-sm font-semibold tabular-nums text-slate-900 dark:text-slate-50">
                        {{ ($dm['active_academic_years'] ?? 0) > 0 ? 'Active year configured' : 'No active year yet' }}
                    </p>
                </div>
            </div>
            <div class="rounded-lg border border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-900/60 p-3.5 sm:p-4 flex items-start gap-3 min-w-0">
                <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-emerald-100 dark:bg-emerald-900 text-emerald-700 dark:text-emerald-100">
                    <i class="fas fa-diagram-project text-sm"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-[11px] font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wide">Projects &amp; submissions</p>
                    <p class="mt-0.5 text-sm font-semibold tabular-nums text-slate-900 dark:text-slate-50">
                        {{ $dm['projects'] ?? 0 }} projects · {{ $dm['submissions'] ?? 0 }} submissions
                    </p>
                </div>
            </div>
        </div>
        <div class="mt-4 rounded-lg border border-dashed border-slate-200 dark:border-slate-700 bg-white/80 dark:bg-slate-900/70 p-3.5 sm:p-4 flex flex-col sm:flex-row items-start sm:items-center gap-3 min-w-0">
            <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-slate-900 text-emerald-400">
                <i class="fas fa-database text-base"></i>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-700 dark:text-slate-200">Database connection</p>
                <p class="mt-0.5 text-sm text-slate-800 dark:text-slate-100">
                    @if($db['connected'] ?? false)
                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 dark:bg-emerald-900/60 px-2 py-0.5 text-[11px] font-semibold text-emerald-800 dark:text-emerald-100 mr-1.5">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                            Connected
                        </span>
                        <span class="tabular-nums text-xs sm:text-sm text-slate-700 dark:text-slate-200">
                            {{ $db['driver'] ?? 'db' }} @ {{ $db['database'] ?? 'default' }}
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 rounded-full bg-red-100 dark:bg-red-900/60 px-2 py-0.5 text-[11px] font-semibold text-red-800 dark:text-red-100 mr-1.5">
                            <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>
                            Not connected
                        </span>
                    @endif
                </p>
                <p class="mt-1 text-[11px] text-slate-500 dark:text-slate-300">
                    <span>Tables: <span class="font-semibold tabular-nums">{{ $db['tables'] ?? '—' }}</span></span>
                    <span class="mx-1.5 text-slate-400">•</span>
                    <span>Migrations: <span class="font-semibold tabular-nums">{{ $db['migrations_total'] ?? '—' }}</span></span>
                    @if(!empty($db['last_migration']))
                        <span class="mx-1.5 text-slate-400">•</span>
                        <span class="truncate inline-block max-w-full align-middle" title="{{ $db['last_migration'] }}">
                            Last change: <span class="font-semibold">{{ $db['last_migration'] }}</span>
                        </span>
                    @endif
                </p>
            </div>
        </div>
    </section>

    <section class="rounded-lg border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-sm p-4 sm:p-5 min-w-0">
        <h2 class="text-xs font-semibold text-gray-600 dark:text-slate-300 uppercase tracking-wider mb-3">Quick links</h2>
        <div class="flex flex-wrap gap-2 sm:gap-3">
            <a href="{{ route('dashboard.settings.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-2.5 min-h-[44px] hover:bg-gray-50 dark:hover:bg-slate-800 hover:border-gray-300 dark:hover:border-slate-500 transition-colors touch-manipulation text-gray-800 dark:text-slate-100" title="Configure app, mail, AI, and Cloudinary">
                <span class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-md bg-gray-100 dark:bg-slate-800 text-gray-500 dark:text-slate-200"><svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg></span>
                <span class="text-sm font-medium">Settings</span>
            </a>
            <a href="{{ route('dashboard.schools.index') }}" class="inline-flex items-center gap-2 rounded-lg bg-amber-500 hover:bg-amber-600 text-white px-3 py-2.5 min-h-[44px] transition-colors touch-manipulation font-medium text-sm" title="Manage schools and add departments">
                <span class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-md bg-amber-600/50 text-white"><svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg></span>
                <span>Schools &amp; departments</span>
            </a>
            <a href="{{ route('dashboard.users.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 dark:border-slate-700 bg-gray-200 dark:bg-slate-800 hover:bg-gray-300 dark:hover:bg-slate-700 text-gray-800 dark:text-slate-100 px-3 py-2.5 min-h-[44px] transition-colors touch-manipulation font-medium text-sm" title="Manage staff (Super Admin and Supervisors)">
                <span class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-md bg-gray-300 dark:bg-slate-700 text-gray-600 dark:text-slate-100"><svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg></span>
                <span>Users</span>
            </a>
            <a href="{{ route('dashboard.system.reset.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-red-200 bg-red-600 hover:bg-red-700 text-white px-3 py-2.5 min-h-[44px] transition-colors touch-manipulation font-medium text-sm" title="Clear data or full system reset (use with caution)">
                <span class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-md bg-red-700/50 text-white"><svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg></span>
                <span>Reset</span>
            </a>
        </div>
    </section>
</div>

@if(($update_mode ?? false) && ($update_estimated_end ?? null))
@push('scripts')
<script>
(function () {
    var el = document.getElementById('update-mode-countdown');
    if (!el) return;
    var endMs = new Date("{{ \Carbon\Carbon::parse($update_estimated_end)->toIso8601String() }}").getTime();
    if (!endMs || Number.isNaN(endMs)) return;
    function formatLeft(totalSeconds) {
        totalSeconds = Math.max(0, Math.floor(totalSeconds));
        var h = Math.floor(totalSeconds / 3600);
        var m = Math.floor((totalSeconds % 3600) / 60);
        var s = totalSeconds % 60;
        if (h > 0) {
            return String(h) + ':' + String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
        }
        return String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
    }
    function tick() {
        var left = Math.max(0, Math.ceil((endMs - Date.now()) / 1000));
        el.textContent = formatLeft(left);
        if (left <= 0) {
            clearInterval(timer);
        }
    }
    tick();
    var timer = setInterval(tick, 1000);
})();
</script>
@endpush
@endif

@endsection
