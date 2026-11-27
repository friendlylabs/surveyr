{{--
    x-table's selection pill, shown at the toolbar's end while rows are ticked (app.js): the count, and an Actions
    button opening a menu of the bulk actions (the slot: forms whose buttons are pc-dropdown-item, or such buttons and
    links on their own, for actions a page script runs).
--}}
@php($menuId = 'pcBulkMenu'.Str::random(6))

<div class="pc-list-bulk" data-pc-bulk role="group" aria-label="{{ __('Bulk actions') }}">
    <span class="pc-list-bulk-count" aria-live="polite"><span class="pc-list-bulk-number" data-pc-bulk-number></span>{{ __('selected') }}</span>
    <div class="pc-dropdown">
        <button type="button" class="pc-list-bulk-trigger" data-pc-trigger="dropdown" data-pc-target="#{{ $menuId }}" aria-haspopup="menu" aria-expanded="false">
            {{ __('Actions') }}<i class="fa-regular fa-chevron-down text-xs" aria-hidden="true"></i>
        </button>
        <div id="{{ $menuId }}" class="pc-dropdown-menu pc-dropdown-menu-end" role="menu" aria-hidden="true">
            {{ $slot }}
        </div>
    </div>
</div>
