{{--
    A ranking: every choice's average position (1 is the top, so lower is better), best first, with how often it came
    first and how many left it unranked.
--}}
@if ($summary['answered'])
    @include('app.responses.visualize.partials.chart')
    <div @class(['pc-viz-data', 'sr-only' => $visual !== 'table']) data-viz-pane="table">
        <table class="pc-table pc-viz-table">
            <thead>
                <tr>
                    <th scope="col">{{ __('Choice') }}</th>
                    <th scope="col" class="text-end">{{ __('Average position') }}</th>
                    <th scope="col" class="text-end">{{ __('Ranked first') }}</th>
                    <th scope="col" class="text-end">{{ __('Not ranked') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($summary['ranking'] as $row)
                    <tr>
                        <th scope="row" class="pc-viz-row-label">{{ $row['label'] }}</th>
                        <td class="pc-mono text-end">{{ $row['average'] ?? '–' }}</td>
                        <td class="pc-mono text-end">{{ number_format($row['first']) }}</td>
                        <td class="pc-mono text-end">{{ number_format($row['unranked']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <p class="pc-viz-note">{{ __('Position 1 is the top, so a lower average is better.') }}</p>
@else
    <p class="pc-viz-none">{{ __('No answers yet.') }}</p>
@endif
