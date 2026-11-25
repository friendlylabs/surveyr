{{--
    A stat in an open grid (pc-stat-grid): icon, label and value, no box. With `href` the whole stat is a link.
      <x-stat icon="users" tone="ink" label="Users" :value="128" :href="route('admin.users')" />
    icon: phosphor name without the prefix, rendered duotone. tone: ink | success | warning | danger | brand.
    Slot: an optional hint under the value. Extra attributes (i.e data-* hooks) land on the card.
--}}
@props(['icon', 'label', 'value', 'tone' => 'ink', 'href' => null])

@php($tag = $href ? 'a' : 'div')

<{{ $tag }} @if ($href) href="{{ $href }}" @endif {{ $attributes->class('pc-stat') }}>
    <i class="ph-duotone ph-{{ $icon }} pc-stat-icon leading-none text-pc-{{ $tone }}" aria-hidden="true"></i>
    <p class="pc-stat-label">{{ $label }}</p>
    <span class="pc-stat-value" data-stat-value>{{ $value }}</span>
    @if ($slot->isNotEmpty())<span class="pc-stat-hint">{{ $slot }}</span>@endif
</{{ $tag }}>
