@extends('layouts.error')

@section('code', '429')
@section('title', __('Too many requests'))
@section('heading', __('Easy... slow down a little'))
@section('message', __('That\'s a lot of requests in a short time. Wait a minute, then try again.'))
