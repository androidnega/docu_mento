@extends('layouts.staff')

@section('title', 'Add student')
@section('staff_heading', 'Add student')

@section('staff_content')
<div class="w-full space-y-6">
    <div class="mb-4 text-sm text-gray-600">
        <a href="{{ route('admin.students.index') }}" class="hover:text-primary-600">← Students</a>
    </div>
    <div class="rounded-lg border border-gray-200 bg-white p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Add student index</h2>
        <form action="{{ route('admin.students.store') }}" method="post" class="space-y-4">
            @csrf
            <div>
                <label for="index_number" class="block text-sm font-medium text-gray-700 mb-1">Index number</label>
                <input type="text" name="index_number" id="index_number" value="{{ old('index_number') }}" required maxlength="64" class="input w-full">
                @error('index_number')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="student_name" class="block text-sm font-medium text-gray-700 mb-1">Student name (optional)</label>
                <input type="text" name="student_name" id="student_name" value="{{ old('student_name') }}" maxlength="255" class="input w-full">
                @error('student_name')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
            </div>
            <div class="flex gap-3">
                <button type="submit" class="btn btn-primary">Add student</button>
                <a href="{{ route('admin.students.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
