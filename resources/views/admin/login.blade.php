@extends('layouts.app')

@section('title', 'Login')

@section('content')
<div class="min-h-screen flex items-center justify-center px-4 py-10 bg-[#f3f4f6]">
    <div class="relative w-full max-w-md">
        {{-- Card --}}
        <div class="w-full bg-white rounded-xl border border-gray-200 px-8 py-8">
            <form action="{{ route('login.post') }}" method="post" class="space-y-4">
                @csrf

                <div class="space-y-1">
                    <div class="flex items-center rounded-md bg-gray-100 px-3 py-2">
                        <span class="text-gray-400 mr-2">
                            <i class="fas fa-user"></i>
                        </span>
                        <input
                            type="text"
                            name="username"
                            id="username"
                            value="{{ old('username') }}"
                            required
                            autofocus
                            placeholder="Username"
                            class="flex-1 bg-transparent border-0 text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-0"
                        >
                    </div>
                    @error('username')
                        <p class="text-xs text-danger-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-1">
                    <div class="flex items-center rounded-md bg-gray-100 px-3 py-2">
                        <span class="text-gray-400 mr-2">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input
                            type="password"
                            name="password"
                            id="password"
                            required
                            placeholder="Password"
                            class="flex-1 bg-transparent border-0 text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-0"
                        >
                    </div>
                    @error('password')
                        <p class="text-xs text-danger-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <button
                    type="submit"
                    class="mt-2 w-full py-2.5 rounded-md bg-slate-900 text-white text-sm font-semibold tracking-wide hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900"
                >
                    LOGIN
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
