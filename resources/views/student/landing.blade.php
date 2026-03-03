@extends('layouts.app')

@section('title', 'Docu Mento')
@section('body_class', 'landing-page')

@push('styles')
<style>
    body, .landing-wrap { background: #f8fafc; }
    .landing-header { background: #fff; border-bottom: 1px solid #e2e8f0; }
    .landing-container { width: 100%; max-width: 1200px; margin: 0 auto; padding: 0 1rem; }
    .landing-logo { font-size: 1.25rem; font-weight: 700; letter-spacing: -0.02em; display: inline-flex; align-items: center; gap: 0.375rem; text-decoration: none; }
    .landing-logo-mark { width: 1.75rem; height: 1.75rem; flex-shrink: 0; }
    .landing-logo-docu { color: #059669; }
    .landing-logo-mento { color: #1e40af; }
    .landing-nav-link { font-size: 0.875rem; font-weight: 500; color: #475569; text-decoration: none; transition: color 0.2s; }
    .landing-nav-link:hover { color: #1e40af; }
    .landing-cta { display: inline-block; padding: 0.5rem 1.25rem; background: #059669; color: #fff !important; font-weight: 600; font-size: 0.875rem; border-radius: 0.375rem; text-decoration: none; transition: background 0.2s; }
    .landing-cta:hover { background: #047857; }
    .landing-hero { width: 100%; display: block; max-height: 50vh; object-fit: cover; }
    .landing-tagline { text-align: center; font-size: 1rem; color: #64748b; margin: 0; padding: 1.5rem 1rem; }
    .landing-footer { border-top: 1px solid #e2e8f0; background: #fff; padding: 1rem; text-align: center; }
    .landing-footer p { margin: 0; font-size: 0.75rem; color: #94a3b8; }
    .landing-footer a { color: #64748b; text-decoration: none; }
    .landing-footer a:hover { color: #1e40af; }
    @media (max-width: 768px) {
        .landing-nav-desktop { display: none; }
        .landing-hero { max-height: 40vh; }
    }
    .landing-mobile-menu { display: none; }
    @media (max-width: 768px) {
        .landing-mobile-menu { display: flex; align-items: center; justify-content: center; width: 2.5rem; height: 2.5rem; border-radius: 0.375rem; color: #475569; background: transparent; border: none; }
        .landing-mobile-menu:hover { background: #f1f5f9; color: #1e40af; }
    }
    .landing-mobile-drawer { position: fixed; inset: 0; z-index: 50; display: none; }
    .landing-mobile-drawer.is-open { display: block; }
    .landing-mobile-drawer-overlay { position: absolute; inset: 0; background: rgba(0,0,0,0.4); }
    .landing-mobile-drawer-panel { position: absolute; left: 0; top: 0; bottom: 0; width: 16rem; max-width: 85vw; background: #fff; border-right: 1px solid #e2e8f0; padding: 1rem; }
    .landing-mobile-drawer a { display: block; padding: 0.75rem 1rem; color: #334155; text-decoration: none; border-radius: 0.375rem; font-weight: 500; }
    .landing-mobile-drawer a:hover { background: #f1f5f9; color: #1e40af; }
</style>
@endpush

@section('content')
<div class="landing-wrap min-h-screen flex flex-col">
    <header class="landing-header shrink-0">
        <div class="landing-container">
            <div class="flex h-14 items-center justify-between">
                <a href="{{ route('student.landing') }}" class="landing-logo">
                    <span class="landing-logo-mark" aria-hidden="true">
                        <svg viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full h-full">
                            <rect width="40" height="40" rx="8" fill="#059669"/>
                            <path d="M12 14h16v2H12v-2zm0 6h12v2H12v-2zm0 6h10v2H12v-2z" fill="#1e40af"/>
                        </svg>
                    </span>
                    <span class="landing-logo-docu">Docu</span><span class="landing-logo-mento"> Mento</span>
                </a>
                <button type="button" id="landing-menu-btn" class="landing-mobile-menu md:hidden" aria-label="Menu" aria-expanded="false">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <nav class="landing-nav-desktop flex items-center gap-6">
                    <a href="{{ route('about-system') }}" class="landing-nav-link">About</a>
                    <a href="{{ route('login') }}" class="landing-nav-link">Staff Login</a>
                    @if(isset($student) && $student)
                        <a href="{{ route('dashboard') }}" class="landing-cta">Dashboard</a>
                    @else
                        <a href="{{ route('student.account.login.form') }}" class="landing-cta">Student Login</a>
                    @endif
                </nav>
            </div>
        </div>
    </header>

    <div class="landing-hero-wrap flex-1">
        <img src="{{ e($landingHeroImage ?? asset('takoraditechnical university.jpg')) }}" alt="Takoradi Technical University" class="landing-hero" width="1200" height="600" loading="eager">
        <p class="landing-tagline">Project and document management for final year projects.</p>
    </div>

    <footer class="landing-footer shrink-0">
        <div class="landing-container">
            <p>&copy; {{ date('Y') }} Docu Mento · <a href="https://www.ausweblabs.com" target="_blank" rel="noopener noreferrer">ausweblabs</a></p>
        </div>
    </footer>
</div>

{{-- Mobile drawer --}}
<div id="landing-drawer" class="landing-mobile-drawer" aria-hidden="true">
    <div class="landing-mobile-drawer-overlay" id="landing-drawer-overlay"></div>
    <div class="landing-mobile-drawer-panel">
        <div class="flex justify-end mb-2"><button type="button" id="landing-drawer-close" class="p-2 text-slate-500 hover:text-slate-700" aria-label="Close">&times;</button></div>
        <a href="{{ route('student.landing') }}">Home</a>
        <a href="{{ route('about-system') }}">About</a>
        <a href="{{ route('login') }}">Staff Login</a>
        @if(isset($student) && $student)
            <a href="{{ route('dashboard') }}">Dashboard</a>
        @else
            <a href="{{ route('student.account.login.form') }}">Student Login</a>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
(function() {
    var btn = document.getElementById('landing-menu-btn');
    var drawer = document.getElementById('landing-drawer');
    var overlay = document.getElementById('landing-drawer-overlay');
    var closeBtn = document.getElementById('landing-drawer-close');
    if (!btn || !drawer) return;
    function openDrawer() {
        drawer.classList.add('is-open');
        drawer.setAttribute('aria-hidden', 'false');
        if (btn) btn.setAttribute('aria-expanded', 'true');
        document.body.style.overflow = 'hidden';
    }
    function closeDrawer() {
        drawer.classList.remove('is-open');
        drawer.setAttribute('aria-hidden', 'true');
        if (btn) btn.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
    }
    btn.addEventListener('click', openDrawer);
    if (overlay) overlay.addEventListener('click', closeDrawer);
    if (closeBtn) closeBtn.addEventListener('click', closeDrawer);
})();
</script>
@endpush
