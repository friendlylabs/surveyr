{{--
    A grouped chart: the chart, and its data as a table of groups by series (number questions worked out, or a split
    question's answers counted), shown with the table visual and kept for screen readers otherwise.
--}}
@if ($summary['groups'])
    @include('app.responses.visualize.partials.chart')
    <div @class(['pc-viz-data', 'sr-only' => $visual !== 'table']) data-viz-pane="table">
        <div class="pc-viz-scroll">
            <table class="pc-table pc-viz-table">
                <thead>
                    <tr>
                        <th scope="col">{{ __('Group') }}</th>
                        @foreach ($summary['series'] as $head)
                            <th scope="col" class="text-end">{{ $head['label'] }}</th>
                        @endforeach
                        <th scope="col" class="text-end">{{ __('Responses') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($summary['groups'] as $group)
                        <tr>
                            <th scope="row" @class(['pc-viz-row-label', 'text-pc-muted' => $group['special']])>{{ $group['label'] }}</th>
                            @foreach ($group['values'] as $value)
                                <td class="pc-mono text-end">{{ $value === null ? '–' : (is_float($value) ? number_format($value, 2) : number_format($value)) }}</td>
                            @endforeach
                            <td class="pc-mono text-end">{{ number_format($group['responses']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @if ($summary['unreadableValues'] ?? 0)
        <p class="pc-viz-note">{{ trans_choice(':count number couldn\'t be read and was left out.|:count numbers couldn\'t be read and were left out.', $summary['unreadableValues'], ['count' => number_format($summary['unreadableValues'])]) }}</p>
    @endif
@else
    <p class="pc-viz-none">{{ __('No answers yet.') }}</p>
@endif
