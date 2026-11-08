{{-- Overview stat cards. Expects $overview (FormsController::overviewStats) and $form. --}}
@php
    $o = $overview;
    $trend = fn (?int $pct) => $pct === null ? null : ['text' => ($pct > 0 ? '+' : '') . $pct . '%', 'tone' => $pct >= 0 ? 'up' : 'down'];
    $weekTrend = $trend($o['prev7'] ? (int) round(($o['last7'] - $o['prev7']) / $o['prev7'] * 100) : null);
@endphp

<div class="fo-stats">
    <div class="fo-stat">
        <span class="fo-stat-label">Responses</span>
        <div class="fo-stat-value">
            {{ number_format($o['total']) }}
            @if($o['last7'])<span class="fo-delta is-up">+{{ number_format($o['last7']) }}</span>@endif
        </div>
        <span class="fo-stat-note">{{ $o['last7'] ? 'last 7 days' : 'none in the last 7 days' }}</span>
    </div>

    @if($o['flags'])
        <div class="fo-stat">
            <span class="fo-stat-label">Awaiting flag</span>
            <div class="fo-stat-value">{{ number_format($o['flags']['unflagged']) }}</div>
            <span class="fo-stat-note">
                {{ $o['flags']['oldestUnflagged'] ? 'oldest ' . \Carbon\Carbon::parse($o['flags']['oldestUnflagged'])->diffForHumans(null, true) : 'all flagged' }}
            </span>
        </div>
    @else
        <div class="fo-stat">
            <span class="fo-stat-label">This week</span>
            <div class="fo-stat-value">
                {{ number_format($o['last7']) }}
                @if($weekTrend)<span class="fo-delta is-{{ $weekTrend['tone'] }}">{{ $weekTrend['text'] }}</span>@endif
            </div>
            <span class="fo-stat-note">{{ number_format($o['prev7']) }} the week before</span>
        </div>
    @endif

    <div class="fo-stat">
        <span class="fo-stat-label">Last response</span>
        <div class="fo-stat-value">{{ $o['lastAt'] ? $o['lastAt']->diffForHumans(['short' => true]) : '—' }}</div>
        <span class="fo-stat-note">{{ $o['lastAt'] ? $o['lastAt']->format('M j, H:i') : 'no responses yet' }}</span>
    </div>
</div>
