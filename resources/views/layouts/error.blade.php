{{--
    Error pages (resources/views/errors/{code}.blade.php): the status code in large two-tone type, a heading with one
    italic word, a line saying what happened, and two actions. Each page fills the sections:
      code     the status code shown large (e.g. 404)
      title    the browser tab title
      heading  the headline; wrap one word in <em> for the italic accent
      message  one short line
      actions  optional: replaces the default Go home / Go back buttons
    Kept free of anything that needs a session, the database or a signed-in user, so it renders when those are what failed.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ App\Support\Locales::direction() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>@yield('title') | {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/svg+xml" href="{{ asset('assets/img/favicon.svg') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/img/apple-touch-icon.png') }}">

    <x-theme-boot />

    @fonts
    @vite(['resources/css/app.css'])
</head>
<body>
    <main class="pc-error">
        {{-- the big number is decoration; screen readers get "Error 404: Not found" before the heading --}}
        <div class="pc-error-code" aria-hidden="true">@yield('code')</div>
        <p class="sr-only">{{ __('Error') }} @yield('code'): @yield('title')</p>

        <h1 class="pc-error-heading">@yield('heading')</h1>
        <p class="pc-error-message">@yield('message')</p>

        <div class="pc-error-actions">
            @hasSection('actions')
                @yield('actions')
            @else
                <a href="{{ url('/') }}" class="pc-btn pc-btn-primary pc-btn-pill"><i class="fa-regular fa-house text-base" aria-hidden="true"></i>{{ __('Go home') }}</a>
                <button type="button" class="pc-btn pc-btn-secondary pc-btn-pill" onclick="history.length > 1 ? history.back() : (location.href = '{{ url('/') }}')"><i class="fa-regular fa-arrow-left text-base rtl:-scale-x-100" aria-hidden="true"></i>{{ __('Go back') }}</button>
            @endif
        </div>
    </main>

    {{-- the code's hover spotlight (app.css .pc-error-code): follow the pointer while it's over the digits; mouse and pen only --}}
    <script>
        (function () {
            var code = document.querySelector(".pc-error-code");
            if (!code || !matchMedia("(hover: hover) and (pointer: fine)").matches) return;
            code.addEventListener("pointermove", function (e) {
                var box = code.getBoundingClientRect();
                code.style.setProperty("--pc-x", e.clientX - box.left + "px");
                code.style.setProperty("--pc-y", e.clientY - box.top + "px");
                code.dataset.pcSpot = "";
            });
            code.addEventListener("pointerleave", function () {
                delete code.dataset.pcSpot;
            });
        })();
    </script>
</body>
</html>
