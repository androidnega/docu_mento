@extends('layouts.dashboard')

@section('title', 'Students – ' . $academicYear->year)
@section('dashboard_heading', 'Students')
@section('breadcrumb_trail')
<a href="{{ route('dashboard.coordinators.students.index') }}" class="hover:text-gray-700">Students</a>
<svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
<span class="text-gray-800 font-medium">{{ $academicYear->year }}</span>
@endsection

@section('dashboard_content')
<div class="w-full space-y-6">
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm p-4">
        <h1 class="text-lg font-semibold text-slate-900">Academic year: {{ $academicYear->year }}</h1>
        <p class="text-sm text-slate-500 mt-0.5">Students in this year. Upload CSV, add single, or manage below.</p>
    </div>

    @if(session('success'))<div class="rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-800">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">{{ session('error') }}</div>@endif

    {{-- Upload + Add single --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-800 mb-3">Upload students (CSV)</h2>
            <form action="{{ route('dashboard.coordinators.students.upload') }}" method="post" enctype="multipart/form-data" class="space-y-3">
                @csrf
                <input type="hidden" name="academic_year_id" value="{{ $academicYear->id }}">
                <input type="hidden" name="role" value="student">
                <input type="file" name="file" accept=".xlsx,.xls,.csv" required class="block w-full text-sm text-slate-600 file:mr-2 file:rounded-lg file:border-0 file:bg-primary-50 file:px-3 file:py-2 file:text-primary-700">
                <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700">Upload CSV</button>
            </form>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-800 mb-3">Add single student</h2>
            <form action="{{ route('dashboard.coordinators.students.store') }}" method="post" class="space-y-3">
                @csrf
                <input type="hidden" name="academic_year_id" value="{{ $academicYear->id }}">
                <input type="hidden" name="role" value="student">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div class="min-w-0">
                        <label for="index_number" class="block text-xs font-medium text-slate-600 mb-0.5">Index number <span class="text-red-500">*</span></label>
                        <input type="text" name="index_number" id="index_number" required maxlength="64" value="{{ old('index_number') }}" class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <div class="min-w-0">
                        <label for="name" class="block text-xs font-medium text-slate-600 mb-0.5">Name</label>
                        <input type="text" name="name" id="name" maxlength="255" value="{{ old('name') }}" class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <div class="min-w-0">
                        <label for="phone" class="block text-xs font-medium text-slate-600 mb-0.5">Phone</label>
                        <input type="text" name="phone" id="phone" maxlength="20" value="{{ old('phone') }}" class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                    </div>
                </div>
                <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700">Add student</button>
            </form>
        </div>
    </div>

    {{-- Student management: search, filter, bulk actions --}}
    <section class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/60 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-sm font-semibold text-slate-800 dark:text-slate-100">Student management</h2>
            <form method="get" action="{{ route('dashboard.coordinators.academic-years.students', $academicYear) }}" class="flex flex-wrap items-center gap-2">
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2 text-slate-400 dark:text-slate-500">
                        <i class="fas fa-search text-xs"></i>
                    </span>
                    <input type="search" name="search" value="{{ request('search') }}" placeholder="Search index, name, phone…" class="rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 pl-7 pr-3 py-1.5 text-sm w-44 sm:w-56 max-w-full text-slate-900 dark:text-slate-100 placeholder:text-slate-400 dark:placeholder:text-slate-500">
                </div>
                <select name="status" class="rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-1.5 text-sm text-slate-900 dark:text-slate-100">
                    <option value="">All statuses</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
                <button type="submit" class="inline-flex items-center gap-1 rounded-full border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-1.5 text-sm font-medium text-slate-700 dark:text-slate-100 hover:bg-slate-50 dark:hover:bg-slate-800">
                    <i class="fas fa-filter text-slate-500 dark:text-slate-300"></i>
                    <span>Filter</span>
                </button>
                @if(request()->hasAny(['search', 'status']))
                    <a href="{{ route('dashboard.coordinators.academic-years.students', $academicYear) }}" class="text-sm text-slate-600 hover:underline">Clear</a>
                @endif
            </form>
        </div>

        @if($students->isEmpty())
            <div class="p-8 text-center text-slate-500">
                <p>No students in this academic year yet.</p>
                <p class="text-sm mt-1">Use &quot;Upload students&quot; or &quot;Add single student&quot; above.</p>
            </div>
        @else
            <form id="bulk-form" action="{{ route('dashboard.coordinators.students.bulk-destroy-selected') }}" method="post" onsubmit="return confirm('Delete selected students? This cannot be undone.');">
                @csrf
                <input type="hidden" name="academic_year_id" value="{{ $academicYear->id }}">
                <div class="px-4 py-2 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/60 flex flex-wrap items-center gap-2">
                    <button type="submit" id="bulk-delete-btn" class="inline-flex items-center gap-1.5 rounded-full border border-red-200 bg-red-50 px-3 py-1.5 text-sm font-medium text-red-700 hover:bg-red-100 disabled:opacity-50 disabled:cursor-not-allowed" disabled><i class="fas fa-trash-alt"></i> Delete selected</button>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                        <thead class="bg-slate-50 dark:bg-slate-900/70">
                            <tr>
                                <th class="px-4 py-3 text-left w-10">
                                    <input type="checkbox" id="select-all" class="rounded border-slate-300 text-primary-600 focus:ring-primary-500" aria-label="Select all">
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Index number</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Name</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Phone</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Leader</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800 bg-white dark:bg-slate-900">
                            @foreach($students as $u)
                                @php $encodedIndex = \App\Http\Controllers\DocuMentor\CoordinatorStudentController::encodeIndex($u->index_number ?? ''); @endphp
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/60">
                                    <td class="px-4 py-3">
                                        <input type="checkbox" name="student_ids[]" value="{{ $u->id }}" class="row-checkbox rounded border-slate-300 text-primary-600 focus:ring-primary-500">
                                    </td>
                                    <td class="px-4 py-3 text-sm font-medium text-slate-900 dark:text-slate-50">
                                        <span class="inline-flex items-center gap-1.5">
                                            <i class="fas fa-id-card text-slate-400"></i>
                                            <span>{{ $u->index_number ?? '—' }}</span>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200">{{ $u->name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">{{ $u->phone ?? '—' }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex px-2.5 py-0.5 rounded-lg text-xs font-medium {{ $u->is_active ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-600' }}">
                                            {{ $u->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if(!empty($u->group_leader))
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg text-xs font-medium bg-amber-100 text-amber-800"><i class="fas fa-crown"></i> Leader</span>
                                        @else
                                            <span class="text-slate-400 text-xs">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex items-center justify-end gap-1 flex-wrap">
                                            <a href="{{ route('dashboard.coordinators.students.show', ['encodedIndex' => $encodedIndex]) }}" class="inline-flex items-center gap-1 rounded border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50 no-underline" title="View details"><i class="fas fa-eye"></i> View</a>
                                            <form action="{{ route('dashboard.coordinators.students.toggle-leader', ['encodedIndex' => $encodedIndex]) }}" method="post" class="inline">
                                                @csrf
                                                <input type="hidden" name="user_id" value="{{ $u->id }}">
                                                <input type="hidden" name="return_url" value="{{ route('dashboard.coordinators.academic-years.students', ['academicYear' => $academicYear]) }}">
                                                <button type="submit" class="inline-flex items-center gap-1 rounded border {{ !empty($u->group_leader) ? 'border-amber-200 bg-amber-50 text-amber-800 hover:bg-amber-100' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50' }} px-2.5 py-1.5 text-xs font-medium" title="{{ !empty($u->group_leader) ? 'Remove group leader' : 'Set as group leader' }}">
                                                    <i class="fas {{ !empty($u->group_leader) ? 'fa-user-minus' : 'fa-crown' }}"></i> {{ !empty($u->group_leader) ? 'Remove leader' : 'Set leader' }}
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </form>
        @endif
    </section>
</div>

@if(!$students->isEmpty())
@push('scripts')
<script>
(function() {
    var selectAll = document.getElementById('select-all');
    var checkboxes = document.querySelectorAll('.row-checkbox');
    var bulkBtn = document.getElementById('bulk-delete-btn');
    function updateBulkBtn() {
        var n = document.querySelectorAll('.row-checkbox:checked').length;
        bulkBtn.disabled = n === 0;
    }
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            checkboxes.forEach(function(cb) { cb.checked = selectAll.checked; });
            updateBulkBtn();
        });
    }
    checkboxes.forEach(function(cb) {
        cb.addEventListener('change', function() {
            var all = document.querySelectorAll('.row-checkbox');
            var checked = document.querySelectorAll('.row-checkbox:checked');
            selectAll.checked = all.length === checked.length;
            updateBulkBtn();
        });
    });
    updateBulkBtn();
})();
</script>
@endpush
@endif
@endsection
