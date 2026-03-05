<div class="dm-container py-3 md:py-4">
    <div class="flex justify-center">
        <div class="flex w-full max-w-5xl items-center gap-4 px-4 py-2 rounded-full bg-white border border-slate-200 shadow-sm">
            {{-- Logo --}}
            <a href="{{ route('student.landing') }}"
               class="flex items-center gap-2 font-semibold text-base sm:text-lg tracking-tight no-underline text-slate-900">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-amber-400 text-slate-900 shrink-0">
                    <i class="fas fa-file-alt text-sm"></i>
                </span>
                <span class="whitespace-nowrap">Docu Mento</span>
            </a>

            {{-- Center nav (desktop only) --}}
            <nav class="hidden md:flex flex-1 items-center justify-center gap-6 text-sm text-slate-600">
                <a href="{{ url('/') }}" class="px-3 py-1 rounded-full hover:bg-amber-100 hover:text-slate-900 no-underline transition-colors">
                    Home
                </a>
                <a href="{{ route('about-system') }}" class="px-3 py-1 rounded-full hover:bg-amber-100 hover:text-slate-900 no-underline transition-colors">
                    About the system
                </a>
            </nav>

            {{-- Right actions --}}
            <div class="flex items-center gap-2">
                @if(isset($student) && $student)
                    <a href="{{ route('dashboard') }}"
                       class="inline-flex items-center justify-center rounded-full bg-slate-900 text-white text-xs sm:text-sm font-semibold px-4 py-2">
                        Dashboard
                    </a>
                @else
                    <a href="{{ route('student.account.login.form') }}"
                       class="inline-flex items-center justify-center rounded-full bg-slate-900 text-white text-xs sm:text-sm font-semibold px-4 py-2">
                        Student Login
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>

