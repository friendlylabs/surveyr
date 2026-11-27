{{--
    A row of figures, the number on top and its label under it: $figures = [[value, label], ...].
--}}
<div class="pc-viz-stats">
    @foreach ($figures as [$value, $label])
        <div class="pc-viz-stat">
            <span class="pc-viz-stat-value">{{ $value }}</span>
            <span class="pc-viz-stat-label">{{ $label }}</span>
        </div>
    @endforeach
</div>
