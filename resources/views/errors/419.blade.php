@extends('layouts.error')

@section('code', '419')
@section('title', __('Page expired'))
@section('heading', __('Hmm... this page went stale'))
@section('message', __('It was open too long and your session timed out. Reload it and try again.'))

@section('actions')
    <button type="button" class="pc-btn pc-btn-primary pc-btn-pill" onclick="location.reload()"><i class="fa-regular fa-arrow-rotate-right text-base" aria-hidden="true"></i>{{ __('Reload page') }}</button>
    <a href="{{ url('/') }}" class="pc-btn pc-btn-secondary pc-btn-pill"><i class="fa-regular fa-house text-base" aria-hidden="true"></i>{{ __('Go home') }}</a>
@endsection
