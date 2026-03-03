@extends('layouts.student-dashboard')

@section('title', 'Class Results')
@php $dashboardTitle = 'Class Results'; @endphp

@section('dashboard_content')
<header class="mb-6">
    <h1 class="text-xl font-semibold text-slate-800 tracking-tight">Class results</h1>
    <p class="text-sm text-slate-500 mt-1">As a class rep, you can preview or download exam results (PDF, Excel, CSV) for your class.</p>
</header>

<section class="mb-8">
    <h2 class="text-sm font-medium text-slate-700 mb-3">Class results</h2>
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-8 text-center">
            <span class="w-12 h-12 rounded-lg bg-slate-100 flex items-center justify-center text-slate-500 mx-auto"><i class="fas fa-file-alt"></i></span>
            <h3 class="text-sm font-medium text-slate-800 mt-3">No class results yet</h3>
            <p class="text-sm text-slate-500 mt-1 max-w-sm mx-auto">Results for your class groups will appear here when available.</p>
        </div>
    </div>
</section>
@endsection
