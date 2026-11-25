{{--
    A person's avatar: their generated picture (config/surveyr.php `avatars.url`, seeded with their public hash) over
    their initials, which show while it loads, when it fails, or when avatars are switched off. size = sm | lg.
      <x-avatar :user="$user" size="sm" />
      <x-avatar name="Northwind Traders" />          initials only, for anything that isn't a user
--}}
@props(['name' => null, 'user' => null, 'size' => null])

@php
    $name ??= $user?->name ?? '';
    $initials = collect(preg_split('/\s+/', trim($name)))->filter()->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
    $template = (string) config('surveyr.avatars.url');
    $src = $user?->hash && $template !== '' ? str_replace(':seed', rawurlencode($user->hash), $template) : null;
@endphp

<span {{ $attributes->class(['pc-avatar', "pc-avatar-$size" => $size]) }} aria-hidden="true">
    {{ $initials }}
    @if ($src)
        <img src="{{ $src }}" alt="" class="pc-avatar-img" loading="lazy" decoding="async" referrerpolicy="no-referrer" onload="this.parentElement.dataset.pcLoaded = ''" onerror="this.remove()">
    @endif
</span>
