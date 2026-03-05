@extends('layouts.app')
@section('title', 'Access denied')
@section('body_class', 'bg-offwhite')
@section('content')
@php
    $user = auth()->user();
@endphp
<div class="min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 sm:p-8 max-w-md w-full text-center">
        <h1 class="text-xl font-semibold text-slate-800 mb-2">You don’t have permission</h1>
        @if(!$user)
            <p class="text-slate-600 text-sm mb-6">
                Your session may have expired or you are not signed in. Please log in again to continue.
            </p>
            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <a href="{{ route('student.login.form') }}" class="inline-flex items-center justify-center px-4 py-2 rounded-lg text-sm font-medium bg-amber-500 text-black hover:bg-amber-600 focus:outline-none focus:ring-2 focus:ring-amber-600 focus:ring-offset-2">
                    Student sign in
                </a>
                <a href="{{ route('login') }}" class="inline-flex items-center justify-center px-4 py-2 rounded-lg text-sm font-medium bg-slate-800 text-white hover:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-800 focus:ring-offset-2">
                    Staff sign in
                </a>
            </div>
        @else
            <p class="text-slate-600 text-sm mb-4">
                You’re signed in, but your role doesn’t allow you to access this page or perform this action.
            </p>
            <p class="text-slate-500 text-xs mb-6">
                This usually means the page is reserved for a different role (for example, coordinator vs supervisor vs student).
                If you think you should have access, please contact your system administrator or coordinator.
            </p>
            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center px-4 py-2 rounded-lg text-sm font-medium bg-slate-800 text-white hover:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-800 focus:ring-offset-2">
                    Back to dashboard
                </a>
            </div>
        @endif
    </div>
</div>
@endsection

