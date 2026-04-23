<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Your name — Docu Mento</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600&display=swap" rel="stylesheet" />
    <style>
        body { font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif; }
    </style>
</head>
<body class="min-h-screen bg-white text-gray-900 antialiased">
    <div class="min-h-screen flex flex-col items-center justify-center px-4 py-12">
        <div class="w-full max-w-sm">
            <h1 class="text-lg font-semibold text-gray-900 text-center mb-1">Confirm your name</h1>
            <p class="text-sm text-gray-500 text-center mb-8">Enter your first and last name as they should appear on projects and supervisor lists.</p>

            @if ($errors->any())
                <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="post" action="{{ route('student.account.legal-name.store') }}" class="space-y-5">
                @csrf
                <div>
                    <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1.5">First name</label>
                    <input
                        type="text"
                        name="first_name"
                        id="first_name"
                        value="{{ $firstName }}"
                        required
                        autocomplete="given-name"
                        autofocus
                        class="w-full rounded-xl border border-gray-200 bg-white px-3 py-3 text-base text-gray-900 placeholder-gray-400 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                        placeholder="First name"
                    >
                </div>
                <div>
                    <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1.5">Last name</label>
                    <input
                        type="text"
                        name="last_name"
                        id="last_name"
                        value="{{ $lastName }}"
                        required
                        autocomplete="family-name"
                        class="w-full rounded-xl border border-gray-200 bg-white px-3 py-3 text-base text-gray-900 placeholder-gray-400 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                        placeholder="Last name"
                    >
                </div>
                <button type="submit" class="w-full rounded-xl bg-emerald-600 py-3 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                    Continue
                </button>
            </form>

            <form method="post" action="{{ route('student.account.logout') }}" class="mt-8 text-center">
                @csrf
                <button type="submit" class="text-sm text-gray-400 hover:text-gray-600 underline-offset-2 hover:underline">Sign out</button>
            </form>
        </div>
    </div>
</body>
</html>
