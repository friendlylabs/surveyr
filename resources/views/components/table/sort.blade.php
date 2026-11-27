{{--
    A sortable column heading for x-table: a link that sorts the list on `column` (?sort=&dir=), newest / highest first on
    the first click, then flips; the list swaps in place (app.js). The current sort shows an arrow and aria-sort.
      <x-table.sort column="submitted" :label="__('Submitted')" :current="$sort" :direction="$direction" />
    Other attributes (class, ...) land on the <th>.
--}}
@props(['column', 'label', 'current' => null, 'direction' => 'desc'])

@php
    $active = $current === $column;
    $next = $active && $direction === 'desc' ? 'asc' : 'desc';
    $url = request()->fullUrlWithQuery(['sort' => $column, 'dir' => $next, 'page' => null, 'response' => null, 'filter' => null]);
@endphp

<th {{ $attributes }} @if ($active) aria-sort="{{ $direction === 'asc' ? 'ascending' : 'descending' }}" @endif>
    <a href="{{ $url }}" @class(['pc-sort', 'pc-sort-active' => $active]) title="{{ $label }}">
        <span class="truncate">{{ Str::limit($label, 40) }}</span>
        <i @class(['fa-regular text-[10px]', 'fa-arrow-up' => $active && $direction === 'asc', 'fa-arrow-down' => ! ($active && $direction === 'asc')]) aria-hidden="true"></i>
    </a>
</th>
