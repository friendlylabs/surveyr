{{-- Default paginator view (set in AppServiceProvider): Surveyr's pager, .pc-pagination (borderless numbers, brand pill for the current page) --}}
@if ($paginator->hasPages())
    <nav class="pc-pagination" aria-label="Pagination">
        @if ($paginator->onFirstPage())
            <button type="button" class="pc-page-btn pc-page-step" disabled aria-label="Previous page"><i class="ph ph-caret-left" aria-hidden="true"></i></button>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="pc-page-btn pc-page-step" rel="prev" aria-label="Previous page"><i class="ph ph-caret-left" aria-hidden="true"></i></a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="pc-page-btn" aria-hidden="true">…</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="pc-page-btn" aria-current="page">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="pc-page-btn" aria-label="Page {{ $page }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="pc-page-btn pc-page-step" rel="next" aria-label="Next page"><i class="ph ph-caret-right" aria-hidden="true"></i></a>
        @else
            <button type="button" class="pc-page-btn pc-page-step" disabled aria-label="Next page"><i class="ph ph-caret-right" aria-hidden="true"></i></button>
        @endif
    </nav>
@endif
