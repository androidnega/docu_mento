@extends('layouts.staff')

@section('title', 'Docu Mentor – Supervisor')

@section('staff_heading', 'Docu Mentor')

@section('staff_content')
    {{-- Flash (success/error) shown once via layouts.app toast --}}
    <div class="mt-2 md:mt-4">
        @yield('content')
    </div>
@endsection
