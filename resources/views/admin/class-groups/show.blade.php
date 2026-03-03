@php
    $isSuperAdmin = session('admin_role') === 'super_admin';
    $isSupervisor = auth()->user() && auth()->user()->role === \App\Models\User::ROLE_SUPERVISOR;
    $accent = $classGroup->accent_classes ?? ['bg' => 'bg-sky-50', 'border' => 'border-sky-200', 'text' => 'text-sky-800'];
@endphp
@extends('layouts.dashboard')

@section('title', $classGroup->display_name)
@section('dashboard_heading')
    <span class="inline-flex items-center gap-2 font-display tracking-tight"><i class="fas fa-users text-primary-600"></i>{{ $classGroup->display_name }}</span>
@endsection

@section('dashboard_content')
<div class="w-full space-y-6">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-error">{{ session('error') }}</div>
    @endif

    {{-- Back link --}}
    <a href="{{ route('dashboard.class-groups.index') }}" class="inline-flex items-center gap-2 text-sm text-gray-600 hover:text-primary-600">
        <i class="fas fa-arrow-left"></i> Back to class groups
    </a>

    {{-- Card: Actions (compact) — soft accent from group color --}}
    <div class="rounded-lg {{ $accent['bg'] }} border {{ $accent['border'] }} px-4 py-3 shadow-sm space-y-3">
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-sm font-semibold text-gray-700 mr-1">Group actions</span>
            @if(!$isSupervisor)
            <a href="{{ route('dashboard.class-groups.edit', $classGroup) }}" class="inline-flex items-center gap-1.5 rounded-md border border-gray-300 bg-white px-2.5 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50" title="Edit {{ $classGroup->display_name }}"><i class="fas fa-pen text-xs"></i> Edit</a>
            <form action="{{ route('dashboard.class-groups.destroy', $classGroup) }}" method="post" class="inline" onsubmit="return confirm('Delete class group \'{{ addslashes($classGroup->display_name) }}\'? This cannot be undone.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="inline-flex items-center gap-1.5 rounded-md border border-danger-300 bg-danger-50 px-2.5 py-1.5 text-sm font-medium text-danger-700 hover:bg-danger-100" title="Delete {{ $classGroup->display_name }}"><i class="fas fa-trash-alt text-xs"></i> Delete</button>
            </form>
            @endif
        </div>
        @if(!$isSupervisor)
            @php
                $allowedDevices = $classGroup->allowed_devices ?? \App\Models\ClassGroup::ALLOWED_DEVICES_DESKTOP;
                $allowedOptions = \App\Models\ClassGroup::allowedDevicesOptions();
            @endphp
            <form method="post" action="{{ route('dashboard.class-groups.allowed-devices.update', $classGroup) }}" class="flex flex-wrap items-center gap-2 mt-1">
                @csrf
                @method('PUT')
                <label for="allowed_devices" class="text-xs font-semibold text-gray-700">Allowed devices:</label>
                <select id="allowed_devices" name="allowed_devices" class="text-xs border-gray-300 rounded-md px-2 py-1 focus:ring-primary-500 focus:border-primary-500">
                    @foreach($allowedOptions as $value => $label)
                        <option value="{{ $value }}" {{ $allowedDevices === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <button type="submit" class="inline-flex items-center gap-1.5 rounded-md bg-primary-600 px-2.5 py-1 text-xs font-semibold text-white hover:bg-primary-700">
                    <i class="fas fa-save text-[10px]"></i> Save
                </button>
            </form>
        @endif
    </div>

    {{-- Card: Student index list — link to full management page --}}
    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <div class="flex items-start justify-between gap-4 mb-4">
            <div class="min-w-0">
                <h2 class="text-lg font-semibold text-gray-900 mb-1 flex items-center gap-2">
                    <i class="fas fa-user-graduate text-primary-600"></i> Student index list
                </h2>
                <p class="text-sm text-gray-600">Manage student indices for this class group. Add, edit, remove, or upload from Excel.</p>
            </div>
            <div class="flex-shrink-0 rounded-lg bg-primary-50 px-4 py-2 text-center">
                <span class="text-2xl font-bold tabular-nums text-primary-700">{{ $students->total() }}</span>
                <span class="block text-xs font-medium text-primary-600">indices</span>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-3 pt-4 border-t border-gray-200">
            <a href="{{ route('dashboard.class-groups.students.index', $classGroup) }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-primary-600 hover:text-primary-800">
                <i class="fas fa-external-link-alt"></i> Manage students
            </a>
            @if($students->total() > 0)
                <span class="text-gray-300">|</span>
                <a href="{{ route('dashboard.class-groups.students.export.excel', $classGroup) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-600 bg-gray-50 border border-gray-200 rounded hover:bg-gray-100 hover:border-gray-300" download>
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Download Excel
                </a>
                <a href="{{ route('dashboard.class-groups.students.export.pdf', $classGroup) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-600 bg-gray-50 border border-gray-200 rounded hover:bg-gray-100 hover:border-gray-300" download>
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    Download PDF
                </a>
            @endif
        </div>

        @if(!$isSupervisor && $students->total() > 0)
        <div class="mt-4 pt-4 border-t border-gray-200">
            <p class="text-sm text-gray-600 mb-2">Remove all index numbers from this class group so you can re-upload a fresh list (e.g. from Excel) instead of deleting students one by one.</p>
            <form action="{{ route('dashboard.class-groups.students.clear', $classGroup) }}" method="post" class="inline" onsubmit="return confirm('Remove all {{ $students->total() }} index number(s) from this class group? You can re-upload or add students again after this.');">
                @csrf
                <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium text-white bg-red-600 hover:bg-red-700 border border-red-700/30">
                    <i class="fas fa-trash-alt text-xs"></i> Delete all indices
                </button>
            </form>
        </div>
        @endif
    </div>
</div>
@endsection
