@extends('layouts.app')

@section('title', 'Your name — Docu Mento')

@section('body_class', 'bg-gradient-to-b from-primary-50/80 via-offwhite to-offwhite min-h-screen')

@section('content')
<div class="min-h-[100dvh] flex flex-col items-center justify-center px-4 py-10 sm:py-14 pb-[max(2.5rem,env(safe-area-inset-bottom))]">
    <div class="w-full max-w-md">
        <div class="rounded-2xl border border-primary-100/80 bg-white/95 shadow-lg shadow-primary-900/5 backdrop-blur-sm overflow-hidden">
            <div class="px-6 pt-8 pb-2 text-center">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-primary-600 text-white shadow-md shadow-primary-600/25">
                    <i class="fas fa-id-card text-xl" aria-hidden="true"></i>
                </div>
                <h1 class="font-display text-xl sm:text-2xl font-bold text-gray-900 tracking-tight">Confirm your name</h1>
                <p class="mt-2 text-sm text-gray-600 leading-relaxed">Enter your first and last name as they should appear on projects and supervisor lists. Letters only — no numbers (do not use your index number).</p>
            </div>

            <div class="px-6 pb-6 sm:px-8 sm:pb-8">
                @if ($errors->any())
                    <div class="mb-5 rounded-xl border border-danger-200 bg-danger-50 px-4 py-3 text-sm text-danger-800" role="alert">
                        <span class="font-medium">Could not save.</span>
                        <span class="block mt-0.5">{{ $errors->first() }}</span>
                    </div>
                @endif

                <form method="post" action="{{ route('student.account.legal-name.store') }}" class="space-y-5">
                    @csrf
                    <div>
                        <label for="first_name" class="block text-sm font-semibold text-gray-800 mb-1.5">First name</label>
                        <input
                            type="text"
                            name="first_name"
                            id="first_name"
                            value="{{ $firstName }}"
                            required
                            autocomplete="given-name"
                            autofocus
                            class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-base text-gray-900 placeholder-gray-400 shadow-sm transition focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/25"
                            placeholder="First name"
                        >
                    </div>
                    <div>
                        <label for="last_name" class="block text-sm font-semibold text-gray-800 mb-1.5">Last name</label>
                        <input
                            type="text"
                            name="last_name"
                            id="last_name"
                            value="{{ $lastName }}"
                            required
                            autocomplete="family-name"
                            class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-base text-gray-900 placeholder-gray-400 shadow-sm transition focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/25"
                            placeholder="Last name"
                        >
                    </div>
                    <button type="submit" class="w-full rounded-xl bg-primary-600 py-3.5 text-sm font-bold text-white shadow-md shadow-primary-600/20 transition hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 focus:ring-offset-white">
                        Continue to dashboard
                    </button>
                </form>

                <div class="mt-8 pt-6 border-t border-gray-100 text-center">
                    <form method="post" action="{{ route('student.account.logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="text-sm font-medium text-gray-500 hover:text-gray-800 transition underline-offset-4 hover:underline">
                            Sign out
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <p class="mt-6 text-center text-xs text-gray-500">
            Docu Mento · one-time profile step
        </p>
    </div>
</div>
@endsection
