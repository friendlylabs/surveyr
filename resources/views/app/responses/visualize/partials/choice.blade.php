{{--
    A choice, multiple-choice or yes / no question: the chart and table of picks. Several picks per answer read as a
    share of those who answered (they add up past 100%). Typed Other answers are listed beneath.
--}}
@if ($summary['answered'])
    @include('app.responses.visualize.partials.chart')
    @include('app.responses.visualize.partials.table')
    @if ($summary['multiple'] ?? false)
        <p class="pc-viz-note">{{ __('More than one could be picked, so the shares add up to more than 100%.') }}</p>
    @endif
    @if (! empty($answers[$summary['question']]))
        <h3 class="pc-viz-subtitle">{{ __('Other answers') }}</h3>
        @include('app.responses.visualize.partials.answers', ['page' => $answers[$summary['question']], 'other' => true, 'name' => $summary['question']])
    @endif
@else
    <p class="pc-viz-none">{{ __('No answers yet.') }}</p>
@endif
