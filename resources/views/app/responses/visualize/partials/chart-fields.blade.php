{{--
    A chart's fields, shared by the add and update panels ($prefix keeps their ids apart). The kind of chart first (soft
    chips), then its fields: the question it charts, its title, the visual, and last the rules that grow:
      question: the question, the title and the visual it opens with (the list follows the question). Free-text
                questions aren't offered: there's nothing to chart in them.
      grouped:  the question whose answers group the responses (choices, ratings, yes / no or short text such as a
                school's name), the title and visual, then either number questions worked out per group (each with sum,
                average, count, smallest or largest) or a choice question split into its answers. Every group shows.
    The other kind's fields sit in a disabled fieldset, so they aren't posted. resources/js/visualize.js runs the
    chips, the number rows and the visual lists.
--}}
@php($aggregates = ['sum' => __('Total'), 'avg' => __('Average'), 'count' => __('Count'), 'min' => __('Smallest'), 'max' => __('Largest')])

<div class="pc-tabs pc-tabs-soft pc-tabs-field" role="radiogroup" aria-label="{{ __('Kind of chart') }}">
    @foreach (['question' => __('One question'), 'grouped' => __('Grouped')] as $kind => $label)
        <label @class(['pc-tab cursor-pointer', 'pc-tab-active' => $kind === 'question'])>
            <input type="radio" name="kind" value="{{ $kind }}" class="sr-only" data-viz-chart-kind @checked($kind === 'question')>{{ $label }}
        </label>
    @endforeach
</div>

<fieldset class="flex flex-col gap-4" data-viz-kind="question">
    <x-form.select :id="$prefix.'Question'" name="question" :label="__('Question')" :options="$chartable" :placeholder="__('Choose a question')" required data-viz-chart-question />
    <x-form.input :id="$prefix.'Title'" name="title" maxlength="160" autocomplete="off" :label="__('Title')" :placeholder="__('The question\'s own title')" />
    <div>
        <label class="pc-label" for="{{ $prefix }}Visual">{{ __('Show as') }}</label>
        <select id="{{ $prefix }}Visual" name="visual" class="pc-select" data-viz-chart-visual aria-describedby="{{ $prefix }}VisualError">
            <option value="">{{ __('Default') }}</option>
        </select>
        <div id="{{ $prefix }}VisualError" class="pc-field-error" hidden></div>
    </div>
</fieldset>

<fieldset class="flex flex-col gap-4" data-viz-kind="grouped" disabled hidden>
    <x-form.select :id="$prefix.'Label'" name="label" :label="__('Group by')" :options="$groupable['labels']" :placeholder="__('Choose a question')" required :hint="__('Each answer becomes a group, e.g. each school.')" />
    <x-form.input :id="$prefix.'GroupedTitle'" name="title" maxlength="160" autocomplete="off" :label="__('Title')" :placeholder="__('Worked out from the questions')" />
    <div>
        <label class="pc-label" for="{{ $prefix }}GroupedVisual">{{ __('Show as') }}</label>
        <select id="{{ $prefix }}GroupedVisual" name="visual" class="pc-select" data-viz-grouped-visual></select>
    </div>

    <div class="flex flex-col gap-1.5">
        <span class="pc-label">{{ __('In each group') }}</span>
        <div class="pc-tabs pc-tabs-soft pc-tabs-field" role="radiogroup" aria-label="{{ __('In each group') }}">
            @foreach (['numbers' => __('Work out numbers'), 'split' => __('Split by a choice')] as $mode => $label)
                <label @class(['pc-tab cursor-pointer', 'pc-tab-active' => $mode === 'numbers'])>
                    <input type="radio" name="mode" value="{{ $mode }}" class="sr-only" data-viz-chart-mode @checked($mode === 'numbers')>{{ $label }}
                </label>
            @endforeach
        </div>
    </div>

    <div data-viz-mode="split" hidden>
        <x-form.select :id="$prefix.'Split'" name="split" :label="__('Split each group by')" :options="$groupable['splits']" :placeholder="__('Choose a question')" :hint="__('Its answers are counted in each group.')" />
    </div>

    {{-- numbers: up to MAX_SERIES items (a number question and how it's worked out per group), set like the filter
         panel's rules (app.css .pc-filter-*): tinted on hover with the remove button then, the add line under them;
         last, as it grows --}}
    <div class="flex flex-col gap-1.5" data-viz-mode="numbers">
        <span class="pc-label">{{ __('Numbers') }}</span>
        <div class="pc-filter-section">
            <div class="pc-filter-rules" data-viz-series></div>
            <button type="button" class="pc-filter-insert" data-viz-series-add>
                <span><i class="fa-regular fa-plus" aria-hidden="true"></i>{{ __('Add item') }}</span>
            </button>
        </div>
        <template data-viz-series-row>
            <div class="pc-filter-rule pc-filter-rule-pair" data-viz-series-item>
                <select class="pc-select" data-viz-series-question aria-label="{{ __('Number question') }}">
                    <option value="">{{ __('Choose a question') }}</option>
                    @foreach ($groupable['series'] as $value => $text)
                        <option value="{{ $value }}">{{ $text }}</option>
                    @endforeach
                </select>
                <select class="pc-select" data-viz-series-aggregate aria-label="{{ __('Worked out as') }}">
                    @foreach ($aggregates as $value => $text)
                        <option value="{{ $value }}">{{ $text }}</option>
                    @endforeach
                </select>
                <button type="button" class="pc-btn pc-btn-ghost pc-btn-icon pc-btn-sm" data-viz-series-remove data-filter-remove aria-label="{{ __('Remove') }}" data-pc-tooltip="{{ __('Remove') }}">
                    <i class="fa-regular fa-xmark" aria-hidden="true"></i>
                </button>
            </div>
        </template>
        <div id="{{ $prefix }}SeriesError" class="pc-field-error" hidden></div>
    </div>
</fieldset>
