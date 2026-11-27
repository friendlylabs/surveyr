{{--
    Data table for list pages, in Surveyr's listing style: the toolbar (search and filters) sits on the page above
    the table, detached from it; the table sits in a card with a 24px inset, heavy uppercase headings and roomy rows.
      <x-table :paginator="$users" label="Users">
          <x-slot:toolbar> filters (page actions go in the layout's header slot, beside the title) </x-slot:toolbar>
          <x-slot:head> <th>Name</th> ... </x-slot:head>
          <x-slot:empty> <x-empty ... /> </x-slot:empty>
          @foreach (...) <tr>...</tr> @endforeach
      </x-table>
    With a paginator, the toolbar and card together fill the viewport below the header: the head sticks, an empty page
    centres the `empty` slot, and the footer ("1 to 20 items of 25" and page links) pins to the card's bottom.
    Without one, a `footer` slot renders as the card footer, and `listing` gives the same viewport fill and sticky head.
    List pages build their toolbar from two slots instead of `toolbar` (which stays for a plain row):
      <x-slot:search> <x-search ... /> </x-slot:search>           the main filter, always on screen (full width on phones)
      <x-slot:filters> <x-filter ... /> ... </x-slot:filters>    the rest; behind a funnel button below sm, stacked when open
    :phone-filters="false": below sm the filters slot stays hidden with no funnel, for filters a phone can do without.
      <x-slot:tools> ... </x-slot:tools>                        buttons at the toolbar's end (exports, other views of the
                                                                 list), icon buttons with tooltips; the bulk pill joins them
    Filters go in this order: text / number inputs, then selects (x-filter), then other controls (tab groups, toggles).
    Page actions go in the layout's header slot; give a button's text <span class="pc-btn-label"> and below sm only its
    icon shows.
    Phones show only a row's essentials. Mark every other column on its <th> and <td>s:
      class="pc-col-more"   hidden below sm; tapping the row slides it open in a details row (app.js). For details a phone
                            user truly needs.
      class="pc-col-hide"   hidden below sm, no details row: for columns that add nothing essential.
    filterable: false hides the search and filters, for when there is nothing to filter at all (no records yet, before
    any filter); a filter that matches nothing keeps them so it can be cleared.
    Bulk actions: a `bulk` slot adds a select-all checkbox to the head, and each row starts with <x-table.select>.
    Ticking rows slides a pill (x-table.bulk) into the toolbar's end, beside the filters, with the count and an Actions
    menu holding the slot; unticking the select-all box (or Esc) clears it (app.js). data-pc-bulk-for="state" shows an
    action only while a ticked row is in that state (<x-table.select state="...">):
      <x-slot:bulk>
          <form method="POST" action="..." onsubmit="submitForm(event)" data-pc-bulk-form="forms" data-pc-bulk-for="active">   posts forms[]
              <button type="submit" class="pc-dropdown-item w-full" role="menuitem" data-pc-dismiss="dropdown" data-pc-confirm data-pc-bulk-confirm="Archive :count form?|Archive :count forms?">
      </x-slot:bulk>
    Attributes (class, ...) land on the card.
--}}
@props(['paginator' => null, 'label' => null, 'toolbar' => null, 'search' => null, 'filters' => null, 'phoneFilters' => true, 'tools' => null, 'filterable' => true, 'bulk' => null, 'head' => null, 'empty' => null, 'footer' => null, 'listing' => false])

@php
    $paged = $paginator && $paginator->total() > 0;
    $fills = $paginator || $listing;
    $selectable = $bulk && ! ($paginator && $paginator->isEmpty());
@endphp

<div @class(['pc-list', 'pc-list-fill' => $fills]) data-pc-list>
    @if ($filterable && ($search || $filters))
        @php($filtersId = 'pcFilters'.Str::random(6))
        <div class="pc-list-toolbar" data-pc-toolbar data-pc-open="false">
            @if ($search)
                <div class="pc-toolbar-search">{{ $search }}</div>
            @endif
            @if ($filters)
                @if ($phoneFilters)
                    <button type="button" class="pc-btn pc-btn-secondary pc-toolbar-toggle" data-pc-trigger="toolbar-filters"
                        aria-expanded="false" aria-controls="{{ $filtersId }}" aria-label="{{ __('Filters') }}" title="{{ __('Filters') }}">
                        <i class="fa-regular fa-filter text-base" aria-hidden="true"></i>
                    </button>
                @endif
                <div id="{{ $filtersId }}" @class(['pc-toolbar-filters', 'max-sm:hidden!' => ! $phoneFilters])>{{ $filters }}</div>
            @endif
            @if ($tools)<div class="pc-toolbar-tools">{{ $tools }}</div>@endif
            @if ($selectable)<x-table.bulk>{{ $bulk }}</x-table.bulk>@endif
        </div>
    @elseif ($toolbar || $tools || $selectable)
        <div class="pc-list-toolbar">
            {{ $toolbar }}
            @if ($tools)<div class="pc-toolbar-tools">{{ $tools }}</div>@endif
            @if ($selectable)<x-table.bulk>{{ $bulk }}</x-table.bulk>@endif
        </div>
    @endif

    <div {{ $attributes->class(['pc-list-card']) }}>
        @if ($paginator && $paginator->isEmpty() && $empty)
            <div class="pc-list-empty">{{ $empty }}</div>
        @else
            {{-- data-pc-scrollbar="y": scrolls sideways with no horizontal thumb (pc-scrollbar.js) --}}
            <div class="pc-list-scroll" data-pc-scrollbar="y">
                <table class="pc-table pc-table-inset pc-table-hover min-w-[640px]" @if ($label) aria-label="{{ $label }}" @endif>
                    @if ($head)
                        <thead><tr>
                            @if ($selectable)
                                <th class="pc-col-select"><input type="checkbox" class="pc-select-box" data-pc-select-all aria-label="{{ __('Select all on this page') }}"></th>
                            @endif
                            {{ $head }}
                        </tr></thead>
                    @endif
                    <tbody>{{ $slot }}</tbody>
                </table>
            </div>
        @endif

        @if ($paged)
            <div class="pc-list-footer">
                <span>{{ __(':first to :last items of :total', ['first' => number_format($paginator->firstItem()), 'last' => number_format($paginator->lastItem()), 'total' => number_format($paginator->total())]) }}</span>
                {{ $paginator->onEachSide(1)->links() }}
            </div>
        @elseif ($footer)
            <div class="pc-list-footer">{{ $footer }}</div>
        @endif
    </div>
</div>
