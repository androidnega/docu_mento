@extends('layouts.student-dashboard')

@section('title', 'Materials')
@php $dashboardTitle = 'Materials'; @endphp

@section('dashboard_content')
<header class="mb-6">
    <h1 class="text-xl font-semibold text-gray-800">Materials</h1>
    <p class="text-sm text-gray-500 mt-1">
        Docu Mento focuses on project management and document submissions.
    </p>
</header>

<section aria-label="Materials info">
    <div class="bg-white rounded-lg border border-gray-100 shadow-sm p-5 sm:p-6">
        <p class="text-sm text-gray-700">
            Use your existing channels (LMS, email, or printed handouts)
            for weekly files and notes from your supervisors.
        </p>
        <p class="text-sm text-gray-700 mt-4">
            You can upload and manage your project documents under
            <a href="{{ route('dashboard.documents.index') }}" class="text-amber-700 font-semibold hover:underline">Documents</a>.
        </p>
    </div>
</section>
@endsection
