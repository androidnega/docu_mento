@extends('layouts.app')

@section('title', $dashboardTitle ?? 'My Dashboard')
@section('body_class', 'bg-gray-50')
@section('body_extra_class', 'min-h-screen')

@section('content')
@php
    $isDashboardHome = request()->routeIs('dashboard') && !request()->routeIs('dashboard.my-*') && !request()->routeIs('dashboard.projects*') && !request()->routeIs('dashboard.public-projects') && !request()->routeIs('dashboard.group*') && !request()->routeIs('dashboard.documents*');
    $navDashboard = $isDashboardHome;
    $navGroup = request()->routeIs('dashboard.group.*');
    $navProjectShow = request()->routeIs('dashboard.projects.show');
    $navProjectsIndex = request()->routeIs('dashboard.projects.index') || request()->routeIs('dashboard.public-projects');
    $navProjects = $navProjectShow || $navProjectsIndex;
    $navProfile = request()->routeIs('dashboard.my-profile');
    $leaderProjectForNav = ($docuMentorGroup ?? null) && $docuMentorGroup->relationLoaded('project') ? $docuMentorGroup->project : null;
    $navActiveClass = 'bg-slate-100 text-slate-900';
    $navInactiveClass = 'bg-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900';
@endphp
<div class="min-h-screen flex bg-gray-50" id="student-dashboard-wrap">
    {{-- Top bar: Menu | Academic Year | Logout --}}
    <div class="fixed top-0 left-0 right-0 z-20 h-12 lg:h-14 flex items-center justify-between px-4 lg:pl-4 lg:pr-4 bg-white border-b border-gray-200 shadow-[0_1px_2px_rgba(0,0,0,0.05)] lg:left-56">
        <div class="flex items-center gap-3">
            {{-- Mobile: open sidebar --}}
            <button type="button" id="student-sidebar-open" class="lg:hidden flex items-center justify-center h-9 w-9 rounded-lg text-gray-600 hover:bg-gray-100" aria-label="Open menu">
                <i class="fas fa-bars"></i>
            </button>
            {{-- Desktop: collapse / expand sidebar --}}
            <button type="button" id="student-sidebar-toggle-lg" class="hidden lg:flex items-center justify-center h-9 w-9 rounded-lg text-gray-600 hover:bg-gray-100" aria-label="Toggle sidebar">
                <i class="fas fa-bars"></i>
            </button>
        </div>
        <div class="flex items-center gap-4 text-sm">
            @php $yearLabel = optional($academicYear ?? null)->year ?? optional($project ?? null)->academicYear?->year ?? null; @endphp
            @if($yearLabel)
                <span class="text-gray-500 hidden sm:inline">Academic Year: <strong class="text-gray-700">{{ $yearLabel }}</strong></span>
            @endif
            <form action="{{ (isset($student) && $student) ? route('student.account.logout') : route('logout') }}" method="post" class="hidden sm:block">
                @csrf
                <button type="submit" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50">
                    <i class="fas fa-sign-out-alt text-xs"></i>
                    <span>Log out</span>
                </button>
            </form>
        </div>
    </div>
    {{-- Sidebar overlay (mobile) --}}
    <div id="student-sidebar-overlay" class="fixed inset-0 z-30 bg-black/20 hidden lg:hidden" aria-hidden="true"></div>
    {{-- Left sidebar --}}
    <aside id="student-sidebar" class="fixed top-0 left-0 z-40 h-full w-56 bg-white border-r border-gray-200 flex flex-col shadow-[0_0_8px_rgba(0,0,0,0.06)] -translate-x-full lg:translate-x-0 transition-transform duration-200 ease-out">
        <div class="h-12 lg:h-14 flex items-center px-4 border-b border-gray-100 shrink-0">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2 no-underline text-gray-900">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-100 text-amber-700"><i class="fas fa-graduation-cap text-sm"></i></span>
                <span class="text-sm font-semibold text-gray-900">Docu Mento</span>
            </a>
        </div>
        <nav class="flex-1 py-4 px-3 space-y-0.5" aria-label="Main navigation">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ $navDashboard ? $navActiveClass : $navInactiveClass }}">
                <i class="fas fa-home w-4 text-center {{ $navDashboard ? 'text-slate-600' : 'text-gray-500' }}"></i>
                <span>Dashboard</span>
            </a>
            @if($docuMentorGroup ?? null)
            <a href="{{ route('dashboard.group.show', $docuMentorGroup) }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ $navGroup ? $navActiveClass : $navInactiveClass }}">
                <i class="fas fa-users w-4 text-center {{ $navGroup ? 'text-slate-600' : 'text-gray-500' }}"></i>
                <span>My Group</span>
            </a>
            @endif
            @if($hasProjectAccess ?? false)
            @if($leaderProjectForNav)
            <a href="{{ route('dashboard.projects.show', $leaderProjectForNav) }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ $navProjectShow ? $navActiveClass : $navInactiveClass }}">
                <i class="fas fa-folder-open w-4 text-center {{ $navProjectShow ? 'text-slate-600' : 'text-gray-500' }}"></i>
                <span>My Project</span>
            </a>
            <a href="{{ route('dashboard.projects.show', $leaderProjectForNav) }}#chapters" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ $navInactiveClass }}">
                <i class="fas fa-book text-gray-500 w-4 text-center"></i>
                <span>Chapters</span>
            </a>
            <a href="{{ route('dashboard.projects.show', $leaderProjectForNav) }}#chapters" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ $navInactiveClass }}">
                <i class="fas fa-upload text-gray-500 w-4 text-center"></i>
                <span>Submissions</span>
            </a>
            <a href="{{ route('dashboard.projects.show', $leaderProjectForNav) }}#proposals" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ $navInactiveClass }}">
                <i class="fas fa-comments text-gray-500 w-4 text-center"></i>
                <span>Messages</span>
            </a>
            @else
            <a href="{{ route('dashboard.projects.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ $navProjectsIndex ? $navActiveClass : $navInactiveClass }}">
                <i class="fas fa-folder-open w-4 text-center {{ $navProjectsIndex ? 'text-slate-600' : 'text-gray-500' }}"></i>
                <span>My Project</span>
            </a>
            @endif
            @endif
            {{-- Profile link removed from sidebar per UI spec --}}
        </nav>
        <div class="p-3 border-t border-gray-100 space-y-1">
            @if(isset($student) && $student)
            <p class="px-3 py-1.5 text-xs text-gray-500 truncate" title="{{ $student->display_name ?? '' }}">{{ $student->display_name ?? '' }}</p>
            <p class="px-3 py-0 text-xs font-mono text-gray-400 truncate">{{ $student->index_number ?? '' }}</p>
            @elseif(isset($user) && $user)
            <p class="px-3 py-1.5 text-xs text-gray-500 truncate">{{ $user->name ?? $user->username ?? '' }}</p>
            @if(!empty($user->index_number))
            <p class="px-3 py-0 text-xs font-mono text-gray-400 truncate">{{ $user->index_number }}</p>
            @endif
            @endif
        </div>
    </aside>
    {{-- Main content --}}
    <main id="student-main" class="flex-1 w-full min-w-0 pt-14 lg:pt-14 lg:pl-56 overflow-x-hidden bg-gray-50 pb-6 sm:pb-10">
        <div class="w-full min-w-0 px-4 py-6 sm:px-6 sm:py-6">
            @if(!request()->routeIs('dashboard') && !request()->routeIs('dashboard.projects.show'))
            <div class="mb-4">
                <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-500 hover:text-gray-800 no-underline">
                    <i class="fas fa-arrow-left text-xs"></i>
                    <span>Back to dashboard</span>
                </a>
            </div>
            @endif
            @yield('dashboard_content')
        </div>
    </main>
