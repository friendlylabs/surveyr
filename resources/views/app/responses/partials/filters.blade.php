{{--
    The filter panel (pages/response-filters.js builds the rule rows): when responses came in, and rules on answers.
    Apply reloads the page with them (?filters=); they can be saved as a view (partials/save-view) or, on a view the
    user may change, saved back into it. Used by the responses table and Visualize.
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
                    @if (($returnTo ?? null) === 'visualize')
                        <input type="hidden" name="return" value="visualize">
                    @endif
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

{{-- read by pages/response-filters.js: the questions a rule can use, its operators, and the filters in force --}}
<script type="application/json" id="responseFiltersConfig">{!! json_encode([
    'fields' => $fields ?: (object) [],
    'operators' => App\Support\SubmissionFilters::OPERATORS,
    'labels' => App\Support\SubmissionFilters::operatorLabels(),
    'filters' => $filters,
    'strings' => ['question' => __('Question'), 'value' => __('Value'), 'min' => __('Min'), 'max' => __('Max'), 'choose' => __('Choose…'), 'remove' => __('Remove rule')],
], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
