@extends('layouts.public')

@section('title', 'Docu Mento – Academic & Final Year Project Management')

@section('content')
{{-- Single full-screen hero: no scroll. Header over hero with white text. --}}
<div class="h-screen overflow-hidden flex flex-col">
    <section class="relative flex-1 min-h-0 flex flex-col justify-center items-center text-center bg-cover bg-center bg-no-repeat" style="background-image: url('{{ asset('takoraditechnical university.jpg') }}');">
        <div class="absolute inset-0 dm-hero-overlay" aria-hidden="true"></div>

        {{-- Header over hero: white text so it’s visible on dark overlay --}}
        <header class="absolute top-0 left-0 right-0 z-10 border-b border-white/20">
            <div class="dm-container">
                <div class="flex h-14 md:h-16 items-center justify-between">
                    <a href="{{ route('student.landing') }}" class="text-white font-semibold text-lg tracking-tight no-underline hover:text-white">Docu Mento</a>
                    <nav class="flex items-center gap-4 md:gap-6">
                        <a href="{{ route('about-system') }}" class="text-sm font-medium text-white hover:text-white/90 no-underline">About</a>
                        @if(isset($student) && $student)
                            <a href="{{ route('dashboard') }}" class="dm-btn-primary text-sm py-2 px-4">Dashboard</a>
                        @else
                            <a href="{{ route('student.account.login.form') }}" class="dm-btn-primary text-sm py-2 px-4">Student Login</a>
                        @endif
                    </nav>
                </div>
            </div>
        </header>

        {{-- Centered hero content --}}
        <div class="relative z-10 px-4 dm-container">
            <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold text-white tracking-tight mb-3">Docu Mento</h1>
            <p class="text-xl md:text-2xl text-white/95 font-medium mb-2">Academic & Final Year Project Management System</p>
            <p class="text-base md:text-lg text-white/90 max-w-xl mx-auto mb-8">Manage schools, departments, projects, proposals, and submissions in one place—with optional AI reviews and SMS notifications.</p>
            <div class="flex flex-col sm:flex-row gap-3 justify-center items-center">
                <a href="{{ route('student.account.login.form') }}" class="dm-btn-primary min-w-[160px] bg-amber-400 text-gray-900 hover:bg-amber-500 border-none">Student Login</a>
                <a href="{{ route('about-system') }}" class="dm-btn-secondary min-w-[160px]">Explore Features</a>
            </div>
        </div>
    </section>
</div>
@endsection
