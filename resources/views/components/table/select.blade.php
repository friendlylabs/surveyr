{{--
    A row's checkbox for x-table's bulk actions; the first cell of each row when the table has a `bulk` slot.
      <x-table.select :value="$form->hash" :label="$form->title" :state="$form->isArchived() ? 'archived' : 'active'" />
    disabled: the row can't take the bulk actions (e.g. the user may not delete it); the cell stays, empty.
    state: what the row is now; a bulk action marked data-pc-bulk-for="archived" only shows while a ticked row is in
    that state (app.js), so the pill offers Restore for archived rows and Archive for the rest.
--}}
@props(['value', 'label', 'disabled' => false, 'state' => null])

<td class="pc-col-select">
    @unless ($disabled)
        <input type="checkbox" class="pc-select-box" value="{{ $value }}" data-pc-select @if ($state) data-pc-state="{{ $state }}" @endif aria-label="{{ __('Select :name', ['name' => $label]) }}">
    @endunless
</td>
