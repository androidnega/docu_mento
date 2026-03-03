@extends('layouts.student-dashboard')

@section('title', 'Documents')
@php $dashboardTitle = 'Documents'; @endphp

@section('dashboard_content')
<div class="space-y-6">
    <header class="mb-4">
        <h1 class="text-xl font-semibold text-gray-800">Documents</h1>
        <p class="text-sm text-gray-500 mt-1">Upload and manage your documents.</p>
    </header>

    @if(session('success'))
        <div class="rounded-lg bg-green-50 border border-gray-100 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-lg bg-red-50 border border-gray-100 px-4 py-3 text-sm text-red-800">
            <ul class="list-disc list-inside space-y-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="bg-white rounded-lg border border-gray-100 shadow-sm p-5 sm:p-6">
        <h2 class="text-sm font-semibold text-gray-800 mb-4">Upload document</h2>
        <form action="{{ route('dashboard.documents.store') }}" method="post" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div>
                <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Title (optional)</label>
                <input type="text" name="title" id="title" value="{{ old('title') }}" placeholder="e.g. Project proposal" class="block w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
            </div>
            <div>
                <label for="file" class="block text-sm font-medium text-gray-700 mb-1">File (PDF, DOC, DOCX)</label>
                <input type="file" name="file" id="file" accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" class="block w-full text-sm text-gray-600 file:mr-2 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-amber-50 file:text-amber-800 hover:file:bg-amber-100">
                <p class="text-xs text-gray-500 mt-1">Max 5MB. PDF, DOC, DOCX only.</p>
            </div>
            <div class="flex justify-end">
                <button type="submit" class="inline-flex items-center justify-center px-4 py-2.5 rounded-lg text-sm font-medium bg-amber-500 text-white hover:bg-amber-600 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2">Upload</button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-lg border border-gray-100 shadow-sm p-5 sm:p-6">
        <h2 class="text-sm font-semibold text-gray-800 mb-4">Your documents</h2>
        @if($documents->isEmpty())
            <p class="text-sm text-gray-500">No documents uploaded yet.</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm text-left text-gray-700">
                    <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-3 py-2">Title</th>
                            <th class="px-3 py-2">Original file</th>
                            <th class="px-3 py-2">Size</th>
                            <th class="px-3 py-2">Uploaded at</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($documents as $doc)
                            <tr>
                                <td class="px-3 py-2">{{ $doc->title ?? '—' }}</td>
                                <td class="px-3 py-2">{{ $doc->original_name }}</td>
                                <td class="px-3 py-2">
                                    @if($doc->size)
                                        {{ number_format($doc->size / 1024, 1) }} KB
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-3 py-2">
                                    {{ $doc->created_at?->format('M j, Y H:i') ?? '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $documents->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

