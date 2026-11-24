@extends('layouts.error')

@section('code', '401')
@section('title', __('Unauthorized'))
@section('heading', __('Hold on... sign in first'))
@section('message', __('You need to be signed in to see this page.'))

@section('actions')
    <a href="{{ route('auth.login') }}" class="pc-btn pc-btn-primary pc-btn-pill"><i class="ph ph-sign-in text-base" aria-hidden="true"></i>Sign in</a>
    <a href="{{ url('/') }}" class="pc-btn pc-btn-secondary pc-btn-pill"><i class="ph ph-house text-base" aria-hidden="true"></i>Go home</a>
@endsection
