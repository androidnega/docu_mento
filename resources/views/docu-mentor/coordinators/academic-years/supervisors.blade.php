@extends('layouts.dashboard')

@section('title', 'Supervisors – ' . $academicYear->year)
@section('dashboard_heading', 'Supervisors')
@section('breadcrumb_trail')
<a href="{{ route('dashboard.coordinators.students.index') }}" class="hover:text-gray-700">Academic years</a>
<svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
<span class="text-gray-800 font-medium">{{ $academicYear->year }}</span>
@endsection

@section('dashboard_content')
@php
    $arkeselConfigured = $arkeselConfigured ?? false;
    $coordinatorSmsRemaining = $coordinatorSmsRemaining ?? 0;
@endphp
<div class="w-full space-y-6">
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm p-4">
        <h1 class="text-lg font-semibold text-slate-900">Academic year: {{ $academicYear->year }}</h1>
        <p class="text-sm text-slate-500 mt-0.5">Supervisors in this year. Upload CSV or add a single supervisor.</p>
    </div>

    @if(session('success'))<div class="rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-800">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">{{ session('error') }}</div>@endif

    <div class="rounded-xl border border-emerald-200/70 bg-gradient-to-br from-emerald-50 via-white to-teal-50/90 p-4 shadow-sm ring-1 ring-emerald-500/10">
        <div class="flex items-start gap-3">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-emerald-600 text-white text-sm font-bold">SMS</span>
            <div class="min-w-0">
                <p class="text-sm font-semibold text-emerald-950">Automatic welcome text</p>
                <p class="text-xs text-emerald-900/85 mt-1 leading-relaxed">Each <strong>new</strong> supervisor you add here (with a phone number) receives the login link, username, and password by SMS when Arkesel is configured — one credit per supervisor from your allocation.</p>
            </div>
        </div>
    </div>

    {{-- Upload Supervisors (CSV) + Add Single Supervisor --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-800 mb-3">Upload supervisors (CSV)</h2>
            <form action="{{ route('dashboard.coordinators.students.upload') }}" method="post" enctype="multipart/form-data" class="space-y-3">
                @csrf
                <input type="hidden" name="academic_year_id" value="{{ $academicYear->id }}">
                <input type="hidden" name="role" value="supervisor">
                <input type="file" name="file" accept=".xlsx,.xls,.csv" required class="block w-full text-sm text-slate-600 file:mr-2 file:rounded-lg file:border-0 file:bg-primary-50 file:px-3 file:py-2 file:text-primary-700">
                <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700">Upload CSV</button>
            </form>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-800 mb-3">Add single supervisor</h2>
            <form action="{{ route('dashboard.coordinators.students.store') }}" method="post" class="space-y-3">
                @csrf
                <input type="hidden" name="academic_year_id" value="{{ $academicYear->id }}">
                <input type="hidden" name="role" value="supervisor">
                <div>
                    <label for="index_number" class="block text-xs font-medium text-slate-600 mb-0.5">Index number <span class="text-red-500">*</span></label>
                    <input type="text" name="index_number" id="index_number" required maxlength="64" value="{{ old('index_number') }}" class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label for="name" class="block text-xs font-medium text-slate-600 mb-0.5">Name</label>
                    <input type="text" name="name" id="name" maxlength="255" value="{{ old('name') }}" class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label for="phone" class="block text-xs font-medium text-slate-600 mb-0.5">Phone</label>
                    <input type="text" name="phone" id="phone" maxlength="20" value="{{ old('phone') }}" class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                </div>
                <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700">Add supervisor</button>
            </form>
        </div>
    </div>

    {{-- Table: Name | Phone | Email | Assigned projects | Login SMS --}}
    <section class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-100 bg-slate-50 space-y-2">
            <h2 class="text-sm font-semibold text-slate-800">Supervisors ({{ $supervisors->count() }})</h2>
            <p class="text-xs text-slate-600">
                Send login URL, username, and a <strong>new</strong> random password by SMS (1 credit per supervisor). Your SMS credits remaining: <span class="font-semibold tabular-nums">{{ $coordinatorSmsRemaining }}</span>.
                @if(! $arkeselConfigured)
                    <span class="text-amber-700 font-medium">SMS provider is not configured.</span>
                @endif
            </p>
        </div>
        @if($supervisors->isEmpty())
            <div class="p-8 text-center text-slate-500">
                <p>No supervisors in this academic year yet.</p>
                <p class="text-sm mt-1">Use &quot;Upload supervisors&quot; or &quot;Add single supervisor&quot; above.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-3 py-3 text-left w-10" scope="col">
                                <input type="checkbox" id="ay-supervisors-select-all" class="rounded border-slate-300 text-primary-600 focus:ring-primary-500" title="Select all" aria-label="Select all supervisors for bulk SMS">
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Name</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Phone</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Email</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Assigned projects</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Login SMS</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @foreach($supervisors as $u)
                            @php
                                $canSendRow = $arkeselConfigured && !empty(trim((string)($u->phone ?? ''))) && $coordinatorSmsRemaining >= 1;
                            @endphp
                            <tr class="hover:bg-slate-50/50">
                                <td class="px-3 py-3">
                                    <input type="checkbox" name="supervisor_ids[]" value="{{ $u->id }}" form="ay-supervisor-bulk-sms-form" class="ay-supervisor-sms-cb rounded border-slate-300 text-primary-600 focus:ring-primary-500" aria-label="Select {{ $u->name ?? $u->username }}">
                                </td>
                                <td class="px-4 py-3 text-sm font-medium text-slate-900">{{ $u->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $u->phone ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $u->email ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm tabular-nums text-slate-700">{{ $u->supervised_projects_count ?? 0 }}</td>
                                <td class="px-4 py-3 text-sm">
                                    <form method="post" action="{{ route('dashboard.coordinators.supervisors.send-login-sms', $u) }}" class="inline" onsubmit="return confirm('Send a new random password, username, and login link by SMS to this supervisor? Their old password will stop working.');">
                                        @csrf
                                        <button
                                            type="submit"
                                            class="inline-flex items-center rounded-md border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                                            @if(! $canSendRow) disabled title="{{ ! $arkeselConfigured ? 'Configure Arkesel under Settings → OTP.' : (!trim((string)($u->phone ?? '')) ? 'Add a phone number for this supervisor.' : 'No SMS credits left.') }}" @endif
                                        >Send</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <form id="ay-supervisor-bulk-sms-form" method="post" action="{{ route('dashboard.coordinators.supervisors.send-login-sms-bulk') }}" class="flex flex-wrap items-center gap-3 px-4 py-3 border-t border-slate-100 bg-slate-50/80" onsubmit="return confirm('Send a new random password and login link by SMS to each selected supervisor? (1 SMS credit per successful send.)');">
                @csrf
                <input type="hidden" name="academic_year_id" value="{{ $academicYear->id }}">
                <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-900 disabled:cursor-not-allowed disabled:opacity-50" @if(! $arkeselConfigured || $coordinatorSmsRemaining < 1) disabled title="{{ ! $arkeselConfigured ? 'Configure Arkesel first.' : 'No SMS credits.' }}" @endif>
                    Send login SMS to selected
                </button>
                <span class="text-xs text-slate-500">Up to 50 per request. Supervisors without a phone are skipped.</span>
            </form>
        @endif
    </section>
</div>
@push('scripts')
<script>
(function () {
    var master = document.getElementById('ay-supervisors-select-all');
    if (!master) return;
    master.addEventListener('change', function () {
        document.querySelectorAll('.ay-supervisor-sms-cb').forEach(function (cb) { cb.checked = master.checked; });
    });
})();
</script>
@endpush
@endsection
