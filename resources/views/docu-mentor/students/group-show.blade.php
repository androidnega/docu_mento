@extends('layouts.student-dashboard')

@section('title', 'Group: ' . $group->name)
@php $dashboardTitle = 'Group: ' . $group->name; @endphp

@section('dashboard_content')
<header class="mb-6">
    <div class="rounded-xl border border-amber-300 bg-amber-50 px-5 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-slate-900 tracking-tight">{{ $group->name }}</h1>
            <p class="text-sm text-slate-700 mt-1">
                Academic year:
                <span class="inline-flex items-center rounded-full bg-white/80 px-2.5 py-0.5 text-xs font-medium text-amber-700 border border-amber-200">
                    {{ $group->academicYear?->year ?? '—' }}
                </span>
            </p>
            @if($user->id === $group->leader_id)
            <p class="text-xs text-amber-800 mt-1">You are the group leader. Add members below.</p>
            @endif
        </div>
        @if($group->members->count())
        <div class="text-xs text-slate-700">
            <p class="font-medium">Members: {{ $group->members->count() }}</p>
        </div>
        @endif
    </div>
</header>

@if(session('success') || session('error') || session('info'))
<section class="mb-6" aria-label="Notice">
    <div class="rounded-xl border {{ session('error') ? 'border-red-200 bg-red-50' : 'border-emerald-200 bg-emerald-50/70' }} p-4">
        @if(session('success'))<p class="text-sm font-medium text-slate-800">{{ session('success') }}</p>@endif
        @if(session('error'))<p class="text-sm font-medium text-red-600">{{ session('error') }}</p>@endif
        @if(session('info'))<p class="text-sm text-slate-600">{{ session('info') }}</p>@endif
    </div>
</section>
@endif

@if($user->id === $group->leader_id)
<section class="mb-8">
    <div class="flex items-center justify-between mb-3">
        <h2 class="text-sm font-semibold text-slate-800 flex items-center gap-2">
            <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-amber-500 text-slate-900 text-xs"><i class="fas fa-user-plus"></i></span>
            <span>Add member</span>
        </h2>
        <button type="button" id="open-add-member-modal" class="inline-flex items-center gap-2 rounded-lg border border-amber-500 bg-amber-500 px-3 py-1.5 text-xs font-medium text-slate-900 hover:bg-amber-400">
            <i class="fas fa-plus text-xs"></i>
            <span>Add member</span>
        </button>
    </div>

    {{-- Add member modal --}}
    <div id="add-member-modal" class="fixed inset-0 z-30 flex items-center justify-center bg-black/40 px-4 {{ $errors->has('phone') ? '' : 'hidden' }}" role="dialog" aria-modal="true" aria-labelledby="add-member-title">
        <div class="w-full max-w-md rounded-2xl bg-white shadow-lg overflow-hidden">
            <div class="flex items-center justify-between px-5 py-3 border-b border-slate-100">
                <h3 id="add-member-title" class="text-sm font-semibold text-slate-800">Add member</h3>
                <button type="button" class="text-slate-400 hover:text-slate-600" aria-label="Close" data-close-add-member-modal>
                    <i class="fas fa-times text-sm"></i>
                </button>
            </div>
            <div class="p-5 sm:p-6">
                <form action="{{ route('dashboard.group.add-member') }}" method="post" class="space-y-4">
                    @csrf
                    <input type="hidden" name="group_id" value="{{ $group->id }}">
                    <div>
                        <label for="phone" class="block text-sm font-medium text-slate-800 mb-1.5">Phone number</label>
                        <input type="text" name="phone" id="phone" value="{{ old('phone') }}" required placeholder="e.g. 0241234567" maxlength="20" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-amber-400" autocomplete="tel">
                        @error('phone')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                        <p class="text-xs text-slate-500 mt-1">Enter the member’s phone number (used for their account).</p>
                    </div>
                    <div class="flex items-center justify-end gap-2 pt-1">
                        <button type="button" class="px-3 py-1.5 rounded-lg text-xs font-medium border border-slate-300 text-slate-600 hover:bg-slate-50" data-close-add-member-modal>Cancel</button>
                        <button type="submit" class="inline-flex items-center justify-center px-4 py-2 rounded-lg text-sm font-medium bg-amber-500 text-slate-900 hover:bg-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-1">
                            <i class="fas fa-plus mr-1 text-xs"></i>
                            Add member
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
@endif

<section class="mb-8">
    <h2 class="text-sm font-semibold text-slate-800 mb-3">Members</h2>
    <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 sm:px-5 sm:py-4">
        @if($group->members->isEmpty())
            <p class="py-2 text-sm text-slate-500 text-center">No members yet.</p>
        @else
            <ul class="divide-y divide-slate-100">
                @foreach($group->members as $m)
                <li class="flex items-center justify-between gap-3 py-3 first:pt-0 last:pb-0">
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-slate-800 truncate">{{ $m->name ?? $m->username }}</p>
                        @if($m->id === $group->leader_id)
                        <p class="mt-0.5 inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-amber-100 text-amber-800">
                            Leader
                        </p>
                        @endif
                    </div>
                    @if($user->id === $group->leader_id && !$group->project && $m->id !== $group->leader_id)
                    <form action="{{ route('dashboard.group.remove-member', [$group, $m]) }}" method="post" class="shrink-0" onsubmit="return confirm('Remove this member from the group?');">
                        @csrf
                        <button type="submit" class="text-xs font-medium text-red-600 hover:text-red-800">
                            Remove
                        </button>
                    </form>
                    @endif
                </li>
                @endforeach
            </ul>
        @endif
    </div>
</section>

@if($user->id === $group->leader_id)
<script>
(function () {
    var modal = document.getElementById('add-member-modal');
    var openBtn = document.getElementById('open-add-member-modal');
    var closeBtns = document.querySelectorAll('[data-close-add-member-modal]');

    function openModal() {
        if (!modal) return;
        modal.classList.remove('hidden');
    }
    function closeModal() {
        if (!modal) return;
        modal.classList.add('hidden');
    }

    if (openBtn) {
        openBtn.addEventListener('click', function () {
            openModal();
        });
    }
    closeBtns.forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            closeModal();
        });
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeModal();
        }
    });
})();
</script>
@endif
@endsection
