{{--
    A stat in an open grid (pc-stat-grid): icon, then the value, then its label and hint beneath (the value is never
    between two lines of text), no box. With `href` the whole stat is a link.
      <x-stat icon="users" tone="ink" label="Users" :value="128" :href="route('admin.users')" />
    icon: Font Awesome name without the fa- prefix, rendered duotone. tone: ink | success | warning | danger | brand.
    Slot: an optional hint under the label. Extra attributes (i.e data-* hooks) land on the card.
--}}
@props(['icon', 'label', 'value', 'tone' => 'ink', 'href' => null])

@php($tag = $href ? 'a' : 'div')

<{{ $tag }} @if ($href) href="{{ $href }}" @endif {{ $attributes->class('pc-stat') }}>
    <i class="fa-duotone fa-{{ $icon }} pc-stat-icon leading-none text-pc-{{ $tone }}" aria-hidden="true"></i>
    <span class="pc-stat-value" data-stat-value>{{ $value }}</span>
    <p class="pc-stat-label">{{ $label }}</p>
    @if ($slot->isNotEmpty())<span class="pc-stat-hint">{{ $slot }}</span>@endif
</{{ $tag }}>
