@extends('layouts.dashboard')

@section('title', 'Supervisors')
@section('dashboard_heading', 'Supervisors')

@section('dashboard_content')
@php
    $arkeselConfigured = $arkeselConfigured ?? false;
    $coordinatorSmsRemaining = $coordinatorSmsRemaining ?? 0;
@endphp
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
                <span>Total: <span class="tabular-nums">{{ $supervisors->total() }}</span></span>
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

    {{-- Filters + table: Name | Phone | Assigned projects | Students --}}
    <section class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/60 space-y-2">
            <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <h2 class="text-sm font-semibold text-slate-800 dark:text-slate-100">All supervisors</h2>
                <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 dark:bg-slate-800 px-2 py-0.5 text-[11px] font-medium text-slate-600 dark:text-slate-300">
                    <i class="fas fa-diagram-project text-[10px]"></i>
                    <span>Projects per supervisor</span>
                </span>
            </div>
            <form id="supervisors-filter-form" method="get" action="{{ route('dashboard.coordinators.supervisors.index') }}" class="flex flex-wrap items-center gap-2 text-xs sm:text-sm">
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2 text-slate-400 dark:text-slate-500">
                        <i class="fas fa-search text-[11px]"></i>
                    </span>
                    <input
                        type="search"
                        name="search"
                        id="supervisors-search"
                        value="{{ $search ?? request('search') }}"
                        placeholder="Search name or phone…"
                        class="rounded-full border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 pl-7 pr-3 py-1.5 text-xs sm:text-sm w-40 sm:w-56 text-slate-900 dark:text-slate-100 placeholder:text-slate-400 dark:placeholder:text-slate-500"
                        autocomplete="off"
                    >
                </div>
                <select name="projects" id="supervisors-projects-filter" class="rounded-full border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-2.5 py-1.5 text-xs sm:text-sm text-slate-900 dark:text-slate-100">
                    <option value="">{{ __('All projects') }}</option>
                    <option value="with" {{ ($projectsFilter ?? request('projects')) === 'with' ? 'selected' : '' }}>With projects</option>
                    <option value="without" {{ ($projectsFilter ?? request('projects')) === 'without' ? 'selected' : '' }}>Without projects</option>
                </select>
            </form>
            </div>
            <p class="text-xs text-slate-600 dark:text-slate-400">
                <i class="fas fa-key text-[10px] mr-1"></i>
                Send login URL, username, and a new password by SMS — <span class="font-semibold tabular-nums">{{ $coordinatorSmsRemaining }}</span> credits remaining.
                @if(! $arkeselConfigured)
                    <span class="text-amber-700 dark:text-amber-400 font-medium">Configure Arkesel under Settings → OTP.</span>
                @endif
            </p>
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
                            <th class="px-3 py-3 text-left w-10" scope="col">
                                <input type="checkbox" id="idx-supervisors-select-all" class="rounded border-slate-300 dark:border-slate-600 text-primary-600 focus:ring-primary-500" title="Select all on this page" aria-label="Select all supervisors on this page">
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Name</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Phone</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Assigned projects</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Students</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Login SMS</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800 bg-white dark:bg-slate-900">
                        @foreach($supervisors as $u)
                            @php
                                $canSendRow = $arkeselConfigured && !empty(trim((string)($u->phone ?? ''))) && $coordinatorSmsRemaining >= 1;
                            @endphp
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/60">
                                <td class="px-3 py-3">
                                    <input type="checkbox" name="supervisor_ids[]" value="{{ $u->id }}" form="idx-supervisor-bulk-sms-form" class="idx-supervisor-sms-cb rounded border-slate-300 dark:border-slate-600 text-primary-600 focus:ring-primary-500" aria-label="Select {{ $u->name ?? $u->username }}">
                                </td>
                                <td class="px-4 py-3 text-sm font-medium text-slate-900 dark:text-slate-50">
                                    <span class="inline-flex items-center gap-1.5">
                                        <i class="fas fa-user-circle text-slate-400"></i>
                                        <span>{{ $u->name ?? '—' }}</span>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">{{ $u->phone ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm tabular-nums text-slate-700 dark:text-slate-200">{{ $u->supervised_projects_count ?? 0 }}</td>
                                <td class="px-4 py-3 text-sm tabular-nums text-slate-700 dark:text-slate-200">{{ $u->total_students_count ?? 0 }}</td>
                                <td class="px-4 py-3 text-sm">
                                    <form method="post" action="{{ route('dashboard.coordinators.supervisors.send-login-sms', $u) }}" class="inline" onsubmit="return confirm('Send a new random password, username, and login link by SMS to this supervisor? Their old password will stop working.');">
                                        @csrf
                                        <button
                                            type="submit"
                                            class="inline-flex items-center rounded-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-2.5 py-1.5 text-xs font-medium text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-50"
                                            @if(! $canSendRow) disabled title="{{ ! $arkeselConfigured ? 'Configure Arkesel under Settings → OTP.' : (!trim((string)($u->phone ?? '')) ? 'Add a phone number for this supervisor.' : 'No SMS credits left.') }}" @endif
                                        >Send</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <form id="idx-supervisor-bulk-sms-form" method="post" action="{{ route('dashboard.coordinators.supervisors.send-login-sms-bulk') }}" class="flex flex-wrap items-center gap-3 px-4 py-3 border-t border-slate-100 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-900/70" onsubmit="return confirm('Send a new random password and login link by SMS to each selected supervisor on this page? (1 SMS credit per successful send.)');">
                @csrf
                <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-slate-800 dark:bg-slate-100 dark:text-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-900 dark:hover:bg-white disabled:cursor-not-allowed disabled:opacity-50" @if(! $arkeselConfigured || $coordinatorSmsRemaining < 1) disabled title="{{ ! $arkeselConfigured ? 'Configure Arkesel first.' : 'No SMS credits.' }}" @endif>
                    <i class="fas fa-paper-plane text-xs"></i>
                    Send login SMS to selected
                </button>
                <span class="text-xs text-slate-500 dark:text-slate-400">This page only · up to 50 · no phone = skipped</span>
            </form>
            <div class="px-4 py-3 border-t border-slate-100 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-900/60 flex items-center justify-between text-xs text-slate-500 dark:text-slate-300">
                <div>
                    Showing
                    <span class="font-medium tabular-nums">{{ $supervisors->firstItem() }}</span>
                    –
                    <span class="font-medium tabular-nums">{{ $supervisors->lastItem() }}</span>
                    of
                    <span class="font-medium tabular-nums">{{ $supervisors->total() }}</span>
                    supervisors
                </div>
                <div class="text-right">
                    {{ $supervisors->links() }}
                </div>
            </div>
        @endif
    </section>

    <p class="text-xs text-slate-500 dark:text-slate-300">Assign or unassign supervisors to projects from <a href="{{ route('dashboard.coordinators.projects.index') }}" class="text-primary-600 hover:underline">Projects</a> → open a project → Review.</p>
</div>

@push('scripts')
<script>
(function () {
    var form = document.getElementById('supervisors-filter-form');
    if (!form) return;

    var searchInput = document.getElementById('supervisors-search');
    var projectsSelect = document.getElementById('supervisors-projects-filter');
    var timer = null;

    function submitWithDebounce() {
        if (timer) {
            clearTimeout(timer);
        }
        timer = setTimeout(function () {
            form.submit();
        }, 400);
    }

    if (searchInput) {
        searchInput.addEventListener('input', submitWithDebounce);
    }
    if (projectsSelect) {
        projectsSelect.addEventListener('change', function () {
            form.submit();
        });
    }

    var masterIdx = document.getElementById('idx-supervisors-select-all');
    if (masterIdx) {
        masterIdx.addEventListener('change', function () {
            document.querySelectorAll('.idx-supervisor-sms-cb').forEach(function (cb) { cb.checked = masterIdx.checked; });
        });
    }
})();
</script>
@endpush

@endsection
