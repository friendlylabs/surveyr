{{--
    Applies the saved light / dark / system theme to <html> before first paint (see public/assets/js/pc-theme.js).
    Put it in every layout's <head>, ahead of the stylesheets: <x-theme-boot />
--}}
<script>(function(d){var t;try{t=localStorage.getItem("pc-theme")}catch(e){}if(t!=="light"&&t!=="dark")t=matchMedia("(prefers-color-scheme: dark)").matches?"dark":"light";d.dataset.pcTheme=t})(document.documentElement)</script>
