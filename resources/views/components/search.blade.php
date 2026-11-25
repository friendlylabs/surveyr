{{-- GET search box for list toolbars; keeps the page's other query filters (except the page number) --}}
@props(['name' => 'q', 'placeholder' => 'Search'])

<form method="GET" {{ $attributes->class('pc-input-group w-64 max-sm:w-full') }} role="search">
    @foreach (request()->except([$name, 'page']) as $key => $value)
        @if (is_scalar($value))
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endif
    @endforeach
    <i class="ph ph-magnifying-glass text-base" aria-hidden="true"></i>
    <input class="pc-input" type="search" name="{{ $name }}" value="{{ request($name) }}" placeholder="{{ $placeholder }}" aria-label="{{ $placeholder }}">
</form>
