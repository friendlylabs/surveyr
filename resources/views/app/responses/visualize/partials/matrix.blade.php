{{--
    A matrix as a heat table: rows by columns, each cell the count and its share of the row, tinted by that share (the
    numbers always show, so the tint is never the only cue). The stacked visual draws the same as bars.
--}}
@if ($summary['answered'])
    @include('app.responses.visualize.partials.chart')
    <div @class(['pc-viz-data', 'sr-only' => $visual !== 'heat']) data-viz-pane="table">
        <div class="pc-viz-scroll">
            <table class="pc-table pc-viz-table pc-viz-heat">
                <thead>
                    <tr>
                        <th scope="col"><span class="sr-only">{{ __('Row') }}</span></th>
                        @foreach ($summary['columns'] as $column)
                            <th scope="col" class="text-center">{{ $column['label'] }}</th>
                        @endforeach
                        <th scope="col" class="text-end">{{ __('Answers') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($summary['rows'] as $row)
                        <tr>
                            <th scope="row" class="pc-viz-row-label">{{ $row['label'] }}</th>
                            @foreach ($row['cells'] as $cell)
                                <td class="pc-viz-heat-cell" style="--pc-viz-share: {{ $cell['percent'] / 100 }}">
                                    <span class="pc-mono font-semibold">{{ $cell['percent'] }}%</span>
                                    <span class="pc-viz-heat-count">{{ number_format($cell['count']) }}</span>
                                </td>
                            @endforeach
                            <td class="pc-mono text-end">{{ number_format($row['total']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@else
    <p class="pc-viz-none">{{ __('No answers yet.') }}</p>
@endif
