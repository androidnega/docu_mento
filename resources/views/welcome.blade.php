@extends('layouts.public')

@section('title', 'Docu Mento – Academic & Final Year Project Management')

@section('content')
{{-- Mobile-first dedicated landing (xs–sm): full page, locked to viewport height, no scroll. --}}
<div class="block md:hidden h-[100svh] bg-[#050818] text-white overflow-hidden">
    <section class="relative flex flex-col h-[100svh] overflow-hidden">
        {{-- Background image + overlay for legibility --}}
        <div class="absolute inset-0">
            <img
                src="{{ asset('images/ttu_main_campus_oduro.png') }}"
                alt="Takoradi Technical University campus"
                class="h-full w-full object-cover opacity-20"
            >
        </div>
        <div class="absolute inset-0 bg-[#050818]/95"></div>
        {{-- Subtle sun flare accents --}}
        <div class="pointer-events-none absolute -top-28 -right-10 h-40 w-40 rounded-full bg-amber-300/30 blur-3xl"></div>
        <div class="pointer-events-none absolute -top-10 -right-6 h-24 w-24 rounded-full bg-amber-100/40 blur-2xl"></div>
        <div class="pointer-events-none absolute bottom-[-4rem] left-[-3rem] h-72 w-72 rounded-full bg-sky-400/10 blur-3xl"></div>

        <div class="relative flex flex-col flex-1">
            {{-- Compact sticky-style header (mobile) --}}
            <header class="text-slate-900">
                @include('layouts.partials.public-nav')
            </header>

            {{-- Fixed-height body (no scroll) --}}
            <main class="flex-1">
                <div class="dm-container px-4 pt-3 pb-3 h-full">
                    {{-- Hero image + copy (project overview hero) --}}
                    <section class="space-y-3 text-center">
                        {{-- Project overview hero illustration (first under header, with card background) --}}
                        <div class="mt-1 rounded-2xl bg-white/95 backdrop-blur-xl border border-white/20 shadow-xl overflow-hidden max-w-sm mx-auto">
                            <div class="relative">
                                <div class="absolute -top-10 -right-10 h-24 w-24 bg-amber-300/30 rounded-full blur-3xl"></div>
                                <div class="relative p-4 pb-5">
                                    <div class="flex items-center justify-between mb-3">
                                        <div class="text-[11px] font-medium text-slate-600 uppercase tracking-[0.18em]">
                                            Project overview
                                        </div>
                                        <div class="flex items-center gap-1.5 text-[10px] text-slate-400">
                                            <span class="inline-flex h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                                            <span>Live</span>
                                        </div>
                                    </div>
                                    <div class="aspect-[16/9] w-full overflow-hidden rounded-lg border border-slate-100 bg-slate-100 flex items-center justify-center">
                                        <img
                                            src="{{ asset('images/hero-documentor.png') }}"
                                            alt="Docu Mento project overview illustration"
                                            class="max-h-full max-w-full object-contain"
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>
                        {{-- Badge + headline and copy --}}
                        <div class="space-y-3">
                            <div class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-[11px] font-medium text-white/90">
                                <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-amber-400 text-[10px] font-bold text-slate-900">
                                    <i class="fas fa-graduation-cap"></i>
                                </span>
                                <span>Final year & academic project companion</span>
                            </div>
                            <div class="space-y-2">
                                <h1 class="text-2xl leading-tight font-bold tracking-tight">
                                    Keep every project
                                    <span class="text-amber-300">on track</span>
                                </h1>
                                <p class="text-sm text-white/85">
                                    A single workspace where coordinators, supervisors, and students plan topics,
                                    track milestones, and manage submissions — from proposal to final report.
                                </p>
                            </div>
                        </div>
                        {{-- Primary action under hero --}}
                        <div>
                            <a href="{{ route('student.account.login.form') }}"
                               class="dm-btn-primary w-full bg-amber-400 text-slate-900 hover:bg-amber-500 border-none text-sm py-2.5">
                                Student Login
                            </a>
                        </div>
                    </section>
                </div>
            </main>
        </div>
    </section>
</div>

