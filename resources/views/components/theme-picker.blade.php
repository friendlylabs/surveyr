{{--
    Light / dark toggle (public/assets/js/pc-theme.js): one icon button showing a moon while the page is light and a sun
    while it is dark, and flipping to the other on click (on a dark OS with nothing saved, the first click goes light).
    The page must load pc-theme.js (the app and auth layouts do). Attributes (class, ...) land on the button.
      <x-theme-picker />
      <x-theme-picker solid />   solid icons, as the sidebar uses (elsewhere icons are regular)
--}}
@props(['solid' => false])

<button type="button" {{ $attributes->class('pc-btn pc-btn-ghost pc-btn-icon pc-btn-sm pc-theme-toggle') }} data-pc-trigger="theme-toggle" aria-label="{{ __('Switch theme') }}">
    @if ($solid)
        <i class="fa-solid fa-moon pc-theme-to-dark" aria-hidden="true"></i>
        <i class="fa-solid fa-sun pc-theme-to-light" aria-hidden="true"></i>
    @else
        <i class="fa-regular fa-moon pc-theme-to-dark text-lg" aria-hidden="true"></i>
        <i class="fa-regular fa-sun pc-theme-to-light text-lg" aria-hidden="true"></i>
    @endif
</button>
