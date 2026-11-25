{{-- Initials avatar from a person's name; size = sm | lg for pc-avatar-sm / pc-avatar-lg --}}
@props(['name', 'size' => null])

@php
    $initials = collect(preg_split('/\s+/', trim($name)))->filter()->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
@endphp

<span {{ $attributes->class(['pc-avatar', "pc-avatar-$size" => $size]) }} aria-hidden="true">{{ $initials }}</span>
