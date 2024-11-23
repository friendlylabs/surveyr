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
        <link href="/assets/fonts/inter/inter.css" rel="stylesheet"/>
		<link href="/assets/fonts/phosphor/duotone/style.css" rel="stylesheet"/>
        <link href="/assets/fonts/fontawesome/all.css" rel="stylesheet"/>

		<link href="/assets/vendors/simplebar/simplebar.min.css" rel="stylesheet">
		<link href="/assets/css/theme.min.css" type="text/css" rel="stylesheet" id="style-default">
		<link href="/assets/css/user.min.css" type="text/css" rel="stylesheet" id="user-style-default">
        <link href="/assets/css/toast.min.css" rel="stylesheet">
        <link href="/assets/css/app.css" rel="stylesheet">

        <link href="/assets/vendors/leaflet/leaflet.css" rel="stylesheet">
        <link href="/assets/vendors/leaflet.markercluster/MarkerCluster.css" rel="stylesheet">
        <link href="/assets/vendors/leaflet.markercluster/MarkerCluster.Default.css" rel="stylesheet">
        <link href="/vendor/select/bootstrap-select.min.css" rel="stylesheet">

        @stack('styles')

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
        <div id="preloader">
            <div class="d-grid">
                <div class="d-grid align-items-center justify-content-center">
                    <span class="loader"></span>
                </div>
            </div>
        </div>

        <main class="main" id="top">
            @include('layouts.app.partials.topbar')
		    @yield('content')
        </main>

        @include('layouts.app.partials.search')
        
		<script src="/assets/vendors/popper/popper.min.js"></script>
		<script src="/assets/vendors/bootstrap/bootstrap.min.js"></script>
		<script src="/assets/vendors/anchorjs/anchor.min.js"></script>
		<script src="/assets/vendors/is/is.min.js"></script>
		<script src="/assets/vendors/lodash/lodash.min.js"></script>
		<script src="/assets/vendors/list.js/list.min.js"></script>
		<script src="/assets/vendors/dayjs/dayjs.min.js"></script>        
        <script src="/assets/vendors/leaflet/leaflet.js"></script>
        <script src="/assets/vendors/leaflet.markercluster/leaflet.markercluster.js"></script>
        <script src="/assets/vendors/leaflet.tilelayer.colorfilter/leaflet-tilelayer-colorfilter.min.js"></script>
		<script src="/assets/js/phoenix.js"></script>
        <script src="/assets/js/jquery-3.7.1.min.js"></script>
        <script src="/assets/js/toast.min.js"></script>
        <script src="/vendor/select/bootstrap-select.min.js"></script>
        <script src="/vendor/swal/sweetalert.min.js"></script>

        <script src="/assets/js/layout.js"></script>
		<script src="/assets/js/app.js"></script>
        

        @stack('scripts')

        <script>
            document.addEventListener("DOMContentLoaded", function() {
                setTimeout(() => {
                    $('#preloader').fadeOut('slow');
                }, 500);
            });
        </script>

	</body>
</html>