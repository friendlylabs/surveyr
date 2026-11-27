<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ App\Support\Locales::direction() }}" @if ($clear) class="pc-public-clear" @endif>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @if ($noindex)<meta name="robots" content="noindex">@endif
    <title>{{ $title ?: config('app.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/svg+xml" href="{{ asset('assets/img/favicon.svg') }}">

    @if ($followTheme)
        <x-theme-boot />
    @endif

    @fonts
    @vite(['resources/css/app.css'])

    @stack('head')
</head>
<body class="pc-public">
    {{ $slot }}

    {{-- the current language's strings for pc.t() (pc-core.js); page scripts go in @push('body') --}}
    <script>window.pcStrings = @json(App\Support\Locales::strings(), JSON_FORCE_OBJECT);</script>
    <script src="@asset('assets/js/pc-core.js')" defer></script>

    @stack('body')
</body>
</html>
