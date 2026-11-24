@extends('layouts.error')

@section('code', '403')
@section('title', __('Forbidden'))
@section('heading', __('Sorry... this one\'s off limits'))
@section('message', __('Your account doesn\'t have access to this page. If you think it should, ask an admin.'))
