@extends('layouts.student-dashboard')

@section('title', 'Name your group')
@php $dashboardTitle = 'Name your group'; @endphp

@section('dashboard_content')
<header class="mb-6">
    <h1 class="text-xl font-semibold text-slate-800 tracking-tight">Name your group</h1>
    <p class="text-sm text-slate-500 mt-1">Choose your own name or use a suggestion, then add your first member by phone.</p>
</header>

<section class="mb-8 w-full">
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 sm:p-5 w-full">
        @if(session('info'))
        <p class="mb-4 text-sm text-slate-500">{{ session('info') }}</p>
        @endif
        @if($errors->any())
        <ul class="mb-4 text-sm text-red-600 list-disc list-inside">
            @foreach($errors->all() as $e)
            <li>{{ $e }}</li>
            @endforeach
        </ul>
        @endif

        <form action="{{ route('dashboard.group.store') }}" method="post" class="space-y-6">
            @csrf
            <div>
                <label for="group_name" class="text-xs font-medium text-slate-500 uppercase tracking-wide block mb-2">Group name</label>
                <input type="text" name="group_name" id="group_name" value="{{ old('group_name') }}" required maxlength="120" placeholder="e.g. Chale Compiler" class="w-full rounded-lg border border-slate-300 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-400">
                @if(count($nameOptions) > 0)
                <p class="text-xs text-slate-500 mt-2 mb-1">Suggestions (click to use):</p>
                <div class="flex flex-wrap gap-2">
                    @foreach($nameOptions as $option)
                    <button type="button" data-name="{{ $option->display_name }}" class="group-name-suggestion inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 bg-slate-50 hover:bg-slate-100 text-sm text-slate-700 transition-colors">
                        <span aria-hidden="true">{{ $option->emoji ?? '✨' }}</span>
                        <span>{{ $option->display_name }}</span>
                    </button>
                    @endforeach
                </div>
                @endif
            </div>
            <div>
                <label for="phone" class="text-xs font-medium text-slate-500 uppercase tracking-wide block mb-1">Member phone number</label>
                <input type="text" name="phone" id="phone" value="{{ old('phone', $phone ?? ($pendingMember->phone ?? '')) }}" required placeholder="e.g. 0241234567" class="w-full rounded-lg border border-slate-300 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-400">
                @if($pendingMember)
                <p class="text-xs text-slate-500 mt-1">Adding: {{ $pendingMember->name ?? $pendingMember->username }}</p>
                @endif
            </div>
            <div class="flex flex-wrap gap-3">
                <button type="submit" class="inline-flex items-center justify-center px-4 py-2 rounded-lg text-sm font-medium bg-slate-600 text-white hover:bg-slate-700 min-h-[44px] sm:min-h-0">Create group & add member</button>
                <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center px-4 py-2 rounded-lg text-sm font-medium bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 min-h-[44px] sm:min-h-0">Cancel</a>
            </div>
        </form>
    </div>
</section>
@push('scripts')
<script>
document.querySelectorAll('.group-name-suggestion').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.getElementById('group_name').value = this.getAttribute('data-name');
    });
});
</script>
@endpush
@endsection
