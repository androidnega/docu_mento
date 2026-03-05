@extends('layouts.app')

@section('title', $dashboardTitle ?? 'Dashboard')
@section('body_class', 'bg-gray-100 dark:bg-slate-900 h-screen overflow-hidden')

@php
    $layoutAdminUser = auth()->user();
    $roleName = $layoutAdminUser && method_exists($layoutAdminUser, 'roleName') ? $layoutAdminUser->roleName() : '';
    $isSuperAdmin = $layoutAdminUser && $layoutAdminUser->role === \App\Models\User::ROLE_SUPER_ADMIN;
    $isSupervisor = $roleName === 'supervisor';
    $isCoordinatorOnly = $roleName === 'coordinator';
    $isDocuMentorCoordinator = in_array($roleName, ['coordinator', 'admin'], true);
    $isDocuMentorStudent = in_array($roleName, ['student', 'group_leader'], true);
    $isStudentProjectRoute = request()->routeIs('dashboard.projects.*') || request()->routeIs('dashboard.public-projects') || request()->routeIs('dashboard.group.*');
    $isStudentDashboardView = $isDocuMentorStudent || $isStudentProjectRoute;
@endphp
@section('content')
{{-- Admin color system: gray-100 page, gray-900 sidebar, amber accent only for active/highlights --}}
<style>
.staff-sidebar .staff-nav-link { color: rgb(209 213 219); border-left-color: transparent; }
.staff-sidebar .staff-nav-link:hover { background: rgb(31 41 55); color: white; }
.staff-sidebar .staff-nav-link:hover .staff-nav-icon { color: white; }
.staff-sidebar .staff-nav-link--active { background: rgb(31 41 55); border-left-color: transparent; color: white; }
.staff-sidebar .staff-nav-link--active .staff-nav-icon,
.staff-sidebar .staff-nav-link--active .staff-nav-text,
.staff-sidebar .staff-nav-link--active svg { color: white; }
.staff-sidebar .staff-nav-icon { color: rgb(156 163 175); transition: color 0.15s; }
</style>
<div class="staff-wrap flex h-screen bg-gray-100 dark:bg-slate-900 overflow-hidden">
    <div id="staff-overlay" class="staff-overlay fixed inset-0 z-30 bg-black/40 md:hidden hidden" aria-hidden="true"></div>

    <aside id="staff-sidebar" class="staff-sidebar flex h-full flex-col w-64 flex-shrink-0 bg-gray-900 border-r border-gray-800 shadow-sm" aria-label="Dashboard navigation" data-collapsed="false">
        <div class="staff-sidebar-inner flex flex-col h-full">
            <div class="staff-sidebar-header flex h-16 flex-shrink-0 items-center justify-between gap-2 px-4 border-b border-gray-800">
                <a href="{{ $isCoordinatorOnly ? route('dashboard') : route('dashboard') }}" class="staff-sidebar-brand flex min-w-0 flex-shrink-0 items-center gap-3 overflow-hidden transition-opacity hover:opacity-90">
                    @if($isCoordinatorOnly)
                        <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-amber-500 text-white font-bold text-lg shadow-sm">C</span>
                        <span class="staff-sidebar-brand-text truncate text-lg font-bold text-white">Coordinator</span>
                    @else
                        <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-amber-500 text-white shadow-sm">
                            <i class="fas fa-file-alt text-lg"></i>
                        </span>
                        <span class="staff-sidebar-brand-text truncate text-lg font-bold text-white">Docu Mento</span>
                    @endif
                </a>
                <button type="button" id="staff-sidebar-toggle-inner" data-staff-collapse class="staff-sidebar-chevron flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-800 hover:text-white transition-colors focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 focus:ring-offset-gray-900 md:flex" aria-label="Toggle sidebar" title="Toggle sidebar">
                    <svg class="h-5 w-5 md:h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
            </div>

            <nav class="staff-sidebar-nav flex-1 overflow-y-auto px-3 py-4 space-y-1">
                <ul class="space-y-1.5" role="list">
                    @if($isCoordinatorOnly)
                    @php
                        // Coordinator sidebar badge: pending project approvals for active academic year
                        try {
                            $activeYear = \App\Models\DocuMentor\AcademicYear::active();
                            $pendingQuery = \App\Models\DocuMentor\Project::query();
                            if ($activeYear) {
                                $pendingQuery->where('academic_year_id', $activeYear->id);
                            }
                            $coordinatorPendingApprovals = (clone $pendingQuery)->where('approved', false)->count();
                        } catch (\Throwable $e) {
                            $coordinatorPendingApprovals = 0;
                        }
                    @endphp
                    {{-- Coordinator sidebar: Academic Years = parent (cards); then Projects, Approvals, Deadlines, Reports, Profile --}}
                    <li>
                        <a href="{{ route('dashboard') }}" class="staff-nav-link {{ request()->routeIs('dashboard') && !request()->is('dashboard/coordinators/*') && !request()->routeIs('dashboard.profile.*') ? 'staff-nav-link--active' : '' }} group flex items-center gap-3 rounded-lg py-3 px-3 text-sm font-medium min-w-0 transition-all border-l-4">
                            <svg class="staff-nav-icon h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                            <span class="staff-nav-text truncate">Dashboard</span>
                        </a>
                    </li>
                    <li><a href="{{ route('dashboard.coordinators.academic-years.index') }}" class="staff-nav-link {{ request()->routeIs('dashboard.coordinators.academic-years.index') || request()->routeIs('dashboard.coordinators.academic-years.edit') || request()->routeIs('dashboard.coordinators.academic-years.create') ? 'staff-nav-link--active' : '' }} group flex items-center gap-3 rounded-lg py-3 px-3 text-sm font-medium min-w-0 transition-all border-l-4" title="Academic years list and management"><svg class="staff-nav-icon h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg><span class="staff-nav-text truncate">Academic Years</span></a></li>
                    <li><a href="{{ route('dashboard.coordinators.students.index') }}" class="staff-nav-link {{ request()->routeIs('dashboard.coordinators.students.index') || request()->routeIs('dashboard.coordinators.academic-years.students') ? 'staff-nav-link--active' : '' }} group flex items-center gap-3 rounded-lg py-3 px-3 text-sm font-medium min-w-0 transition-all border-l-4" title="Students by year"><svg class="staff-nav-icon h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg><span class="staff-nav-text truncate">Students</span></a></li>
                    <li><a href="{{ route('dashboard.coordinators.supervisors.index') }}" class="staff-nav-link {{ request()->routeIs('dashboard.coordinators.supervisors.index') ? 'staff-nav-link--active' : '' }} group flex items-center gap-3 rounded-lg py-3 px-3 text-sm font-medium min-w-0 transition-all border-l-4" title="Supervisors"><svg class="staff-nav-icon h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg><span class="staff-nav-text truncate">Supervisors</span></a></li>
                    <li><a href="{{ route('dashboard.coordinators.groups.index') }}" class="staff-nav-link {{ request()->routeIs('dashboard.coordinators.groups.*') ? 'staff-nav-link--active' : '' }} group flex items-center gap-3 rounded-lg py-3 px-3 text-sm font-medium min-w-0 transition-all border-l-4" title="Project groups"><svg class="staff-nav-icon h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-5.33-3.8M9 20H4v-2a4 4 0 015.33-3.8M12 12a4 4 0 100-8 4 4 0 000 8z"/></svg><span class="staff-nav-text truncate">Groups</span></a></li>
                    <li><a href="{{ route('dashboard.coordinators.projects.index') }}" class="staff-nav-link {{ request()->routeIs('dashboard.coordinators.projects.index') && !request()->get('pending') ? 'staff-nav-link--active' : '' }} group flex items-center gap-3 rounded-lg py-3 px-3 text-sm font-medium min-w-0 transition-all border-l-4"><svg class="staff-nav-icon h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg><span class="staff-nav-text truncate">Projects</span></a></li>
                    <li>
                        <a href="{{ route('dashboard.coordinators.projects.index', ['pending' => 1]) }}" class="staff-nav-link {{ request()->routeIs('dashboard.coordinators.projects.index') && request()->get('pending') ? 'staff-nav-link--active' : '' }} group flex items-center justify-between gap-3 rounded-lg py-3 px-3 text-sm font-medium min-w-0 transition-all border-l-4">
                            <span class="flex items-center gap-3 min-w-0">
                                <svg class="staff-nav-icon h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2m0 0l7 7 7-7M5 12l2-2m0 0l7-7 7 7"/>
                                </svg>
                                <span class="staff-nav-text truncate">Approvals</span>
                            </span>
                            @if(($coordinatorPendingApprovals ?? 0) > 0)
                                <span class="ml-2 inline-flex items-center justify-center h-6 min-w-[1.5rem] px-2 rounded-full bg-amber-400 text-slate-900 text-xs font-semibold tabular-nums">
                                    {{ $coordinatorPendingApprovals > 99 ? '99+' : $coordinatorPendingApprovals }}
                                </span>
                            @endif
                        </a>
                    </li>
                    <li><a href="{{ route('dashboard.coordinators.categories.index') }}" class="staff-nav-link {{ request()->routeIs('dashboard.coordinators.categories.*') ? 'staff-nav-link--active' : '' }} group flex items-center gap-3 rounded-lg py-3 px-3 text-sm font-medium min-w-0 transition-all border-l-4" title="Project categories"><svg class="staff-nav-icon h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h10M4 14h7M4 18h13"/></svg><span class="staff-nav-text truncate">Categories</span></a></li>
                    <li><a href="{{ route('dashboard.coordinators.export-report') }}" class="staff-nav-link {{ request()->routeIs('dashboard.coordinators.export-report*') ? 'staff-nav-link--active' : '' }} group flex items-center gap-3 rounded-lg py-3 px-3 text-sm font-medium min-w-0 transition-all border-l-4"><svg class="staff-nav-icon h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v2m0 4v2m0-6v2m6-16v2m0 4v2m0-6v2m-6 6h12m-6 0H3"/></svg><span class="staff-nav-text truncate">Reports</span></a></li>
                    <li><a href="{{ route('dashboard.profile.show') }}" class="staff-nav-link {{ request()->routeIs('dashboard.profile.*') ? 'staff-nav-link--active' : '' }} group flex items-center gap-3 rounded-lg py-3 px-3 text-sm font-medium min-w-0 transition-all border-l-4"><svg class="staff-nav-icon h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg><span class="staff-nav-text truncate">Profile</span></a></li>
                    @else
                    <li>
                        <a href="{{ route('dashboard') }}" class="staff-nav-link {{ (!$isCoordinatorOnly && request()->routeIs('dashboard') && !request()->is('dashboard/*')) ? 'staff-nav-link--active' : '' }} group flex items-center gap-3 rounded-lg py-3 px-3 text-sm font-medium min-w-0 transition-all border-l-4" title="Overview and quick links">
                            <svg class="staff-nav-icon h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                            <span class="staff-nav-text truncate">Dashboard</span>
                        </a>
                    </li>
                    @if($isSupervisor)
                    <li>
                        <a href="{{ route('dashboard.docu-mentor.projects.index') }}" class="staff-nav-link {{ request()->routeIs('dashboard.docu-mentor.projects.index') && !request()->get('pending') ? 'staff-nav-link--active' : '' }} group flex items-center gap-3 rounded-lg py-3 px-3 text-sm font-medium min-w-0 transition-all border-l-4" title="Assigned projects">
                            <svg class="staff-nav-icon h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <span class="staff-nav-text truncate">Assigned Projects</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('dashboard.docu-mentor.projects.index', ['pending' => 1]) }}" class="staff-nav-link {{ request()->routeIs('dashboard.docu-mentor.projects.index') && request()->get('pending') ? 'staff-nav-link--active' : '' }} group flex items-center gap-3 rounded-lg py-3 px-3 text-sm font-medium min-w-0 transition-all border-l-4" title="Pending reviews">
                            <svg class="staff-nav-icon h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span class="staff-nav-text truncate">Pending Reviews</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('dashboard.docu-mentor.projects.index') }}" class="staff-nav-link group flex items-center gap-3 rounded-lg py-3 px-3 text-sm font-medium min-w-0 transition-all border-l-4" title="Messages (project list)">
                            <svg class="staff-nav-icon h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                            <span class="staff-nav-text truncate">Messages</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('dashboard.profile.show') }}" class="staff-nav-link {{ request()->routeIs('dashboard.profile.*') ? 'staff-nav-link--active' : '' }} group flex items-center gap-3 rounded-lg py-3 px-3 text-sm font-medium min-w-0 transition-all border-l-4" title="Profile">
                            <svg class="staff-nav-icon h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            <span class="staff-nav-text truncate">Profile</span>
                        </a>
                    </li>
                    @elseif($isDocuMentorStudent)
                    <li>
                        <a href="{{ route('dashboard') }}" class="staff-nav-link {{ (request()->routeIs('dashboard.projects.*') || request()->routeIs('dashboard.public-projects') || request()->routeIs('dashboard.group.*')) ? 'staff-nav-link--active' : (request()->routeIs('dashboard.docu-mentor.*') ? 'staff-nav-link--active' : '') }} group flex items-center gap-3 rounded-lg py-3 px-3 text-sm font-medium min-w-0 transition-all border-l-4" title="Project">
                            <svg class="staff-nav-icon h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <span class="staff-nav-text truncate">Project</span>
                        </a>
                    </li>
                    @endif
                    @if($isDocuMentorStudent)
                    <li>
                        <a href="{{ route('dashboard.projects.index') }}" class="staff-nav-link {{ request()->routeIs('dashboard.projects.*') ? 'staff-nav-link--active' : '' }} group flex items-center gap-3 rounded-lg py-3 px-3 text-sm font-medium min-w-0 transition-all border-l-4"><svg class="staff-nav-icon h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg><span class="staff-nav-text truncate">My Projects</span></a>
                    </li>
                    <li>
                        <a href="{{ route('dashboard.public-projects') }}" class="staff-nav-link {{ request()->routeIs('dashboard.public-projects') ? 'staff-nav-link--active' : '' }} group flex items-center gap-3 rounded-lg py-3 px-3 text-sm font-medium min-w-0 transition-all border-l-4"><svg class="staff-nav-icon h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg><span class="staff-nav-text truncate">Public Projects</span></a>
                    </li>
                    @endif
                    @if($isSuperAdmin)
                    <li>
                        <a href="{{ route('dashboard.schools.index') }}" class="staff-nav-link {{ request()->routeIs('dashboard.schools.*') ? 'staff-nav-link--active' : '' }} group flex items-center gap-3 rounded-lg py-3 px-3 text-sm font-medium min-w-0 transition-all border-l-4" title="Manage schools and add departments">
                            <svg class="staff-nav-icon h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            <span class="staff-nav-text truncate">Schools &amp; departments</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('dashboard.users.index') }}" class="staff-nav-link {{ request()->routeIs('dashboard.users.*') ? 'staff-nav-link--active' : '' }} group flex items-center gap-3 rounded-lg py-3 px-3 text-sm font-medium min-w-0 transition-all border-l-4" title="Manage staff (Super Admin and supervisors)">
                            <svg class="staff-nav-icon h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            <span class="staff-nav-text truncate">Users</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('dashboard.settings.index') }}" class="staff-nav-link {{ request()->routeIs('dashboard.settings.*') ? 'staff-nav-link--active' : '' }} group flex items-center gap-3 rounded-lg py-3 px-3 text-sm font-medium min-w-0 transition-all border-l-4" title="Configure app, mail, AI, and Cloudinary">
                            <svg class="staff-nav-icon h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <span class="staff-nav-text truncate">Settings</span>
                        </a>
                    </li>
                    <li>
                        @php $isResetPage = request()->routeIs('dashboard.system.reset.*') || request()->routeIs('system.reset.*') || request()->is('dashboard/system/reset*'); @endphp
                        <a href="{{ route('dashboard.system.reset.index') }}" class="staff-nav-link {{ $isResetPage ? 'staff-nav-link--active' : '' }} group flex items-center gap-3 rounded-lg py-3 px-3 text-sm font-medium min-w-0 transition-all border-l-4" title="Clear data or full system reset (use with caution)">
                            <svg class="staff-nav-icon h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            <span class="staff-nav-text truncate">Reset</span>
                        </a>
                    </li>
                    @endif
                    @endif
                </ul>
            </nav>
            <div class="px-3 pb-4 border-t border-gray-800">
                <form action="{{ route('logout') }}" method="post" class="mt-3">
                    @csrf
                    <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-lg bg-gray-800 text-gray-100 text-sm font-medium py-2.5 hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 focus:ring-offset-gray-900">
                        <i class="fas fa-right-from-bracket text-xs"></i>
                        <span class="staff-nav-text">Log out</span>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <div class="staff-main flex flex-col flex-1 min-w-0 min-h-0">
        <header class="flex min-h-14 flex-shrink-0 items-stretch border-b border-gray-200 dark:border-slate-800 bg-white dark:bg-slate-900 z-10 min-w-0 safe-area-header">
            <div class="staff-page flex flex-1 flex-wrap items-center gap-2 sm:gap-3 w-full min-w-0 px-3 py-2 sm:px-4 md:px-6">
                <button type="button" id="staff-sidebar-menu-btn" class="flex h-11 w-11 min-h-[44px] min-w-[44px] flex-shrink-0 items-center justify-center rounded-lg text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-slate-800 hover:text-gray-900 dark:hover:text-white focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 touch-manipulation" aria-label="Open menu" title="Open menu" style="display: none;">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <h1 class="min-w-0 flex-1 truncate text-base sm:text-lg font-semibold text-gray-800 dark:text-slate-50">@yield('dashboard_heading', 'Dashboard')</h1>
                @php
                    $staffUser = auth()->user();
                    $isCoordinatorOrSupervisorOrAdmin = $staffUser && (
                        $staffUser->isDocuMentorSupervisor()
                        || $staffUser->role === \App\Models\User::DM_ROLE_COORDINATOR
                        || $staffUser->isSuperAdmin()
                    );
                    $showSmsInHeader = $staffUser && $staffUser->isDocuMentorCoordinator();
                    if ($showSmsInHeader) {
                        $staffUser->refresh();
                    }
                    $smsRemaining = $showSmsInHeader ? $staffUser->sms_remaining : 0;
                    $smsAllocation = $showSmsInHeader ? ($staffUser->sms_allocation ?? 0) : 0;
                    $smsBadgeClass = $smsRemaining >= 100 ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700';
                @endphp
                @if($isCoordinatorOrSupervisorOrAdmin)
                    @php
                        $aiTokenStatus = app(\App\Services\AiTokenService::class)->getStatus($staffUser);
                        $aiTokenBadgeClass = $aiTokenStatus['remaining'] > 0 ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700';
                    @endphp
                    <div class="flex flex-shrink-0 items-center gap-2 sm:gap-3 flex-wrap justify-end">
                        <div class="flex items-center gap-3 rounded-xl bg-white/80 dark:bg-slate-900 px-3 py-1.5 sm:px-3.5 sm:py-2 text-xs sm:text-sm font-medium text-slate-800 dark:text-amber-100 shadow-sm">
                            <div class="flex items-center gap-3">
                                @if($showSmsInHeader)
                                    <span class="inline-flex items-center gap-1.5">
                                        <span class="flex h-7 w-7 sm:h-8 sm:w-8 items-center justify-center rounded-full bg-amber-100 text-amber-600">
                                            <i class="fas fa-comment-sms text-[11px] sm:text-xs"></i>
                                        </span>
                                        <span class="font-semibold tabular-nums text-slate-800 dark:text-amber-100">
                                            SMS: {{ $smsRemaining }}
                                        </span>
                                    </span>
                                @endif
                                <span class="inline-flex items-center gap-1.5">
                                    <span class="flex h-7 w-7 sm:h-8 sm:w-8 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                                        <i class="fas fa-robot text-[11px] sm:text-xs"></i>
                                    </span>
                                    <span class="font-semibold tabular-nums text-slate-800 dark:text-amber-100">
                                        AI: {{ $aiTokenStatus['remaining'] }}
                                    </span>
                                </span>
                            </div>
                            <div class="flex items-center gap-2 pl-2 border-l border-slate-200 dark:border-slate-700">
                                <button type="button"
                                        id="staff-theme-toggle"
                                        class="inline-flex h-7 w-7 sm:h-8 sm:w-8 items-center justify-center rounded-full bg-slate-900 text-amber-300 text-xs sm:text-sm hover:bg-black focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 focus:ring-offset-white dark:focus:ring-offset-slate-900"
                                        title="Toggle dark mode">
                                    <i class="fas fa-moon" id="staff-theme-icon"></i>
                                </button>
                                <button type="button"
                                        id="staff-fullscreen-toggle"
                                        class="inline-flex h-7 w-7 sm:h-8 sm:w-8 items-center justify-center rounded-full bg-sky-100 dark:bg-sky-900 text-sky-600 dark:text-sky-200 text-xs sm:text-sm hover:bg-sky-200 dark:hover:bg-sky-800 focus:outline-none focus:ring-2 focus:ring-sky-400 focus:ring-offset-2 focus:ring-offset-white dark:focus:ring-offset-slate-900"
                                        title="Toggle full screen">
                                    <i class="fas fa-expand" id="staff-fullscreen-icon"></i>
                                </button>
                                <button type="button"
                                        id="staff-sidebar-collapse-toggle"
                                        class="inline-flex h-7 w-7 sm:h-8 sm:w-8 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900 text-emerald-600 dark:text-emerald-200 text-xs sm:text-sm hover:bg-emerald-200 dark:hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:ring-offset-2 focus:ring-offset-white dark:focus:ring-offset-slate-900"
                                        title="Collapse / expand sidebar">
                                    <i class="fas fa-table-columns" id="staff-sidebar-icon"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                @endif
                <div class="relative flex flex-shrink-0 items-center" id="profile-menu-wrap">
                    <button type="button" class="flex items-center gap-2 rounded-full pl-1 pr-2 py-0.5 hover:bg-gray-100 dark:hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 touch-manipulation" aria-expanded="false" aria-haspopup="true" id="profile-menu-btn" title="Profile">
                        @php $user = auth()->user(); @endphp
                        @if($user && $user->avatar_url)
                            <img src="{{ $user->avatar_url }}" alt="Profile" class="h-9 w-9 sm:h-9 sm:w-9 rounded-full object-cover flex-shrink-0 border border-gray-200 dark:border-slate-700" />
                        @else
                            <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-gray-200 dark:bg-slate-700 text-gray-600 dark:text-slate-100 text-base font-semibold leading-none border border-gray-200 dark:border-slate-700">{{ $user ? strtoupper(substr($user->name ?? $user->username ?? 'U', 0, 1)) : 'U' }}</span>
                        @endif
                        <svg class="h-4 w-4 flex-shrink-0 text-gray-500 dark:text-slate-300 hidden sm:block" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div id="profile-menu-dropdown" class="fixed inset-0 z-50 bg-black/40 sm:bg-transparent sm:absolute sm:inset-auto sm:right-0 sm:top-full hidden">
                        <div class="ml-auto mr-4 sm:mr-0 mt-20 sm:mt-1.5 w-full max-w-xs sm:max-w-none sm:w-48 md:w-56 rounded-lg border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-900 py-1 shadow-lg max-h-[80vh] overflow-y-auto overscroll-contain">
                            <a href="{{ route('dashboard.profile.show') }}" class="block px-4 py-3 sm:py-2.5 text-sm text-gray-700 dark:text-slate-100 hover:bg-gray-100 dark:hover:bg-slate-800 whitespace-nowrap touch-manipulation">Profile &amp; info</a>
                            <a href="{{ route('dashboard.profile.password') }}" class="block px-4 py-3 sm:py-2.5 text-sm text-gray-700 dark:text-slate-100 hover:bg-gray-100 dark:hover:bg-slate-800 whitespace-nowrap touch-manipulation">Reset password</a>
                            <form action="{{ route('logout') }}" method="post" class="border-t border-gray-100 dark:border-slate-700 mt-1">
                                @csrf
                                <button type="submit" class="block w-full px-4 py-3 sm:py-2.5 text-left text-sm font-medium text-gray-700 dark:text-slate-100 hover:bg-gray-100 dark:hover:bg-slate-800 whitespace-nowrap touch-manipulation">Log out</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <main class="staff-main-content flex-1 min-h-0 overflow-y-auto overflow-x-hidden bg-gray-100 dark:bg-slate-900 overscroll-behavior-y-contain">
            @php
                $fullBleedPage = request()->routeIs('dashboard.profile.*') || request()->routeIs('dashboard.system.reset.*') || request()->routeIs('system.reset.*') || request()->is('dashboard/system/reset*');
                $fullWidthFormPage = false;
                $isCoordinatorDashboardHome = $isCoordinatorOnly
                    && request()->routeIs('dashboard')
                    && !request()->is('dashboard/coordinators/*')
                    && !request()->routeIs('dashboard.class-groups.*')
                    && !request()->routeIs('dashboard.profile.*');
            @endphp
            <div class="staff-page w-full min-h-full max-w-full {{ $fullBleedPage ? 'p-0' : 'px-3 py-4 sm:px-4 sm:py-6 md:px-6 md:py-8 safe-area-main' }}">
                <div class="staff-dashboard-content w-full max-w-none overflow-x-hidden {{ $fullBleedPage ? 'px-0' : 'px-0 md:px-2' }}">
                    @if($isCoordinatorOnly && !$isCoordinatorDashboardHome && (request()->routeIs('dashboard') || request()->routeIs('dashboard.coordinators.*') || request()->routeIs('dashboard.class-groups.*') ||  request()->routeIs('dashboard.profile.*')))
                    <nav class="coordinator-breadcrumb flex items-center gap-2 text-sm text-gray-500 dark:text-slate-300 mb-4" aria-label="Breadcrumb">
                        <a href="{{ route('dashboard') }}" class="hover:text-gray-700 dark:hover:text-slate-50 transition-colors">Dashboard</a>
                        @hasSection('breadcrumb_trail')
                            <svg class="w-4 h-4 text-gray-400 dark:text-slate-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            @yield('breadcrumb_trail')
                        @else
                            @unless(request()->routeIs('dashboard') && !request()->is('dashboard/coordinators/*') && !request()->routeIs('dashboard.class-groups.*') && !request()->routeIs('dashboard.profile.*'))
                            <svg class="w-4 h-4 text-gray-400 dark:text-slate-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            <span class="text-gray-800 dark:text-slate-100 font-medium">@yield('dashboard_heading', 'Page')</span>
                            @endunless
                        @endif
                    </nav>
                    @endif
                    @yield('dashboard_content')
                </div>
            </div>
        </main>
    </div>
