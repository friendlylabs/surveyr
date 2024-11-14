<!DOCTYPE html>
<html lang="en-US" dir="ltr" data-navigation-type="default" data-navbar-horizontal-shape="default">
	<head>
		<meta charset="utf-8">
		<meta name="theme-color" content="#ffffff">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="csrf-token" content="{{ csrf_token() }}">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta name="msapplication-TileImage" content="/assets/img/favicons/mstile-150x150.png">

        <link rel="shortcut icon" href="/favicon.ico">
		<title>{{ _env('APP_NAME') }} {{ $title ?? 'App' }}</title>
        
		<script src="/assets/vendors/simplebar/simplebar.min.js"></script>
		<script src="/assets/js/config.js"></script>
        
		<link rel="preconnect" href="https://fonts.googleapis.com/">
		<link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin="">
		<link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@300;400;600;700;800;900&amp;display=swap" rel="stylesheet">
        <link href="/assets/fonts/fontawesome/all.css" rel="stylesheet"/>

		<link href="/assets/css/theme.min.css" type="text/css" rel="stylesheet" id="style-default">
		<link href="/assets/css/user.min.css" type="text/css" rel="stylesheet" id="user-style-default">

        <style>
            .error-container {
                position: relative;
                font-family: 'Arial', sans-serif;
            }

            .error-container .shadow {
                font-size: 11rem;
                font-weight: bold;
                color: rgba(33, 150, 243, 0.3); /* Light blue color with opacity */
                position: absolute;
                left: 10px;
                top: 10px;
                z-index: 1;
            }

            .error-container .main {
                font-size: 11rem;
                font-weight: bold;
                color: #2196f3; /* Solid blue color */
                position: relative;
                z-index: 2;
            }
        </style>

        @stack('styles')

	</head>
	<body>

		@yield('content')

		<script src="/assets/js/phoenix.js"></script>
		<script src="/assets/js/app.js"></script>
        @stack('scripts')

	</body>
</html>