<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ App\Support\Locales::direction() }}">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        @if (session('toast'))<meta name="pc-toast" content="{{ session('toast') }}">@endif

        <title>{{ !empty($title) ? "$title | " . config('app.name') : config('app.name') }}</title>
        <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
        <link rel="icon" type="image/svg+xml" href="{{ asset('assets/img/favicon.svg') }}">
        <link rel="apple-touch-icon" href="{{ asset('assets/img/apple-touch-icon.png') }}">

        <x-theme-boot />

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @stack('head')
    </head>
    <body>
        <x-page-loader />

        {{-- Split screen: the form on the left, the brand artwork on the right (a banner across the top on small screens) --}}
        <div class="pc-auth" style="--pc-auth-art: url('{{ asset('assets/img/auth-art.webp') }}')">
            <div class="pc-auth-art pc-auth-art-banner">
                <img src="{{ asset('assets/img/logo-dark.svg') }}" alt="" class="pc-auth-art-logo">
                <x-theme-picker class="pc-theme-toggle-glass" />
            </div>

            <main class="pc-auth-panel">
                <div class="pc-auth-head">
                    <a href="{{ route('auth.login') }}" class="pc-auth-brand" aria-label="{{ __(':app home', ['app' => config('app.name')]) }}">
                        <img src="{{ asset('assets/img/logo.svg') }}" alt="{{ config('app.name') }}" class="pc-auth-brand-light">
                        <img src="{{ asset('assets/img/logo-dark.svg') }}" alt="" class="pc-auth-brand-dark">
                    </a>
                    <x-theme-picker class="pc-theme-toggle-ring ms-auto" />
                </div>
                <div class="pc-auth-body">
                    {{ $slot }}
                </div>
            </main>

            <div class="pc-auth-art pc-auth-art-side" aria-hidden="true"></div>
        </div>

        {{-- the current language's strings for pc.t() (pc-core.js) --}}

        <script>window.pcStrings = @json(App\Support\Locales::strings(), JSON_FORCE_OBJECT);</script>

        <script src="@asset('assets/js/pc-core.js')" defer></script>
        <script src="@asset('assets/js/pc-theme.js')" defer></script>
        <script src="@asset('assets/js/pc-toast.js')" defer></script>
        <script src="@asset('assets/js/pc-dialog.js')" defer></script>
        <script src="@asset('assets/js/pc-password.js')" defer></script>
        <script src="@asset('assets/js/app.js')" defer></script>
        <script src="@asset('assets/js/pages/auth.js')" defer></script>

        @stack('body')
    </body>
</html>
