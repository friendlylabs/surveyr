@extends('layouts.error')

@section('code', '500')
@section('title', __('Server error'))
@section('heading', __('Well... something broke'))
@section('message', __('An error on our side stopped this page. It\'s been logged; try again in a moment.'))

@section('actions')
    <button type="button" class="pc-btn pc-btn-primary pc-btn-pill" onclick="location.reload()"><i class="fa-regular fa-arrow-rotate-right text-base" aria-hidden="true"></i>{{ __('Try again') }}</button>
    <a href="{{ url('/') }}" class="pc-btn pc-btn-secondary pc-btn-pill"><i class="fa-regular fa-house text-base" aria-hidden="true"></i>{{ __('Go home') }}</a>
@endsection
