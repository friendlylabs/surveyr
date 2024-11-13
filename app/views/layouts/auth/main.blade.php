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
		<title>{{ _env('APP_NAME') }} {{ $title }}</title>
        
		<script src="/assets/vendors/simplebar/simplebar.min.js"></script>
		<script src="/assets/js/config.js"></script>
        
		<link rel="preconnect" href="https://fonts.googleapis.com/">
		<link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin="">
		<link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@300;400;600;700;800;900&amp;display=swap" rel="stylesheet">
        <link href="/assets/fonts/fontawesome/all.css" rel="stylesheet"/>

		<link href="/assets/vendors/simplebar/simplebar.min.css" rel="stylesheet">
		<link href="/assets/css/theme-rtl.css" type="text/css" rel="stylesheet" id="style-rtl">
		<link href="/assets/css/theme.min.css" type="text/css" rel="stylesheet" id="style-default">
		<link href="/assets/css/user-rtl.min.css" type="text/css" rel="stylesheet" id="user-style-rtl">
		<link href="/assets/css/user.min.css" type="text/css" rel="stylesheet" id="user-style-default">
		<link href="/assets/fonts/phosphor/duotone/style.css" rel="stylesheet"/>
        <link href="/assets/css/toast.min.css" rel="stylesheet">

        <script>
            var phoenixIsRTL = window.config.config.phoenixIsRTL;
            if (phoenixIsRTL) {
                var linkDefault = document.getElementById('style-default');
                var userLinkDefault = document.getElementById('user-style-default');
                linkDefault.setAttribute('disabled', true);
                userLinkDefault.setAttribute('disabled', true);
                document.querySelector('html').setAttribute('dir', 'rtl');
            } else {
                var linkRTL = document.getElementById('style-rtl');
                var userLinkRTL = document.getElementById('user-style-rtl');
                linkRTL.setAttribute('disabled', true);
                userLinkRTL.setAttribute('disabled', true);
            }
        </script>

        <style>     
            .card{
                border: unset;
                border-radius: 1.5rem;
                box-shadow: rgba(99, 99, 99, 0.2) 0px 2px 8px 0px;
            }
        </style>

        @stack('styles')

	</head>
	<body>

		@yield('content')
        
		<script src="/assets/vendors/popper/popper.min.js"></script>
		<script src="/assets/vendors/bootstrap/bootstrap.min.js"></script>
		<script src="/assets/vendors/anchorjs/anchor.min.js"></script>
		<script src="/assets/vendors/is/is.min.js"></script>
		<script src="/assets/vendors/lodash/lodash.min.js"></script>
		<script src="/assets/vendors/dayjs/dayjs.min.js"></script>
		<script src="/assets/js/phoenix.js"></script>
        <script src="/assets/js/jquery-3.7.1.min.js"></script>
        <script src="/assets/js/toast.min.js"></script>
		<script src="/assets/js/app.js"></script>
        @stack('scripts')

	</body>
</html>