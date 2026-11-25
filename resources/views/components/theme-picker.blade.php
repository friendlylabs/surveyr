{{--
    Light / dark toggle (public/assets/js/pc-theme.js): one icon button showing a moon while the page is light and a sun
    while it is dark, and flipping to the other on click (on a dark OS with nothing saved, the first click goes light).
    The page must load pc-theme.js (the app and auth layouts do). Attributes (class, ...) land on the button.
      <x-theme-picker />
--}}
<button type="button" {{ $attributes->class('pc-btn pc-btn-ghost pc-btn-icon pc-btn-sm pc-theme-toggle') }} data-pc-trigger="theme-toggle" aria-label="Switch theme">
    <i class="ph ph-moon pc-theme-to-dark text-lg" aria-hidden="true"></i>
    <i class="ph ph-sun pc-theme-to-light text-lg" aria-hidden="true"></i>
</button>
