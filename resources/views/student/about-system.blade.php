@extends('layouts.public')

@section('title', 'About Docu Mento')

@section('content')
{{-- Header: light background so dark text is visible --}}
<header class="bg-dm-card border-b border-dm-border sticky top-0 z-50">
    <div class="dm-container">
        <div class="flex h-14 md:h-16 items-center justify-between">
            <a href="{{ route('student.landing') }}" class="text-dm-primary font-semibold text-lg tracking-tight no-underline">Docu Mento</a>
            <nav class="flex items-center gap-4 md:gap-6">
                <a href="{{ route('about-system') }}" class="text-sm font-medium text-dm-primary no-underline">About</a>
                @if(isset($student) && $student)
                    <a href="{{ route('dashboard') }}" class="dm-btn-primary text-sm py-2 px-4">Dashboard</a>
                @else
                    <a href="{{ route('student.account.login.form') }}" class="text-sm font-medium text-dm-text hover:text-dm-primary no-underline">Student</a>
                    <a href="{{ route('login') }}" class="dm-btn-primary text-sm py-2 px-4">Login</a>
                @endif
            </nav>
        </div>
    </div>
</header>

<main>
    {{-- About --}}
    <section class="dm-section bg-dm-bg" aria-labelledby="about-heading">
        <div class="dm-container">
            <h1 id="about-heading" class="text-2xl md:text-3xl font-bold text-dm-primary text-center mb-6">About Docu Mento</h1>
            <p class="text-dm-text text-center max-w-3xl mx-auto leading-relaxed">
                Docu Mento is a dedicated platform for tertiary institutions to run final year and capstone projects. It connects schools, departments, and academic years with staff—coordinators and supervisors—and students. Groups form around projects, submit proposals and chapters, receive feedback, and optionally benefit from AI-assisted reviews and SMS alerts. The system is multi-institution, role-based, and built for a calm, professional workflow.
            </p>
        </div>
    </section>

    {{-- System Flow – 6 steps, 3 per row on desktop --}}
    <section class="dm-section bg-dm-bg" aria-labelledby="flow-heading">
        <div class="dm-container">
            <h2 id="flow-heading" class="text-2xl md:text-3xl font-bold text-dm-primary text-center mb-8">How It Works</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6">
                <div class="dm-card">
                    <h3 class="font-semibold text-dm-primary mb-1">Academic Structure</h3>
                    <p class="text-sm text-dm-text">Schools, departments, and academic years define the context for all projects and users.</p>
                </div>
                <div class="dm-card">
                    <h3 class="font-semibold text-dm-primary mb-1">Identity</h3>
                    <p class="text-sm text-dm-text">Staff (admins, coordinators, supervisors) and students with role-based access and optional OTP or passkey login.</p>
                </div>
                <div class="dm-card">
                    <h3 class="font-semibold text-dm-primary mb-1">Collaboration</h3>
                    <p class="text-sm text-dm-text">Project categories and groups with leaders; students and supervisors work together on defined projects.</p>
                </div>
                <div class="dm-card">
                    <h3 class="font-semibold text-dm-primary mb-1">Projects</h3>
                    <p class="text-sm text-dm-text">Supervisors, features, proposals, and chapter-based structure for a clear project lifecycle.</p>
                </div>
                <div class="dm-card">
                    <h3 class="font-semibold text-dm-primary mb-1">Documents</h3>
                    <p class="text-sm text-dm-text">Proposals, chapters, submissions, and comments—versioned and traceable.</p>
                </div>
                <div class="dm-card">
                    <h3 class="font-semibold text-dm-primary mb-1">AI & SMS</h3>
                    <p class="text-sm text-dm-text">Optional AI reviews on submissions and optional SMS notifications to keep everyone informed.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Features Grid – 6 cards, navy top border accent --}}
    <section id="features" class="dm-section bg-dm-bg" aria-labelledby="features-heading">
        <div class="dm-container">
            <h2 id="features-heading" class="text-2xl md:text-3xl font-bold text-dm-primary text-center mb-8">Features</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6">
                <div class="dm-card dm-card-accent-top">
                    <h3 class="font-semibold text-dm-primary mb-1">Multi-institution</h3>
                    <p class="text-sm text-dm-text">Support for multiple institutions, each with its own schools, departments, and academic setup.</p>
                </div>
                <div class="dm-card dm-card-accent-top">
                    <h3 class="font-semibold text-dm-primary mb-1">Role-based access</h3>
                    <p class="text-sm text-dm-text">Admins, coordinators, supervisors, and students see only what they need, with clear permissions.</p>
                </div>
                <div class="dm-card dm-card-accent-top">
                    <h3 class="font-semibold text-dm-primary mb-1">Project supervision</h3>
                    <p class="text-sm text-dm-text">Assign supervisors to projects, track workload, and manage approvals and feedback in one place.</p>
                </div>
                <div class="dm-card dm-card-accent-top">
                    <h3 class="font-semibold text-dm-primary mb-1">Proposal versioning</h3>
                    <p class="text-sm text-dm-text">Submit and track proposal versions with comments and approval workflows.</p>
                </div>
                <div class="dm-card dm-card-accent-top">
                    <h3 class="font-semibold text-dm-primary mb-1">Chapter submissions</h3>
                    <p class="text-sm text-dm-text">Structured chapters with submissions, deadlines, and supervisor feedback.</p>
                </div>
                <div class="dm-card dm-card-accent-top">
                    <h3 class="font-semibold text-dm-primary mb-1">AI reviews</h3>
                    <p class="text-sm text-dm-text">Optional AI-assisted review of submissions to support supervisors and improve quality.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- CTA – soft green background, navy button --}}
    <section class="dm-section bg-dm-secondary" aria-labelledby="cta-heading">
        <div class="dm-container text-center">
            <h2 id="cta-heading" class="text-2xl md:text-3xl font-bold text-white mb-4">Ready to get started?</h2>
            <p class="text-white/95 max-w-xl mx-auto mb-6">Sign in as staff or student and start managing your projects with Docu Mento.</p>
            <a href="{{ route('login') }}" class="dm-btn-primary bg-dm-primary hover:opacity-90">Go to Login</a>
        </div>
    </section>

    {{-- Footer – navy, white text --}}
    <footer class="bg-dm-primary text-white py-10 md:py-12">
        <div class="dm-container">
            <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="text-sm">
                    <span class="font-semibold">Docu Mento</span>
                    <span class="mx-2 text-white/70">·</span>
                    <span class="text-white/80">&copy; {{ date('Y') }}</span>
                </div>
                <nav class="flex flex-wrap items-center justify-center gap-4 md:gap-6 text-sm">
                    <a href="{{ route('student.landing') }}" class="text-white/90 hover:text-white no-underline">Home</a>
                    <a href="{{ route('about-system') }}" class="text-white/90 hover:text-white no-underline">About</a>
                    <a href="{{ route('login') }}" class="text-white/90 hover:text-white no-underline">Login</a>
                    <a href="{{ route('public.projects.index') }}" class="text-white/90 hover:text-white no-underline">Projects</a>
                </nav>
            </div>
            <p class="text-center md:text-left text-white/70 text-sm mt-6">Academic &amp; final year project management for institutions.</p>
        </div>
    </footer>
</main>
@endsection
