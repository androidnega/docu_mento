@extends('layouts.dashboard')

@section('title', trim($__env->yieldContent('title')) !== '' ? trim($__env->yieldContent('title')) : 'Docu Mentor')
@section('dashboard_heading', 'Docu Mentor')

@section('dashboard_content')
<div class="mt-2 md:mt-4">
    @yield('content')
</div>
@endsection
