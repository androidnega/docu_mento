@extends('layouts.app')

@section('title', 'Account Setup – Docu Mento')
@section('body_class', 'bg-gray-50')

@section('content')
<div class="min-h-[100dvh] min-h-screen flex items-center justify-center px-4 py-8">
    <div class="max-w-md w-full">
        <div class="bg-white border border-gray-100 rounded-lg p-6 sm:p-8 shadow-sm">
            <div class="flex items-center gap-3 mb-6">
                <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-500 text-white"><i class="fas fa-user-plus text-sm"></i></span>
                <div>
                    <h1 class="text-xl font-semibold text-gray-800">Account setup</h1>
                    <p class="text-sm text-gray-500 mt-0.5">Complete your profile</p>
                </div>
            </div>
            <p class="text-sm text-gray-500 mb-6">All fields are required. Your phone must be the one linked to index <strong class="text-gray-800">{{ $index_number }}</strong>.</p>

            @if(session('error'))
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">{{ session('error') }}</div>
            @endif

            <form action="{{ route('student.account.setup.store') }}" method="post" class="space-y-4">
                @csrf
                <div>
                    <label for="student_name" class="block text-sm font-medium text-gray-700 mb-1">Full name <span class="text-red-600">*</span></label>
                    <input type="text" id="student_name" name="student_name" value="{{ old('student_name', $student->student_name ?? '') }}" required maxlength="255" placeholder="e.g. Jane Doe" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg bg-white text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500" autocomplete="name" style="text-transform: capitalize;">
                    @error('student_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="phone_contact" class="block text-sm font-medium text-gray-700 mb-1">Phone number <span class="text-red-600">*</span></label>
                    <input type="tel" id="phone_contact" name="phone_contact" value="{{ old('phone_contact', $student->phone_contact ?? '') }}" required placeholder="e.g. 0244123456 or 233244123456" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg bg-white text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500" autocomplete="tel">
                    @error('phone_contact')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password <span class="text-red-600">*</span></label>
                    <input type="password" id="password" name="password" required minlength="8" placeholder="At least 8 characters" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg bg-white text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500" autocomplete="new-password">
                    @error('password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirm password <span class="text-red-600">*</span></label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required minlength="8" placeholder="Same as above" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg bg-white text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500" autocomplete="new-password">
                </div>
                <button type="submit" class="w-full py-2.5 px-4 text-sm font-semibold rounded-lg text-white bg-amber-500 hover:bg-amber-600 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 transition-colors">Complete setup and sign in</button>
            </form>
        </div>
        <p class="text-center mt-6">
            <a href="{{ route('student.account.login.form') }}" class="text-sm text-gray-500 hover:text-gray-800 no-underline">← Back to login</a>
        </p>
    </div>
</div>
@endsection
