{{-- Activity log of one API key (List.js via Phoenix data-list). Expects $activities, newest first. --}}
<div id="apiActivityList" data-list='{"valueNames":["method","path","origin","code","outcome",{"name":"time","attr":"data-time"}],"page":15,"pagination":true,"filter":{"key":"outcome"}}'>

    {{-- toolbar: search + outcome filter --}}
    <div class="row g-2 align-items-center mb-3">
        <div class="col-12 col-md-5 col-lg-4">
            <div class="search-box w-100">
                <form class="position-relative" onsubmit="return false">
                    <input class="form-control search-input search form-control-sm" type="search" placeholder="Search endpoint or origin" aria-label="Search activity">
                    <span class="fas fa-search search-box-icon"></span>
                </form>
            </div>
        </div>
        <div class="col-auto">
            <select class="form-select form-select-sm" data-list-filter aria-label="Filter by outcome">
                <option value="">All responses</option>
                <option value="success">Successful</option>
                <option value="client">Client errors (4xx)</option>
                <option value="server">Server errors (5xx)</option>
            </select>
        </div>
        <div class="col text-end text-body-tertiary fs-9 d-none d-md-block">
            @if($activities->count() >= 500)
                Latest 500 requests
            @else
                {{ $activities->count() }} {{ $activities->count() === 1 ? 'request' : 'requests' }}
            @endif
        </div>
    </div>

    <div class="table-responsive scrollbar">
        <table class="table table-hover fs-9 mb-0 border-translucent align-middle">
            <thead>
                <tr class="text-body-tertiary text-uppercase fs-10">
                    <th class="sort ps-0" data-sort="time" style="min-width: 160px;">When</th>
                    <th class="sort" data-sort="path" style="min-width: 260px;">Endpoint</th>
                    <th class="sort" data-sort="origin">Origin</th>
                    <th class="sort text-end pe-0" data-sort="code">Status</th>
                </tr>
            </thead>
            <tbody class="list">
                @foreach($activities as $activity)
                    @php
                        [$method, $path] = $activity->route();
                        $outcome = $activity->outcome();
                        $tone = ['success' => 'success', 'client' => 'warning', 'server' => 'danger'][$outcome];
                        $methodTone = ['GET' => 'info', 'POST' => 'success', 'PUT' => 'warning', 'PATCH' => 'warning', 'DELETE' => 'danger'][$method] ?? 'secondary';
                    @endphp
                    <tr>
                        <td class="ps-0 white-space-nowrap text-body-secondary time" data-time="{{ $activity->created_at->timestamp }}" title="{{ $activity->created_at->format('M j, Y H:i:s') }}">
                            {{ $activity->created_at->diffForHumans() }}
                            <span class="d-block text-body-tertiary fs-10">{{ $activity->created_at->format('M j, H:i:s') }}</span>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2 min-w-0">
                                @if($method)
                                    <span class="badge badge-phoenix badge-phoenix-{{ $methodTone }} method" style="min-width: 52px;">{{ $method }}</span>
                                @endif
                                <code class="text-body text-truncate path" style="max-width: 420px;" title="{{ $path }}">{{ $path }}</code>
                            </div>
                        </td>
                        <td class="white-space-nowrap text-body-secondary origin">{{ $activity->origin }}</td>
                        <td class="text-end pe-0 white-space-nowrap">
                            <span class="badge badge-phoenix badge-phoenix-{{ $tone }} code">{{ $activity->status }}</span>
                            <span class="d-none outcome">{{ $outcome }}</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- shown by list.js when a search / filter matches nothing --}}
        <div class="fallback d-none text-center text-body-tertiary py-5">
            <i class="fa-solid fa-magnifying-glass fs-6 mb-2 d-block"></i>
            No requests match your search
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-3 fs-9">
        <span class="text-body-tertiary" data-list-info></span>
        <div class="d-flex align-items-center">
            <button class="btn btn-link btn-sm px-1" type="button" data-list-pagination="prev" aria-label="Previous page">
                <i class="fa-solid fa-chevron-left"></i>
            </button>
            <ul class="mb-0 pagination pagination-sm"></ul>
            <button class="btn btn-link btn-sm px-1" type="button" data-list-pagination="next" aria-label="Next page">
                <i class="fa-solid fa-chevron-right"></i>
            </button>
        </div>
    </div>
</div>