{{-- Desktop & tablet hero (md+): original full-screen layout, untouched for larger screens. --}}
<div class="hidden md:flex h-screen overflow-hidden flex-col">
    <section
        class="relative flex-1 min-h-0 flex flex-col bg-cover bg-center bg-no-repeat text-white"
        style="background-image: url('{{ asset('images/ttu_main_campus_oduro.png') }}');">
        {{-- Deep blue overlay to keep text legible and match brand feel --}}
        <div class="absolute inset-0 bg-[#050818]/90"></div>
        {{-- Sun flare accents --}}
        <div class="pointer-events-none absolute -top-28 -right-10 h-52 w-52 rounded-full bg-amber-300/30 blur-3xl"></div>
        <div class="pointer-events-none absolute -top-10 -right-4 h-28 w-28 rounded-full bg-amber-100/40 blur-2xl"></div>
        <div class="pointer-events-none absolute bottom-[-5rem] right-[-4rem] h-80 w-80 rounded-full bg-sky-400/10 blur-3xl"></div>

        {{-- Header over hero --}}
        <header class="absolute top-0 left-0 right-0 z-20 bg-transparent text-slate-900">
            @include('layouts.partials.public-nav')
        </header>

        {{-- Two-column hero content --}}
        <div class="relative z-10 flex-1 flex items-center">
            <div class="dm-container w-full px-4 md:px-6 lg:px-8">
                <div class="pt-24 md:pt-28 lg:pt-32 pb-8 lg:pb-16">
                    <div class="flex flex-col-reverse lg:flex-row items-center lg:items-center gap-8 lg:gap-16">
                        {{-- Left: copy --}}
                        <div class="w-full flex flex-row items-start gap-4 lg:gap-8">
                            <div class="flex-1 max-w-xl text-center lg:text-left">
                                <div class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-white/90 mb-4">
                                    <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-amber-400 text-[10px] font-bold text-slate-900">
                                        <i class="fas fa-graduation-cap"></i>
                                    </span>
                                    <span>Final year & academic project companion</span>
                                </div>

                                <h1 class="text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-bold tracking-tight mb-4">
                                    Keep every project
                                    <span class="text-amber-300">on track</span>
                                </h1>

                                <p class="text-sm sm:text-base md:text-lg text-white/85 mb-6 md:mb-8">
                                    Docu Mento brings coordinators, supervisors, and students into a single space
                                    for allocating topics, tracking milestones, and managing submissions — from
                                    proposal to final report.
                                </p>

                                <div class="flex flex-col sm:flex-row gap-3 justify-center lg:justify-start items-center">
                                    <a href="{{ route('student.account.login.form') }}"
                                       class="dm-btn-primary min-w-[170px] bg-amber-400 text-slate-900 hover:bg-amber-500 border-none">
                                        Student Login
                                    </a>
                                    <a href="{{ route('about-system') }}"
                                       class="dm-btn-secondary min-w-[170px] border-white/50 text-white hover:border-white">
                                        About the system
                                    </a>
                                </div>

                                <div class="mt-5 md:mt-6 flex flex-col sm:flex-row items-center gap-3 text-xs sm:text-sm text-white/75 justify-center lg:justify-start">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-amber-400 text-slate-900">
                                            <i class="fas fa-check text-xs"></i>
                                        </span>
                                        <span>Structured workflow for every project</span>
                                    </div>
                                    <div class="hidden sm:inline-block h-4 w-px bg-white/20"></div>
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-amber-400 text-slate-900">
                                            <i class="fas fa-bell text-xs"></i>
                                        </span>
                                        <span>Reminders for reviews & submissions</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Right: hero illustration (desktop only) --}}
                        <div class="hidden lg:block w-full max-w-lg mx-auto lg:mx-0">
                            <div class="relative">
                                <div class="absolute -top-10 -right-10 h-32 w-32 bg-amber-300/40 rounded-full blur-3xl"></div>
                                <div class="relative">
                                    <div class="aspect-square sm:aspect-[4/3] w-full flex items-center justify-center">
                                        <div class="w-full h-full max-w-md rounded-3xl bg-white/10 border border-white/20 backdrop-blur-sm flex items-center justify-center">
                                            <img
                                                src="{{ asset('images/hero-documentor.png') }}"
                                                alt="Docu Mento project overview illustration"
                                                class="max-h-[90%] max-w-[90%] object-contain"
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
