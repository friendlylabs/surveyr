{{-- Overview charts: daily responses with a 7-day trend, plus the flag breakdown when the form has a flag group. --}}
@php
    $o = $overview;
    $rows = $o['flags']
        ? collect($o['flags']['items'])->map(fn ($f) => ['label' => $f['label'], 'count' => $f['count'], 'color' => $f['color']])
            ->push(['label' => 'Unflagged', 'count' => $o['flags']['unflagged'], 'color' => null])
        : null;
@endphp

<div class="row g-3">
    <div class="{{ $rows ? 'col-lg-7' : 'col-12' }}">
        <div class="fo-card h-100">
            <div class="fo-card-head">
                <div>
                    <h3>Daily responses</h3>
                    <p>Last 30 days, with the 7-day average</p>
                </div>
            </div>
            @if($o['total'])
                <div id="foDailyChart" class="fo-chart"></div>
                <div class="fo-legend">
                    <span><i class="is-bar"></i> Responses</span>
                    <span><i class="is-line"></i> 7-day average</span>
                </div>
            @else
                @include('components.empty', [
                    'alertTitle' => 'No responses yet',
                    'alertMessage' => 'Share the form to start collecting responses.'
                ])
            @endif
        </div>
    </div>

    @if($rows)
    <div class="col-lg-5">
        <div class="fo-card h-100">
            <div class="fo-card-head">
                <div>
                    <h3>Flags</h3>
                    <p>How responses have been flagged</p>
                </div>
            </div>
            <ul class="fo-bars">
                @foreach($rows as $row)
                    @php $share = $o['total'] ? round($row['count'] / $o['total'] * 100) : 0; @endphp
                    <li>
                        <div class="fo-bar-label">
                            <span>{{ $row['label'] }}</span>
                            <span class="fo-bar-value">{{ number_format($row['count']) }} · {{ $share }}%</span>
                        </div>
                        <div class="fo-bar-track">
                            <div class="fo-bar-fill" style="width: {{ $share }}%; {{ $row['color'] ? '--bar: ' . $row['color'] : '' }}"></div>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif
</div>

<script>window.formOverviewSeries = @json((object) $o['series']);</script>
