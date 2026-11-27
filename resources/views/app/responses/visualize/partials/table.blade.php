{{--
    A card's data: every row with its count and share, the same numbers the chart draws. Shown with the table visual;
    otherwise only screen readers get it (sr-only), so the chart never stands alone. $heading names the first column.
--}}
<div @class(['pc-viz-data', 'sr-only' => ! in_array($visual, ['table', 'heat'], true)]) data-viz-pane="table">
    <table class="pc-table pc-viz-table">
        <thead>
            <tr>
                <th scope="col">{{ $heading ?? __('Answer') }}</th>
                <th scope="col" class="text-end">{{ __('Count') }}</th>
                <th scope="col" class="text-end">{{ __('Share') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($summary['categories'] as $row)
                <tr>
                    <th scope="row" @class(['pc-viz-row-label', 'text-pc-muted' => $row['special']])>{{ $row['label'] }}</th>
                    <td class="pc-mono text-end">{{ number_format($row['count']) }}</td>
                    <td class="pc-mono text-end">
                        <span class="pc-viz-share">
                            <span class="pc-progress" aria-hidden="true"><span class="pc-progress-bar block" style="width: {{ min(100, $row['percent']) }}%"></span></span>
                            {{ $row['percent'] }}%
                        </span>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
