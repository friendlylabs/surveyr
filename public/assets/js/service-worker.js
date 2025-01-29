const CACHE_NAME = 'surveyr-cache-v2';
const CACHE_ASSETS = [
    "/assets/css/app.css",
    "/assets/css/theme-rtl.css",
    "/assets/css/theme.min.css",
    "/assets/css/toast.min.css",
    "/assets/css/user-rtl.min.css",
    "/assets/css/user.min.css",
    "/assets/fonts/fontawesome/all.css",
    "/assets/fonts/fontawesome/fa-brands-400.eot",
    "/assets/fonts/fontawesome/fa-brands-400.svg",
    "/assets/fonts/fontawesome/fa-brands-400.ttf",
    "/assets/fonts/fontawesome/fa-brands-400.woff",
    "/assets/fonts/fontawesome/fa-brands-400.woff2",
    "/assets/fonts/fontawesome/fa-brands-400d41d.eot",
    "/assets/fonts/fontawesome/fa-duotone-900.ttf",
    "/assets/fonts/fontawesome/fa-duotone-900.woff2",
    "/assets/fonts/fontawesome/fa-light-300.ttf",
    "/assets/fonts/fontawesome/fa-light-300.woff2",
    "/assets/fonts/fontawesome/fa-regular-400.eot",
    "/assets/fonts/fontawesome/fa-regular-400.svg",
    "/assets/fonts/fontawesome/fa-regular-400.ttf",
    "/assets/fonts/fontawesome/fa-regular-400.woff",
    "/assets/fonts/fontawesome/fa-regular-400.woff2",
    "/assets/fonts/fontawesome/fa-regular-400d41d.eot",
    "/assets/fonts/fontawesome/fa-sharp-light-300.woff2",
    "/assets/fonts/fontawesome/fa-sharp-regular-400.woff2",
    "/assets/fonts/fontawesome/fa-sharp-solid-900.woff2",
    "/assets/fonts/fontawesome/fa-sharp-thin-100.woff2",
    "/assets/fonts/fontawesome/fa-solid-900.eot",
    "/assets/fonts/fontawesome/fa-solid-900.svg",
    "/assets/fonts/fontawesome/fa-solid-900.ttf",
    "/assets/fonts/fontawesome/fa-solid-900.woff",
    "/assets/fonts/fontawesome/fa-solid-900.woff2",
    "/assets/fonts/fontawesome/fa-solid-900d41d.eot",
    "/assets/fonts/fontawesome/fa-thin-100.ttf",
    "/assets/fonts/fontawesome/fa-thin-100.woff2",
    "/assets/fonts/fontawesome.css",
    "/assets/fonts/inter/Inter-italic.var32a8.woff2",
    "/assets/fonts/inter/Inter-roman.var32a8.woff2",
    "/assets/fonts/inter/inter.css",
    "/assets/fonts/phosphor/duotone/Phosphor-Duotone.svg",
    "/assets/fonts/phosphor/duotone/Phosphor-Duotone.ttf",
    "/assets/fonts/phosphor/duotone/Phosphor-Duotone.woff",
    "/assets/fonts/phosphor/duotone/style.css",
    "/assets/images/brand/favicon.ico",
    "/assets/images/brand/icon.png",
    "/assets/images/brand/icon.svg",
    "/assets/images/brand/logo-dark.png",
    "/assets/images/brand/logo-light.png",
    "/assets/images/brand/logo-sm.png",
    "/assets/images/brand/logo.png",
    "/assets/images/favicon.ico",
    "/assets/images/templates/672cfed1e3222.png",
    "/assets/images/templates/672cfed1e515c.png",
    "/assets/images/templates/672cfed1e700a.png",
    "/assets/images/templates/672cfed1e9066.png",
    "/assets/images/templates/672cfed1ea257.png",
    "/assets/images/templates/672cfed1eb379.png",
    "/assets/images/templates/672cfed1ec438.png",
    "/assets/images/templates/672cfed1ed510.png",
    "/assets/images/templates/672cfed1eea95.png",
    "/assets/images/templates/672cfed1efc31.png",
    "/assets/images/templates/672cfed1f0a90.png",
    "/assets/images/templates/672cfed1f1497.png",
    "/assets/images/templates/672cfed1f1b73.png",
    "/assets/images/templates/672cfed1f22f6.png",
    "/assets/images/templates/672cfed1f29f3.png",
    "/assets/images/templates/672cfed1f3093.png",
    "/assets/images/templates/672cfed1f3760.png",
    "/assets/images/templates/672cfed1f3dc6.png",
    "/assets/images/templates/672cfed200308.png",
    "/assets/images/templates/672cfed200cf2.png",
    "/assets/images/templates/672cfed201694.png",
    "/assets/images/templates/672cfed201f14.png",
    "/assets/images/templates/672cfed202737.png",
    "/assets/images/templates/672cfed202f6f.png",
    "/assets/images/templates/672cfed2038da.png",
    "/assets/images/templates/672cfed20446b.png",
    "/assets/images/templates/672cfed204e9f.png",
    "/assets/images/templates/672cfed205870.png",
    "/assets/images/templates/672cfed206137.png",
    "/assets/images/templates/672cfed20698c.png",
    "/assets/images/templates/672cfed20728e.png",
    "/assets/images/themes/3d.png",
    "/assets/images/themes/borderless.png",
    "/assets/images/themes/contrast.png",
    "/assets/images/themes/default.png",
    "/assets/images/themes/double.png",
    "/assets/images/themes/flat.png",
    "/assets/images/themes/layered.png",
    "/assets/images/themes/plain.png",
    "/assets/images/themes/sharp.png",
    "/assets/images/themes/solid.png",
    "/assets/images/users/avatar.jpg",
    "/assets/images/vector/403-illustration.png",
    "/assets/images/vector/403.png",
    "/assets/images/vector/404-illustration.png",
    "/assets/images/vector/404.png",
    "/assets/images/vector/500-illustration.png",
    "/assets/images/vector/bird.png",
    "/assets/images/vector/dark_403-illustration.png",
    "/assets/images/vector/dark_403.png",
    "/assets/images/vector/dark_404-illustration.png",
    "/assets/images/vector/dark_404.png",
    "/assets/images/vector/dark_500-illustration.png",
    "/assets/images/vector/dark_500.png",
    "/assets/images/vector/gbg.jpg",
    "/assets/images/vector/teapot.svg",
    "/assets/images/vector/welcome_bg.png",
    "/assets/js/app.js",
    "/assets/js/calendar.js",
    "/assets/js/config.js",
    "/assets/js/jquery-3.7.1.min.js",
    "/assets/js/layout.js",
    "/assets/js/phoenix.js",
    "/assets/js/showcase.js",
    "/assets/js/toast.min.js",
    "/assets/pwa/android-launchericon-144-144.png",
    "/assets/pwa/android-launchericon-192-192.png",
    "/assets/pwa/android-launchericon-48-48.png",
    "/assets/pwa/android-launchericon-512-512.png",
    "/assets/pwa/android-launchericon-72-72.png",
    "/assets/pwa/android-launchericon-96-96.png",
    "/assets/vendors/anchorjs/anchor.min.js",
    "/assets/vendors/bootstrap/bootstrap.min.js",
    "/assets/vendors/dayjs/dayjs.min.js",
    "/assets/vendors/is/is.min.js",
    "/assets/vendors/leaflet/images/layers-2x.html",
    "/assets/vendors/leaflet/images/layers.png",
    "/assets/vendors/leaflet/images/marker-icon.html",
    "/assets/vendors/leaflet/leaflet.css",
    "/assets/vendors/leaflet/leaflet.js",
    "/assets/vendors/leaflet.markercluster/MarkerCluster.Default.css",
    "/assets/vendors/leaflet.markercluster/MarkerCluster.css",
    "/assets/vendors/leaflet.markercluster/leaflet.markercluster.js",
    "/assets/vendors/leaflet.tilelayer.colorfilter/leaflet-tilelayer-colorfilter.min.js",
    "/assets/vendors/list.js/list.min.js",
    "/assets/vendors/lodash/lodash.min.js",
    "/assets/vendors/popper/popper.min.js",
    "/assets/vendors/simplebar/simplebar.min.css",
    "/assets/vendors/simplebar/simplebar.min.js",
    "/vendor/ace/ace.min.js",
    "/vendor/ace/ext-language_tools.js",
    "/vendor/ace/ext-searchbox.min.js",
    "/vendor/echarts/echarts.min.js",
    "/vendor/flatpickr/flatpickr.min.css",
    "/vendor/flatpickr/flatpickr.min.js",
    "/vendor/qrcode/qrcode.js",
    "/vendor/qrcode/qrcode.min.js",
    "/vendor/select/bootstrap-select.min.css",
    "/vendor/select/bootstrap-select.min.js",
    "/vendor/splide/splide.min.css",
    "/vendor/splide/splide.min.js",
    "/vendor/surveyjs/bootstrap-integration.min.js",
    "/vendor/surveyjs/defaultV2.min.css",
    "/vendor/surveyjs/dev/survey-creator-js.js",
    "/vendor/surveyjs/plotly.min.js",
    "/vendor/surveyjs/survey-creator-core.min.css",
    "/vendor/surveyjs/survey-creator-core.min.js",
    "/vendor/surveyjs/survey-creator-js.min.js",
    "/vendor/surveyjs/survey-js-ui.min.js",
    "/vendor/surveyjs/survey.analytics.min.css",
    "/vendor/surveyjs/survey.analytics.min.js",
    "/vendor/surveyjs/survey.core.min.js",
    "/vendor/surveyjs/surveyjs-widgets.min.js",
    "/vendor/surveyjs/themes/index.min.js",
    "/vendor/surveyjs/plugins/survey.analytics.tabulator.min.css",
    "/vendor/surveyjs/plugins/survey.analytics.tabulator.min.js",
    "/vendor/swal/sweetalert.min.js",
    "/vendor/tabulator/tabulator.min.css",
    "/vendor/tabulator/tabulator.min.js",
    "/vendor/sheetjs/xlsx.full.min.js",
    "/vendor/simplemde/simplemde.min.css",
    "/vendor/simplemde/simplemde.min.js",
    "/assets/css/splash.css",
    "/offline.html"
];

// Install Service Worker and cache assets
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => {
      console.log('Service Worker: Caching files...');
      return cache.addAll(CACHE_ASSETS);
    }).catch(err => console.error('Error during caching:', err))
  );
});

// Activate Service Worker and clean up old caches
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cache => {
          if (cache !== CACHE_NAME) {
            console.log('Service Worker: Deleting old cache:', cache);
            return caches.delete(cache);
          }
        })
      );
    })
  );
});

// Intercept fetch requests and serve cached files
self.addEventListener('fetch', event => {
  event.respondWith(
    caches.match(event.request).then(response => {
      // Return cached response if available, otherwise fetch from the network
      return response || fetch(event.request).catch(() => {
        // If both fail, serve the offline fallback (if it's a navigational request)
        if (event.request.mode === 'navigate') {
          return caches.match('/offline.html');
        }
      });
    })
  );
});