</div>
<script>
(function() {
    var KEY = 'dashboardSidebar';
    var sidebar = document.getElementById('staff-sidebar');
    var overlay = document.getElementById('staff-overlay');
    var menuBtn = document.getElementById('staff-sidebar-menu-btn');
    var toggleInner = document.getElementById('staff-sidebar-toggle-inner');
    if (!sidebar) return;
    var isDesktop = function() { return window.innerWidth >= 768; };
    var collapsed = localStorage.getItem(KEY) === 'collapsed';
    function updateMenuButton() {
        if (!menuBtn) return;
        var show = !isDesktop() || collapsed;
        menuBtn.style.setProperty('display', show ? 'flex' : 'none');
        menuBtn.setAttribute('aria-label', collapsed && isDesktop() ? 'Expand sidebar' : 'Open menu');
        menuBtn.setAttribute('title', collapsed && isDesktop() ? 'Expand sidebar' : 'Open menu');
    }
    function setCollapsed(c) {
        collapsed = c;
        localStorage.setItem(KEY, c ? 'collapsed' : 'expanded');
        sidebar.setAttribute('data-collapsed', c ? 'true' : 'false');
        sidebar.classList.toggle('staff-sidebar--collapsed', c);
        if (isDesktop()) { sidebar.style.width = c ? '4.5rem' : ''; sidebar.style.minWidth = c ? '4.5rem' : ''; } else { sidebar.style.width = ''; sidebar.style.minWidth = ''; }
        if (overlay) overlay.classList.toggle('hidden', c);
        if (toggleInner) { toggleInner.setAttribute('aria-label', c ? 'Expand sidebar' : 'Collapse sidebar'); toggleInner.setAttribute('title', c ? 'Expand sidebar' : 'Collapse sidebar'); }
        updateMenuButton();
    }
    function init() {
        if (isDesktop()) setCollapsed(collapsed); else setCollapsed(true);
        updateMenuButton();
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
    if (menuBtn) menuBtn.addEventListener('click', function(e) { e.preventDefault(); setCollapsed(false); });
    if (overlay) overlay.addEventListener('click', function() { setCollapsed(true); });
    document.addEventListener('click', function(e) {
        var collapseBtn = e.target && e.target.closest && e.target.closest('[data-staff-collapse]');
        if (collapseBtn) { e.preventDefault(); e.stopPropagation(); if (isDesktop()) setCollapsed(!collapsed); else setCollapsed(true); }
    }, true);
    /* On mobile: close sidebar when any nav link is clicked */
    var nav = sidebar && sidebar.querySelector('.staff-sidebar-nav');
    if (nav) nav.addEventListener('click', function(e) {
        var link = e.target && e.target.closest && e.target.closest('a[href]');
        if (link && link.getAttribute('href') && link.getAttribute('href') !== '#' && !isDesktop()) setCollapsed(true);
    });
    // Top-right sidebar collapse / expand button
    var topCollapseBtn = document.getElementById('staff-sidebar-collapse-toggle');
    if (topCollapseBtn) {
        topCollapseBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (isDesktop()) {
                setCollapsed(!collapsed);
            } else {
                // On mobile, toggle drawer visibility
                var isHidden = sidebar.classList.contains('staff-sidebar--collapsed') || collapsed;
                setCollapsed(!isHidden);
            }
        });
    }
    window.addEventListener('resize', function() {
        if (!isDesktop()) setCollapsed(true);
        updateMenuButton();
    });
    var profileBtn = document.getElementById('profile-menu-btn');
    var profileDropdown = document.getElementById('profile-menu-dropdown');
    var profileWrap = document.getElementById('profile-menu-wrap');
    if (profileBtn && profileDropdown) {
        profileBtn.addEventListener('click', function(e) { e.stopPropagation(); var open = !profileDropdown.classList.contains('hidden'); profileDropdown.classList.toggle('hidden', open); profileBtn.setAttribute('aria-expanded', !open); });
        document.addEventListener('click', function() { profileDropdown.classList.add('hidden'); profileBtn.setAttribute('aria-expanded', 'false'); });
        if (profileWrap) profileWrap.addEventListener('click', function(e) { e.stopPropagation(); });
    }
})();
</script>
@endsection
