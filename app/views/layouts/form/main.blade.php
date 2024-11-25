<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $title ?? 'App' }}</title>

    <link href="/assets/css/toast.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/vendor/surveyjs/defaultV2.min.css">
    <script src="/vendor/surveyjs/survey.core.min.js"></script>
    <script src="/vendor/surveyjs/survey-js-ui.min.js"></script>

    <script src="/vendor/surveyjs/themes/index.min.js"></script>
    <link href="/assets/fonts/fontawesome/all.css" rel="stylesheet"/>

    @stack('styles')
    <style>
        body{margin: 0; padding: 0; font-family: Arial, sans-serif;}
        .recordBtn { height: 35px; width: 35px; background: #F8BBD0; border: none; border-radius: 4px; cursor: pointer; }
        .saveRecord { height: 35px; width: 35px; background: #C8E6C9; border: none; border-radius: 4px; cursor: pointer; }

        .saveRecord i { color: green; }
        .recordBtn i { color: crimson; }

        .recordBtn:hover { background: #F48FB1; }
        .saveRecord:hover { background: #A5D6A7; }
    </style>

    <link rel="manifest" href="/manifest.json">
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/assets/js/service-worker.js').then(function(registration) {
                    console.log('ServiceWorker registration successful with scope: ', registration.scope);
                }, function(err) {
                    console.log('ServiceWorker registration failed: ', err);
                });
            });
        }
    </script>
</head>
<body>
    @yield('content')

    <script> const csrf_token = "{{ csrf_token() }}"; </script>
    <script src="/assets/js/jquery-3.7.1.min.js"></script>
    <script src="/assets/js/toast.min.js"></script>

    @stack('scripts')
</body>
</html>