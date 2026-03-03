@extends('layouts.dashboard')

@section('title', 'Student details')
@section('dashboard_heading', 'Student details')

@section('dashboard_content')
<div class="w-full space-y-6">
    @if(session('success'))
        <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800">{{ session('success') }}</div>
    @endif

    <a href="{{ route('dashboard.coordinators.students.index') }}" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-gray-700">
        <i class="fas fa-arrow-left"></i> Back to students
    </a>

    <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/80 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-sm font-semibold text-gray-800 uppercase tracking-wide">Student information</h2>
            <div class="flex flex-wrap items-center gap-2">
                <form action="{{ route('dashboard.coordinators.students.toggle-leader', ['encodedIndex' => $encodedIndex]) }}" method="post" class="inline">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1">
                        <i class="fas fa-user-tie text-xs"></i> {{ ($isGroupLeader ?? false) ? 'Unassign' : 'Set as group leader' }}
                    </button>
                </form>
                <a href="{{ route('dashboard.coordinators.students.edit', ['encodedIndex' => $encodedIndex]) }}" class="inline-flex items-center justify-center h-9 w-9 rounded-lg border border-gray-300 bg-white text-gray-600 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1" title="Edit" aria-label="Edit">
                    <i class="fas fa-edit text-sm"></i>
                </a>
                <form action="{{ route('dashboard.coordinators.students.destroy', ['encodedIndex' => $encodedIndex]) }}" method="post" class="inline" onsubmit="return confirm('Remove this student?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center justify-center h-9 w-9 rounded-lg border border-gray-300 bg-white text-gray-600 hover:bg-red-50 hover:text-red-600 hover:border-red-200 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-1" title="Remove" aria-label="Remove">
                        <i class="fas fa-user-minus text-sm"></i>
                    </button>
                </form>
            </div>
        </div>

        <div class="px-6 py-6 space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div class="rounded-lg bg-gray-50/50 p-4">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Index number</p>
                    <p class="text-sm font-mono font-semibold text-gray-900">{{ $indexNumber }}</p>
                </div>
                <div class="rounded-lg bg-gray-50/50 p-4">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Name</p>
                    <p class="text-sm font-medium text-gray-900">{{ $displayName ?: '—' }}</p>
                </div>
                <div class="rounded-lg bg-gray-50/50 p-4">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Phone number</p>
                    <p class="text-sm text-gray-900">{{ $phone ?: '—' }}</p>
                </div>
                <div class="rounded-lg bg-gray-50/50 p-4">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Group leader</p>
                    <p class="text-sm text-gray-900">{{ ($isGroupLeader ?? false) ? 'Yes' : 'No' }}</p>
                </div>
                @if($institution || $faculty || $department)
                <div class="sm:col-span-2 rounded-lg bg-gray-50/50 p-4">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Institution / Faculty / Department</p>
                    <p class="text-sm text-gray-900">{{ implode(' · ', array_filter([$institution, $faculty, $department])) ?: '—' }}</p>
                </div>
                @endif
            </div>

        </div>
    </div>
</div>
@endsection
