@extends('layouts.dashboard')

@section('title', 'Students')
@section('dashboard_heading', 'Students')

@section('dashboard_content')
<div class="w-full space-y-6">
    @if(session('success'))
        <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800">{{ session('error') }}</div>
    @endif

    <div class="rounded-xl border border-slate-200 bg-white shadow-sm p-4">
        <h1 class="text-lg font-semibold text-slate-900">Students</h1>
        <p class="text-sm text-slate-500 mt-0.5">List of students by academic year. Use Academic Years to upload or add students.</p>
    </div>

    {{-- Year filter --}}
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <form method="get" action="{{ route('dashboard.coordinators.students.list') }}" class="flex flex-wrap items-end gap-3">
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
        <a href="{{ route('dashboard.coordinators.academic-years.students', $academicYear) }}" class="inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 no-underline">Upload / Add students ({{ $academicYear->year }})</a>
        <a href="{{ route('dashboard.coordinators.students.index') }}" class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 no-underline">Academic years</a>
    </div>
    @endif

    {{-- Table --}}
    <section class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-100 bg-slate-50">
            <h2 class="text-sm font-semibold text-slate-800">Students {{ $academicYear ? "({$academicYear->year})" : '' }} ({{ $students->count() }})</h2>
        </div>
        @if(!$academicYear)
            <div class="p-8 text-center text-slate-500">
                <p>Select an academic year above to see students.</p>
            </div>
        @elseif($students->isEmpty())
            <div class="p-8 text-center text-slate-500">
                <p>No students in this academic year yet.</p>
                <a href="{{ route('dashboard.coordinators.academic-years.students', $academicYear) }}" class="mt-2 inline-block text-sm font-medium text-primary-600 hover:underline">Upload or add students</a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Index number</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Name</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Phone</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Leader</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @foreach($students as $u)
                            @php
                                $idx = $u->index_number ?? '';
                                $encodedIndex = $idx !== '' ? \App\Http\Controllers\DocuMentor\CoordinatorStudentController::encodeIndex($idx) : '';
                            @endphp
                            <tr class="hover:bg-slate-50/50">
                                <td class="px-4 py-3 text-sm font-medium text-slate-900">{{ $idx !== '' ? $idx : '—' }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ $u->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $u->phone ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex px-2.5 py-0.5 rounded-lg text-xs font-medium {{ $u->is_active ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-600' }}">
                                        {{ $u->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    @if(\Illuminate\Support\Facades\Schema::hasColumn('users', 'group_leader'))
                                        <div class="flex flex-col gap-2 items-start">
                                            @if(!empty($u->group_leader))
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg text-xs font-medium bg-amber-100 text-amber-800"><i class="fas fa-crown"></i> Leader</span>
                                            @else
                                                <span class="text-slate-400 text-xs">Not leader</span>
                                            @endif
                                            @if($encodedIndex !== '')
                                                <form action="{{ route('dashboard.coordinators.students.toggle-leader', ['encodedIndex' => $encodedIndex]) }}" method="post" class="inline">
                                                    @csrf
                                                    <input type="hidden" name="user_id" value="{{ $u->id }}">
                                                    <input type="hidden" name="academic_year_id" value="{{ $academicYear->id }}">
                                                    <input type="hidden" name="return_url" value="{{ route('dashboard.coordinators.students.list', ['academic_year_id' => $academicYear->id], false) }}">
                                                    <button type="submit" class="inline-flex items-center gap-1 rounded border {{ !empty($u->group_leader) ? 'border-amber-200 bg-amber-50 text-amber-800 hover:bg-amber-100' : 'border-primary-200 bg-primary-50 text-primary-800 hover:bg-primary-100' }} px-2.5 py-1.5 text-xs font-medium" title="{{ !empty($u->group_leader) ? 'Remove group leader' : 'Set as group leader' }}">
                                                        <i class="fas {{ !empty($u->group_leader) ? 'fa-user-minus' : 'fa-crown' }}"></i> {{ !empty($u->group_leader) ? 'Remove leader' : 'Set as leader' }}
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-xs text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    @if($encodedIndex !== '')
                                        <a href="{{ route('dashboard.coordinators.students.show', ['encodedIndex' => $encodedIndex]) }}" class="inline-flex items-center gap-1 rounded border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50 no-underline" title="View details"><i class="fas fa-eye"></i> View</a>
                                    @else
                                        <span class="text-xs text-slate-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</div>
@endsection
