@extends('layouts.dashboard')

@section('title', 'Add school')
@section('admin_heading', 'Add school')

@section('dashboard_content')
<div class="w-full max-w-lg space-y-6">
    <div class="flex items-center gap-2 text-sm text-gray-600 mb-6">
        <a href="{{ route('dashboard') }}" class="hover:text-primary-600">Dashboard</a>
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <a href="{{ route('dashboard.schools.index') }}" class="hover:text-primary-600">Schools</a>
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="text-gray-900 font-medium">Add school</span>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white shadow-sm p-6">
        <form action="{{ route('dashboard.schools.store') }}" method="post" class="space-y-4">
            @csrf
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 @error('name') border-red-500 @enderror">
                @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="flex gap-3">
                <button type="submit" class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium text-white bg-primary-600 hover:bg-primary-700">Create</button>
                <a href="{{ route('dashboard.schools.index') }}" class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
