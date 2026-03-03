@extends('layouts.dashboard')

@section('title', 'Supervisors – ' . $academicYear->year)
@section('dashboard_heading', 'Supervisors')
@section('breadcrumb_trail')
<a href="{{ route('dashboard.coordinators.students.index') }}" class="hover:text-gray-700">Academic years</a>
<svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
<span class="text-gray-800 font-medium">{{ $academicYear->year }}</span>
@endsection

@section('dashboard_content')
<div class="w-full space-y-6">
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm p-4">
        <h1 class="text-lg font-semibold text-slate-900">Academic year: {{ $academicYear->year }}</h1>
        <p class="text-sm text-slate-500 mt-0.5">Supervisors in this year. Upload CSV or add a single supervisor.</p>
    </div>

    @if(session('success'))<div class="rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-800">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">{{ session('error') }}</div>@endif

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

    {{-- Table: Name | Email | Assigned Projects --}}
    <section class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-100 bg-slate-50">
            <h2 class="text-sm font-semibold text-slate-800">Supervisors ({{ $supervisors->count() }})</h2>
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
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Name</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Email</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Assigned projects</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @foreach($supervisors as $u)
                            <tr class="hover:bg-slate-50/50">
                                <td class="px-4 py-3 text-sm font-medium text-slate-900">{{ $u->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $u->email ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm tabular-nums text-slate-700">{{ $u->supervised_projects_count ?? 0 }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</div>
@endsection
