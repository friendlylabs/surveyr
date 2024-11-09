<div class="d-grid h-100 align-items-center justify-content-center" style="min-height: 300px;">
    <div class="d-grid align-items-center justify-content-center">
        <div class="text-center empty-icon">
            {!! (isset($emptyIcon)) ? null : '<i class="fa-regular fa-folder-open mb-3" style="font-size:4rem"></i>' !!}
            <h2>{{ $emptyTitle ?? null }}</h2>
            <p>{!! $emptyText ?? __('No records found') !!}</p>
        </div>
    </div>
</div>