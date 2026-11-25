{{-- Empty state: what's missing and, in the slot, what to do next. icon = phosphor name, rendered duotone --}}
@props(['icon' => 'tray', 'title'])

<div {{ $attributes->class('pc-empty') }}>
    <span class="pc-empty-icon"><i class="ph-duotone ph-{{ $icon }} text-[22px]" aria-hidden="true"></i></span>
    <div class="text-sm font-semibold text-pc-text">{{ $title }}</div>
    {{ $slot }}
</div>
