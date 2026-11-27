{{-- The overview's stats: responses in all (and the last 7 days), this week against the one before, the latest response --}}
@php
    $change = $stats['prev7'] ? (int) round(($stats['last7'] - $stats['prev7']) / $stats['prev7'] * 100) : null;
@endphp

<div class="pc-stat-grid mb-6">
    <x-stat icon="inbox" tone="brand" :label="__('Responses')" :value="number_format($stats['total'])" :href="route('forms.responses', $form)">
        {{ $stats['last7'] ? __('+:count in the last 7 days', ['count' => number_format($stats['last7'])]) : __('None in the last 7 days') }}
    </x-stat>
    <x-stat icon="calendar-check" tone="ink" :label="__('This week')" :value="number_format($stats['last7'])">
        @if ($change !== null)
            <span @class(['text-pc-success-ink' => $change >= 0, 'text-pc-danger-ink' => $change < 0])>{{ ($change > 0 ? '+' : '').$change }}%</span>
            {{ __('against :count the week before', ['count' => number_format($stats['prev7'])]) }}
        @else
            {{ __(':count the week before', ['count' => number_format($stats['prev7'])]) }}
        @endif
    </x-stat>
    <x-stat icon="clock" tone="ink" :label="__('Last response')" :value="$stats['lastAt'] ? $stats['lastAt']->diffForHumans(['short' => true]) : '—'">
        {{ $stats['lastAt'] ? $stats['lastAt']->format('j M Y, H:i') : __('No responses yet') }}
    </x-stat>
</div>
