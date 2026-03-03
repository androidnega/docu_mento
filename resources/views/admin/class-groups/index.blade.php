@extends('layouts.dashboard')

@section('title', 'Class Groups')
@section('dashboard_heading', 'Class Groups')

@push('styles')
<style>
    @keyframes breathe-glow {
        0%, 100% { opacity: 1; box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4); }
        50% { opacity: 0.92; box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
    }
    .breathe-dot {
        animation: breathe-glow 2.2s ease-in-out infinite;
    }
</style>
@endpush

@section('dashboard_content')
<div class="w-full space-y-4">
    <div class="flex items-center justify-between flex-wrap gap-3">
        <p class="text-sm text-gray-600">Student cohorts. Click a group to view or edit.</p>
        @can('create', \App\Models\ClassGroup::class)
        <a href="{{ route('dashboard.class-groups.create') }}" class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium text-gray-900 bg-yellow-400 hover:bg-yellow-500 border border-yellow-600/30 shadow-sm">Add class group</a>
        @endcan
    </div>

    <form method="get" action="{{ route('dashboard.class-groups.index') }}" id="class_groups_filter_form" class="flex flex-wrap items-end gap-4 rounded-lg border border-gray-200 bg-white p-4">
        @if(isset($academicYears) && $academicYears->isNotEmpty())
        <div>
            <label for="filter_academic_year" class="block text-xs font-medium text-gray-500 mb-1">Academic Year</label>
            <select name="academic_year_id" id="filter_academic_year" class="block w-full min-w-[140px] rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 focus:border-gray-400 focus:ring-1 focus:ring-gray-300 focus:outline-none filter-auto-submit">
                <option value="">All years</option>
                @foreach($academicYears as $ay)
                    <option value="{{ $ay->id }}" {{ request('academic_year_id') == $ay->id ? 'selected' : '' }}>{{ $ay->year }}</option>
                @endforeach
            </select>
        </div>
        @endif
        @if($supervisors->isNotEmpty())
        <div>
            <label for="filter_supervisor" class="block text-xs font-medium text-gray-500 mb-1">Supervisor</label>
            <select name="supervisor_id" id="filter_supervisor" class="block w-full min-w-[160px] rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 focus:border-gray-400 focus:ring-1 focus:ring-gray-300 focus:outline-none filter-auto-submit">
                <option value="">All supervisors</option>
                @foreach($supervisors as $sup)
                    <option value="{{ $sup->id }}" {{ request('supervisor_id') == $sup->id ? 'selected' : '' }}>{{ $sup->name ?: $sup->username }}</option>
                @endforeach
            </select>
        </div>
        @endif
        <div class="flex items-end">
            <a href="{{ route('dashboard.class-groups.index') }}" class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-1 focus:ring-gray-300 focus:ring-offset-1">Clear</a>
        </div>
    </form>
    @push('scripts')
    <script>
    (function(){
        var form = document.getElementById('class_groups_filter_form');
        if (form) {
            form.querySelectorAll('.filter-auto-submit').forEach(function(el){
                el.addEventListener('change', function(){ form.submit(); });
            });
        }
    })();
    </script>
    @endpush

    @if(session('success'))
        <div class="alert alert-success text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-error text-sm">{{ session('error') }}</div>
    @endif

    @if($classGroups->isNotEmpty())
        <section class="border border-gray-200 rounded-lg overflow-hidden">
            <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
                <h2 class="text-sm font-semibold text-gray-700">Groups</h2>
            </div>
            <div class="p-3 bg-white">
                <div class="grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-2">
                    @foreach($classGroups as $g)
                        @include('admin.class-groups.partials.group-card', ['g' => $g])
                    @endforeach
                </div>
            </div>
        </section>
        @if($classGroups->hasPages())
            <div class="mt-4">{{ $classGroups->links() }}</div>
        @endif
    @else
        <div class="rounded-lg border border-gray-200 bg-gray-50 p-8 text-center">
            <p class="text-sm text-gray-500">No class groups yet. Create one to get started.</p>
            @can('create', \App\Models\ClassGroup::class)
            <a href="{{ route('dashboard.class-groups.create') }}" class="mt-3 inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium text-gray-900 bg-yellow-400 hover:bg-yellow-500 border border-yellow-600/30 shadow-sm">Add class group</a>
            @endcan
        </div>
    @endif
</div>
@endsection
