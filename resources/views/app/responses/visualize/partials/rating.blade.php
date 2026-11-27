{{--
    A rating: its average, median and range (when the steps are numbers) and how many gave each step. A 0–10 rating
    leads with its Net Promoter Score and the promoter / passive / detractor split.
--}}
@php($stats = $summary['stats'])
@if ($summary['answered'])
    @if ($summary['kind'] === 'nps' && $summary['nps']['score'] !== null)
        @include('app.responses.visualize.partials.stats', ['figures' => [
            [($summary['nps']['score'] > 0 ? '+' : '').$summary['nps']['score'], __('Net Promoter Score')],
            [$summary['nps']['promoters']['percent'].'%', __('Promoters (9–10)')],
            [$summary['nps']['passives']['percent'].'%', __('Passives (7–8)')],
            [$summary['nps']['detractors']['percent'].'%', __('Detractors (0–6)')],
        ]])
    @elseif ($stats['count'])
        @include('app.responses.visualize.partials.stats', ['figures' => [
            [$summary['max'] ? __(':value / :max', ['value' => $stats['mean'], 'max' => $summary['max']]) : $stats['mean'], __('Average')],
            [$stats['median'], __('Median')],
            [$stats['min'].' – '.$stats['max'], __('Range')],
        ]])
    @endif
    @include('app.responses.visualize.partials.chart')
    @include('app.responses.visualize.partials.table', ['heading' => __('Rating')])
@else
    <p class="pc-viz-none">{{ __('No answers yet.') }}</p>
@endif
