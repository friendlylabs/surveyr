{{-- Empty state: what's missing and, in the slot, a line on why or what happens next (no buttons). icon = Font Awesome name (without fa-), rendered duotone --}}
@props(['icon' => 'tray', 'title'])

<div {{ $attributes->class('pc-empty') }}>
    <span class="pc-empty-icon"><i class="fa-duotone fa-{{ $icon }} text-[22px]" aria-hidden="true"></i></span>
    <div class="text-sm font-semibold text-pc-text">{{ $title }}</div>
    {{ $slot }}
</div>
