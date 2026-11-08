{{-- Zones list (List.js via Phoenix data-list). Expects $zones with datasets_count, records_count and editor_users. --}}
<div id="zonesList" data-list='{"valueNames":["name","description","owner",{"name":"datasets","attr":"data-datasets"},{"name":"records","attr":"data-records"},{"name":"modified","attr":"data-modified"}],"page":10,"pagination":true}'>

    {{-- toolbar: search + data URL builder --}}
    <div class="row g-2 align-items-center mb-3">
        <div class="col-12 col-md-5 col-lg-4">
            <div class="search-box w-100">
                <form class="position-relative" onsubmit="return false">
                    <input class="form-control search-input search form-control-sm" type="search" placeholder="Search zones" aria-label="Search zones">
                    <span class="fas fa-search search-box-icon"></span>
                </form>
            </div>
        </div>
        <div class="col text-end">
            <span class="text-body-tertiary fs-9 d-none d-md-inline me-3">
                {{ $zones->count() }} {{ $zones->count() === 1 ? 'zone' : 'zones' }}
            </span>
            <button class="btn btn-phoenix-secondary btn-sm" type="button" data-bs-toggle="offcanvas" data-bs-target="#datazoneBuilderOffCanvas" aria-controls="datazoneBuilderOffCanvas">
                <i class="fa-solid fa-link me-md-2"></i><span class="d-none d-md-inline">Data URL builder</span>
            </button>
        </div>
    </div>

    <div class="table-responsive scrollbar">
        <table class="table table-hover fs-9 mb-0 border-translucent align-middle">
            <thead>
                <tr class="text-body-tertiary text-uppercase fs-10">
                    <th class="sort ps-0" data-sort="name" style="min-width: 260px;">Zone</th>
                    <th class="sort text-center" data-sort="datasets">Datasets</th>
                    <th class="sort text-center" data-sort="records">Records</th>
                    <th>Editors</th>
                    <th class="sort" data-sort="owner">Owner</th>
                    <th class="sort" data-sort="modified">Updated</th>
                    <th class="text-end pe-0"></th>
                </tr>
            </thead>
            <tbody class="list">
                @foreach($zones as $zone)
                    @php
                        $isOwner = $zone->user_id == auth()->id();
                        $editors = $zone->editor_users;
                        $extra = $editors->count() - 3;
                        $publicUrl = request()->getUrl() . route('public.zone', md5($zone->id));
                    @endphp
                    <tr>
                        <td class="ps-0 py-2">
                            <div class="d-flex align-items-center">
                                <div class="avatar avatar-m me-3 d-none d-sm-block">
                                    <div class="avatar-name rounded-3 bg-warning-subtle text-warning fw-bold">
                                        <span>{{ strtoupper(substr($zone->name, 0, 1)) }}</span>
                                    </div>
                                </div>
                                <div class="min-w-0" style="max-width: 420px;">
                                    <a class="fw-bold text-body-emphasis text-truncate d-block name" href="@route('zones.show', $zone->id)">
                                        {{ $zone->name }}
                                    </a>
                                    @if($zone->description)
                                        <span class="text-body-tertiary fs-10 text-truncate d-block description">
                                            {{ substring($zone->description, 100) }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="text-center fw-semibold datasets" data-datasets="{{ $zone->datasets_count }}">{{ number_format($zone->datasets_count) }}</td>
                        <td class="text-center fw-semibold records" data-records="{{ $zone->records_count }}">{{ number_format($zone->records_count) }}</td>
                        <td>
                            @if($editors->count())
                                <div class="avatar-group avatar-group-dense d-inline-flex align-items-center">
                                    @foreach($editors->take(3) as $editor)
                                        <div class="avatar avatar-s" data-bs-toggle="tooltip" title="{{ $editor->fullname }}">
                                            <img class="rounded-circle" src="{{ urlPath($editor->avatar) }}" alt="{{ $editor->fullname }}">
                                        </div>
                                    @endforeach
                                    @if($extra > 0)
                                        <div class="avatar avatar-s" data-bs-toggle="tooltip" title="{{ $extra }} more">
                                            <div class="avatar-name rounded-circle bg-body-secondary text-body"><span>+{{ $extra }}</span></div>
                                        </div>
                                    @endif
                                </div>
                            @else
                                <span class="text-body-tertiary fs-10">Owner only</span>
                            @endif
                        </td>
                        <td class="white-space-nowrap owner">
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar avatar-s">
                                    <img class="rounded-circle" src="{{ urlPath($zone->user->avatar) }}" alt="">
                                </div>
                                <span class="text-body-secondary">{{ $isOwner ? 'You' : $zone->user->fullname }}</span>
                            </div>
                        </td>
                        <td class="white-space-nowrap text-body-secondary modified" data-modified="{{ $zone->updated_at->timestamp }}" title="{{ $zone->updated_at->format('M j, Y H:i') }}">
                            {{ $zone->updated_at->diffForHumans() }}
                        </td>
                        <td class="text-end pe-0">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-phoenix-secondary py-0 px-2" type="button" id="zoneActions{{ $zone->id }}" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false" aria-label="Zone actions">
                                    <i class="fa-solid fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="zoneActions{{ $zone->id }}">
                                    <li>
                                        <a class="dropdown-item" href="@route('zones.show', $zone->id)">
                                            <i class="fa-solid fa-{{ $isOwner ? 'edit' : 'table' }} me-2"></i> {{ $isOwner ? 'Manage zone' : 'Open zone' }}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" data-clipboard="{{ $publicUrl }}" onclick="copyToClipboard(this.dataset.clipboard)">
                                            <i class="fa-solid fa-link me-2"></i> Copy data URL
                                        </a>
                                    </li>
                                    @if($isOwner)
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item text-danger" href="@route('zones.delete', $zone->id)" onclick="confirmDelete(event)"
                                                data-delete-msg="Forms loading data from this zone will stop receiving it. Are you sure?">
                                                <i class="fa-solid fa-trash me-2"></i> Delete zone
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

        {{-- shown by list.js when a search matches nothing --}}
        <div class="fallback d-none text-center text-body-tertiary py-5">
            <i class="fa-solid fa-magnifying-glass fs-6 mb-2 d-block"></i>
            No zones match your search
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
