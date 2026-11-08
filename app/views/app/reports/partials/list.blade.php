{{-- Global reports list (List.js via Phoenix data-list). Expects $reports with forms, link and sections_count. --}}
<div id="reportsList" data-list='{"valueNames":["title","description","sources","owner","status",{"name":"modified","attr":"data-modified"}],"page":10,"pagination":true,"filter":{"key":"status"}}'>

    {{-- toolbar: search + status filter --}}
    <div class="row g-2 align-items-center mb-3">
        <div class="col-12 col-md-5 col-lg-4">
            <div class="search-box w-100">
                <form class="position-relative" onsubmit="return false">
                    <input class="form-control search-input search form-control-sm" type="search" placeholder="Search reports" aria-label="Search reports">
                    <span class="fas fa-search search-box-icon"></span>
                </form>
            </div>
        </div>
        <div class="col-auto">
            <select class="form-select form-select-sm" data-list-filter aria-label="Filter by status">
                <option value="">All statuses</option>
                <option value="published">Published</option>
                <option value="draft">Draft</option>
            </select>
        </div>
        <div class="col text-end text-body-tertiary fs-9 d-none d-md-block">
            {{ $reports->count() }} {{ $reports->count() === 1 ? 'report' : 'reports' }}
        </div>
    </div>

    <div class="table-responsive scrollbar">
        <table class="table table-hover fs-9 mb-0 border-translucent align-middle">
            <thead>
                <tr class="text-body-tertiary text-uppercase fs-10">
                    <th class="sort ps-0" data-sort="title" style="min-width: 260px;">Report</th>
                    <th class="sort" data-sort="sources" style="min-width: 180px;">Sources</th>
                    <th class="sort" data-sort="owner">Owner</th>
                    <th class="sort" data-sort="status">Status</th>
                    <th class="sort" data-sort="modified">Modified</th>
                    <th class="text-end pe-0"></th>
                </tr>
            </thead>
            <tbody class="list">
                @foreach($reports as $report)
                    @php
                        $published = $report->sections_count > 0;
                        $sources = $report->sourceForms()->pluck('title')->all();
                        $isOwner = $report->user_id == auth()->id() || auth()->user()->role === 'admin';
                    @endphp
                    <tr>
                        <td class="ps-0 py-2">
                            <div class="d-flex align-items-center">
                                <div class="avatar avatar-m me-3 d-none d-sm-block">
                                    <div class="avatar-name rounded-3 bg-primary-subtle text-primary fw-bold">
                                        <span>{{ strtoupper(substr($report->title, 0, 1)) }}</span>
                                    </div>
                                </div>
                                <div class="min-w-0" style="max-width: 420px;">
                                    <a class="fw-bold text-body-emphasis text-truncate d-block title" href="@route('reports.show', $report->id)">
                                        {{ $report->title }}
                                    </a>
                                    @if($report->description)
                                        <span class="text-body-tertiary fs-10 text-truncate d-block description">
                                            {{ substring($report->description, 100) }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="sources">
                            <div class="d-flex flex-wrap gap-1">
                                @foreach(array_slice($sources, 0, 2) as $title)
                                    <span class="badge badge-phoenix badge-phoenix-secondary text-truncate" style="max-width: 160px;" title="{{ $title }}">{{ $title }}</span>
                                @endforeach
                                @if(count($sources) > 2)
                                    <span class="badge badge-phoenix badge-phoenix-secondary" title="{{ implode(', ', array_slice($sources, 2)) }}">+{{ count($sources) - 2 }}</span>
                                @endif
                                @if(!count($sources))
                                    <span class="text-body-tertiary fs-10">No source form</span>
                                @endif
                            </div>
                        </td>
                        <td class="white-space-nowrap owner">
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar avatar-s">
                                    @if($report->user->avatar)
                                        <img class="rounded-circle" src="{{ urlPath($report->user->avatar) }}" alt="">
                                    @else
                                        <div class="avatar-name rounded-circle"><span>{{ strtoupper(substr($report->user->fullname, 0, 1)) }}</span></div>
                                    @endif
                                </div>
                                <span class="text-body-secondary">{{ $report->user->fullname }}</span>
                            </div>
                        </td>
                        <td class="white-space-nowrap">
                            <span class="badge badge-phoenix badge-phoenix-{{ $published ? 'success' : 'warning' }} status">{{ $published ? 'Published' : 'Draft' }}</span>
                            @if($report->link)
                                <span class="badge badge-phoenix badge-phoenix-info ms-1" title="Has a public link"><i class="fa-solid fa-link me-1"></i>Public</span>
                            @endif
                        </td>
                        <td class="white-space-nowrap text-body-secondary modified" data-modified="{{ $report->updated_at->timestamp }}" title="{{ $report->updated_at->format('M j, Y H:i') }}">
                            {{ $report->updated_at->diffForHumans() }}
                        </td>
                        <td class="text-end pe-0">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-phoenix-secondary py-0 px-2" type="button" id="reportActions{{ $report->id }}" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false" aria-label="Report actions">
                                    <i class="fa-solid fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="reportActions{{ $report->id }}">
                                    <li>
                                        <a class="dropdown-item" href="@route('reports.show', $report->id)">
                                            <i class="fa-solid fa-eye me-2"></i> View report
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="@route('reports.edit', $report->id)">
                                            <i class="fa-solid fa-pencil me-2"></i> Edit report
                                        </a>
                                    </li>
                                    @if($isOwner)
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item text-danger" href="@route('reports.delete', $report->id)" onclick="confirmDelete(event)"
                                                data-delete-msg="Deleting this report also revokes its public link. Are you sure?">
                                                <i class="fa-solid fa-trash me-2"></i> Delete report
                                            </a>
                                        </li>
                                    @endif
                                </ul>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- shown by list.js when a search / filter matches nothing --}}
        <div class="fallback d-none text-center text-body-tertiary py-5">
            <i class="fa-solid fa-magnifying-glass fs-6 mb-2 d-block"></i>
            No reports match your search
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
