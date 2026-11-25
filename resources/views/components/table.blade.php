{{--
    Data table for list pages, in Surveyr's listing style: the toolbar (search, filters, actions) sits on the page above
    the table, detached from it; the table sits in a card with a 24px inset, heavy uppercase headings and roomy rows.
      <x-table :paginator="$users" label="Users">
          <x-slot:toolbar> filters on the left, actions pushed right with ml-auto </x-slot:toolbar>
          <x-slot:head> <th>Name</th> ... </x-slot:head>
          <x-slot:empty> <x-empty ... /> </x-slot:empty>
          @foreach (...) <tr>...</tr> @endforeach
      </x-table>
    With a paginator, the toolbar and card together fill the viewport below the header: the head sticks, an empty page
    centres the `empty` slot, and the footer ("1 to 20 items of 25" and page links) pins to the card's bottom.
    Without one, a `footer` slot renders as the card footer, and `listing` gives the same viewport fill and sticky head.
    Toolbar filters go in this order: text / number inputs (x-search), then selects (x-filter), then other controls
    (tab groups, toggles, date ranges).
    Attributes (class, ...) land on the card.
--}}
@props(['paginator' => null, 'label' => null, 'toolbar' => null, 'head' => null, 'empty' => null, 'footer' => null, 'listing' => false])

@php
    $paged = $paginator && $paginator->total() > 0;
    $fills = $paginator || $listing;
@endphp

<div @class(['pc-list', 'pc-list-fill' => $fills])>
    @if ($toolbar)
        <div class="pc-list-toolbar">
            {{ $toolbar }}
        </div>
    @endif

    <div {{ $attributes->class(['pc-card pc-list-card']) }}>
        @if ($paginator && $paginator->isEmpty() && $empty)
            <div class="pc-list-empty">{{ $empty }}</div>
        @else
            <div class="pc-list-scroll">
                <table class="pc-table pc-table-inset pc-table-hover min-w-[640px]" @if ($label) aria-label="{{ $label }}" @endif>
                    @if ($head)
                        <thead><tr>{{ $head }}</tr></thead>
                    @endif
                    <tbody>{{ $slot }}</tbody>
                </table>
            </div>
        @endif

        @if ($paged)
            <div class="pc-list-footer">
                <span>{{ number_format($paginator->firstItem()) }} to {{ number_format($paginator->lastItem()) }} items of {{ number_format($paginator->total()) }}</span>
                {{ $paginator->onEachSide(1)->links() }}
            </div>
        @elseif ($footer)
            <div class="pc-list-footer">{{ $footer }}</div>
        @endif
    </div>
</div>
