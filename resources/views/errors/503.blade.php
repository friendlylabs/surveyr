@extends('layouts.error')

@section('code', '503')
@section('title', __('Down for maintenance'))
@section('heading', __('Back soon... tuning things up'))
@section('message', __('We\'re doing some maintenance right now. Check back in a few minutes.'))

@section('actions')
    <button type="button" class="pc-btn pc-btn-primary pc-btn-pill" onclick="location.reload()"><i class="fa-regular fa-arrow-rotate-right text-base" aria-hidden="true"></i>{{ __('Try again') }}</button>
@endsection
