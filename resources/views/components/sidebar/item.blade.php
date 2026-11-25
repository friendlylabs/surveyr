{{--
    One sidebar link.
      route  route name; until that route exists the link opens the "under development" dialog
      icon   phosphor name without the prefix, rendered duotone (e.g. "scan" -> ph-duotone ph-scan)
      active route-name pattern that marks the item current (defaults to the route and its children)
      modal  id of a modal the item opens instead of following the route (e.g. a picker that chooses what to open); the route
             still marks it current
--}}
@props(['route', 'icon', 'label', 'active' => null, 'modal' => null])

@php
    $exists = Route::has($route);
    $current = $exists && request()->routeIs($active ?? [$route, "$route.*"]);
@endphp

<a href="{{ $exists && ! $modal ? route($route) : '#' }}"
   @class(['pc-nav-item', 'pc-nav-item-active' => $current])
   @if ($current) aria-current="page" @endif
   @if ($modal) data-pc-trigger="modal" data-pc-target="#{{ $modal }}" @elseif (! $exists) onclick="underDevelopment(event)" @endif
   data-pc-tooltip="{{ $label }}" data-pc-placement="right" data-pc-tooltip-only="collapsed">
    <span class="pc-nav-icon"><i class="ph-duotone ph-{{ $icon }} text-xl" aria-hidden="true"></i></span>
    <span class="pc-nav-label">{{ $label }}</span>
</a>
