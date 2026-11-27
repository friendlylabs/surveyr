{{--
    The filter panel (pages/response-filters.js builds the rule rows): when responses came in, and rules on answers.
    Apply reloads the table with them (?filters=); they can be saved as a view (partials/save-view) or, on a view the
    user may change, saved back into it.
--}}
<x-dialog.offcanvas id="responsesFilter" :title="__('Filter responses')" :subtitle="$activeView ? __('View: :name', ['name' => $activeView->name]) : null">
    <x-form.date id="filterDates" name="from" end-name="to" :label="__('Submitted')" range :value="$filters['from']" :end-value="$filters['to']" />

    {{-- the add line shows when the rules are hovered or it has focus (always, faintly, on touch screens) --}}
    <div class="pc-filter-section">
        <div class="pc-filter-rules" data-filter-rules></div>
        <button type="button" class="pc-filter-insert" data-filter-add>
            <span><i class="fa-regular fa-plus" aria-hidden="true"></i>{{ __('Add rule') }}</span>
        </button>
    </div>

    <x-slot:footer>
        <button type="button" class="pc-btn pc-btn-ghost" data-filter-clear>{{ __('Clear') }}</button>
        <div class="ms-auto flex items-center gap-2">
            @if ($canUpdateView)
                <form method="POST" action="{{ route('forms.views.update', [$form, $activeView]) }}" onsubmit="submitForm(event)" data-filter-save-into>
                    <input type="hidden" name="filters" value="">
                    <button type="submit" class="pc-btn pc-btn-secondary">{{ __('Save to view') }}</button>
                </form>
            @endif
            <button type="button" class="pc-btn pc-btn-secondary pc-btn-icon" data-pc-trigger="modal" data-pc-target="#saveView" aria-label="{{ __('Save as view') }}" data-pc-tooltip="{{ __('Save as view') }}">
                <i class="fa-regular fa-floppy-disk" aria-hidden="true"></i>
            </button>
            <button type="button" class="pc-btn pc-btn-primary" data-filter-apply>{{ __('Apply') }}</button>
        </div>
    </x-slot:footer>
</x-dialog.offcanvas>