</div>
<script>
(function(){
    var openBtn = document.getElementById('student-sidebar-open');
    var toggleLgBtn = document.getElementById('student-sidebar-toggle-lg');
    var sidebar = document.getElementById('student-sidebar');
    var overlay = document.getElementById('student-sidebar-overlay');
    var main = document.getElementById('student-main');

    function openMobile(){
        if (!sidebar || !overlay) return;
        sidebar.classList.remove('-translate-x-full');
        overlay.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
    function closeMobile(){
        if (!sidebar || !overlay) return;
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('hidden');
        document.body.style.overflow = '';
    }

    if (openBtn){
        openBtn.addEventListener('click', openMobile);
    }
    if (overlay){
        overlay.addEventListener('click', closeMobile);
    }
    if (sidebar){
        document.addEventListener('keydown', function(e){
            if (e.key === 'Escape') closeMobile();
        });
    }

    // Desktop collapse / expand sidebar (lg and up)
    if (toggleLgBtn && sidebar && main){
        toggleLgBtn.addEventListener('click', function () {
            var expanded = sidebar.classList.contains('lg:translate-x-0');
            if (expanded) {
                sidebar.classList.remove('lg:translate-x-0');
                sidebar.classList.add('lg:-translate-x-full');
                main.classList.remove('lg:pl-56');
            } else {
                sidebar.classList.remove('lg:-translate-x-full');
                sidebar.classList.add('lg:translate-x-0');
                if (!main.classList.contains('lg:pl-56')) {
                    main.classList.add('lg:pl-56');
                }
            }
        });
    }
})();
</script>
{{-- Hidden utility to keep lg:-translate-x-full class in Tailwind build --}}
<span class="hidden lg:-translate-x-full"></span>
@if(isset($student) && $student && !empty($vapidPublicKey ?? null))
@push('scripts')
<script>
(function() {
    var vapidPublicKey = @json($vapidPublicKey);
    var subscribeUrl = @json(route('dashboard.push-subscribe'));
    var csrfToken = document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    function urlBase64ToUint8Array(base64String) {
        var padLen = (4 - base64String.length % 4) % 4;
        for (var p = 0; p < padLen; p++) base64String += '=';
        var base64 = base64String.replace(/-/g, '+').replace(/_/g, '/');
        var rawData = atob(base64);
        var outputArray = new Uint8Array(rawData.length);
        for (var i = 0; i < rawData.length; ++i) outputArray[i] = rawData.charCodeAt(i);
        return outputArray;
    }
    function subscribePush(registration) {
        if (!registration.pushManager || !vapidPublicKey) return Promise.resolve();
        return registration.pushManager.getSubscription().then(function(existing) { if (existing) return existing; return registration.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: urlBase64ToUint8Array(vapidPublicKey) }); })
        .then(function(subscription) {
            var payload = subscription.toJSON();
            if (!payload.endpoint || !payload.keys) return;
            var xhr = new XMLHttpRequest();
            xhr.open('POST', subscribeUrl, true);
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken || '');
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.send(JSON.stringify({ endpoint: payload.endpoint, keys: payload.keys }));
        }).catch(function(err) { console.warn('Push subscribe:', err); });
    }
    if ('serviceWorker' in navigator && 'PushManager' in window) {
        navigator.serviceWorker.register('{{ asset('sw.js') }}', { scope: '/' }).then(function(reg) {
            if (Notification.permission === 'granted') subscribePush(reg);
            else if (Notification.permission === 'default') Notification.requestPermission().then(function(p) { if (p === 'granted') subscribePush(reg); });
        }).catch(function(err) { console.warn('SW:', err); });
    }
})();
</script>
@endpush
@endif
@endsection
