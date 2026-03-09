@extends('layouts.dashboard')

@section('title', 'Supervisors')
@section('dashboard_heading', 'Supervisors')

@section('dashboard_content')
<div class="w-full space-y-6">
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm p-4">
        <h1 class="text-lg font-semibold text-slate-900">Supervisors</h1>
        <p class="text-sm text-slate-500 mt-0.5">List of supervisors by academic year. Use Academic Years to upload or add supervisors.</p>
    </div>

    {{-- Year filter --}}
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <form method="get" action="{{ route('dashboard.coordinators.supervisors.list') }}" class="flex flex-wrap items-end gap-3">
            <label for="academic_year_id" class="block text-xs font-medium text-slate-600">Academic year</label>
            <select name="academic_year_id" id="academic_year_id" onchange="this.form.submit()" class="rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                <option value="">— Select year —</option>
                @foreach($academicYears ?? [] as $ay)
                    <option value="{{ $ay->id }}" {{ (isset($academicYear) && $academicYear && $academicYear->id == $ay->id) ? 'selected' : '' }}>{{ $ay->year }}</option>
                @endforeach
            </select>
            <button type="submit" class="rounded-lg bg-slate-700 px-4 py-2 text-sm font-medium text-white hover:bg-slate-600">Show</button>
        </form>
    </div>

    @if(isset($academicYear) && $academicYear)
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('dashboard.coordinators.academic-years.supervisors', $academicYear) }}" class="inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 no-underline">Upload / Add supervisors ({{ $academicYear->year }})</a>
        <a href="{{ route('dashboard.coordinators.students.index') }}" class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 no-underline">Academic years</a>
    </div>
    @endif

    {{-- Table --}}
    <section class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-100 bg-slate-50">
            <h2 class="text-sm font-semibold text-slate-800">Supervisors {{ $academicYear ? "({$academicYear->year})" : '' }} ({{ $supervisors->count() }})</h2>
        </div>
        @if(!$academicYear)
            <div class="p-8 text-center text-slate-500">
                <p>Select an academic year above to see supervisors.</p>
            </div>
        @elseif($supervisors->isEmpty())
            <div class="p-8 text-center text-slate-500">
                <p>No supervisors in this academic year yet.</p>
                <a href="{{ route('dashboard.coordinators.academic-years.supervisors', $academicYear) }}" class="mt-2 inline-block text-sm font-medium text-primary-600 hover:underline">Upload or add supervisors</a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Name</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Phone</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Assigned projects</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Students</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @foreach($supervisors as $u)
                            <tr class="hover:bg-slate-50/50">
                                <td class="px-4 py-3 text-sm font-medium text-slate-900">{{ $u->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $u->phone ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm tabular-nums text-slate-700">{{ $u->supervised_projects_count ?? 0 }}</td>
                                <td class="px-4 py-3 text-sm tabular-nums text-slate-700">{{ $u->total_students_count ?? 0 }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</div>
@endsection
