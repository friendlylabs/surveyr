<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $title }}</title>

    <link href="/assets/css/iziToast.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/vendor/surveyjs/defaultV2.min.css">
    <script src="/vendor/surveyjs/survey.core.min.js"></script>
    <script src="/vendor/surveyjs/survey-js-ui.min.js"></script>

    <!-- (Optional) Predefined theme configurations -->
    <script src="/vendor/surveyjs/themes/index.min.js"></script>
    @stack('styles')

    <style>
        body{margin: 0; padding: 0; font-family: Arial, sans-serif;}
        
    </style>
</head>
<body>
    @yield('content')

    <script> const csrf_token = "{{ csrf_token() }}"; </script>
    <script src="/assets/js/jquery-3.7.1.min.js"></script>
    <script src="/assets/js/iziToast.min.js"></script>

    @stack('scripts')
</body>
</html>