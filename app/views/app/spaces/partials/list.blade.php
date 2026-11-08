{{-- Spaces list (List.js via Phoenix data-list). Expects $spaces with forms_count and member_users. --}}
<div id="spacesList" data-list='{"valueNames":["name","description","owner","role",{"name":"forms","attr":"data-forms"},{"name":"members","attr":"data-members"},{"name":"modified","attr":"data-modified"}],"page":10,"pagination":true,"filter":{"key":"role"}}'>

    {{-- toolbar: search + role filter --}}
    <div class="row g-2 align-items-center mb-3">
        <div class="col-12 col-md-5 col-lg-4">
            <div class="search-box w-100">
                <form class="position-relative" onsubmit="return false">
                    <input class="form-control search-input search form-control-sm" type="search" placeholder="Search spaces" aria-label="Search spaces">
                    <span class="fas fa-search search-box-icon"></span>
                </form>
            </div>
        </div>
        <div class="col-auto">
            <select class="form-select form-select-sm" data-list-filter aria-label="Filter by role">
                <option value="">All spaces</option>
                <option value="owner">Spaces I own</option>
                <option value="member">Shared with me</option>
            </select>
        </div>
        <div class="col text-end text-body-tertiary fs-9 d-none d-md-block">
            {{ $spaces->count() }} {{ $spaces->count() === 1 ? 'space' : 'spaces' }}
        </div>
    </div>

    <div class="table-responsive scrollbar">
        <table class="table table-hover fs-9 mb-0 border-translucent align-middle">
            <thead>
                <tr class="text-body-tertiary text-uppercase fs-10">
                    <th class="sort ps-0" data-sort="name" style="min-width: 260px;">Space</th>
                    <th class="sort text-center" data-sort="forms">Forms</th>
                    <th class="sort" data-sort="members">Members</th>
                    <th class="sort" data-sort="owner">Owner</th>
                    <th class="sort" data-sort="modified">Updated</th>
                    <th class="text-end pe-0"></th>
                </tr>
            </thead>
            <tbody class="list">
                @foreach($spaces as $space)
                    @php
                        $isOwner = $space->user_id == auth()->id();
                        $members = $space->member_users;
                        $extra = $members->count() - 3;
                    @endphp
                    <tr>
                        <td class="ps-0 py-2">
                            <div class="d-flex align-items-center">
                                <div class="avatar avatar-m me-3 d-none d-sm-block">
                                    <div class="avatar-name rounded-3 text-white fw-bold" style="background: {{ $space->color }};">
                                        <span>{{ strtoupper(substr($space->name, 0, 1)) }}</span>
                                    </div>
                                </div>
                                <div class="min-w-0" style="max-width: 420px;">
                                    <a class="fw-bold text-body-emphasis text-truncate d-block name" href="@route('spaces.show', $space->id)">
                                        {{ $space->name }}
                                    </a>
                                    @if($space->description)
                                        <span class="text-body-tertiary fs-10 text-truncate d-block description">
                                            {{ substring($space->description, 100) }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="text-center">
                            <a class="fw-semibold text-body forms" href="@route('spaces.show', $space->id)" data-forms="{{ $space->forms_count }}">{{ $space->forms_count }}</a>
                        </td>
                        <td class="members" data-members="{{ $members->count() }}">
                            @if($members->count())
                                <div class="avatar-group avatar-group-dense d-inline-flex align-items-center">
                                    @foreach($members->take(3) as $member)
                                        <div class="avatar avatar-s" data-bs-toggle="tooltip" title="{{ $member->fullname }}">
                                            <img class="rounded-circle" src="{{ urlPath($member->avatar) }}" alt="{{ $member->fullname }}">
                                        </div>
                                    @endforeach
                                    @if($extra > 0)
                                        <div class="avatar avatar-s" data-bs-toggle="tooltip" title="{{ $extra }} more">
                                            <div class="avatar-name rounded-circle bg-body-secondary text-body"><span>+{{ $extra }}</span></div>
                                        </div>
                                    @endif
                                </div>
                            @else
                                <span class="text-body-tertiary fs-10">Only you</span>
                            @endif
                        </td>
                        <td class="white-space-nowrap owner">
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar avatar-s">
                                    <img class="rounded-circle" src="{{ urlPath($space->user->avatar) }}" alt="">
                                </div>
                                <span class="text-body-secondary">{{ $isOwner ? 'You' : $space->user->fullname }}</span>
                            </div>
                            <span class="d-none role">{{ $isOwner ? 'owner' : 'member' }}</span>
                        </td>
                        <td class="white-space-nowrap text-body-secondary modified" data-modified="{{ $space->updated_at->timestamp }}" title="{{ $space->updated_at->format('M j, Y H:i') }}">
                            {{ $space->updated_at->diffForHumans() }}
                        </td>
                        <td class="text-end pe-0">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-phoenix-secondary py-0 px-2" type="button" id="spaceActions{{ $space->id }}" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false" aria-label="Space actions">
                                    <i class="fa-solid fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="spaceActions{{ $space->id }}">
                                    <li>
                                        <a class="dropdown-item" href="@route('spaces.show', $space->id)">
                                            <i class="fa-solid fa-{{ $isOwner ? 'edit' : 'folder-open' }} me-2"></i> {{ $isOwner ? 'Manage space' : 'Open space' }}
                                        </a>
                                    </li>
                                    @if($isOwner)
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item text-danger" href="@route('spaces.delete', $space->id)" onclick="confirmDelete(event)"
                                                data-delete-msg="Deleting this space removes it from its forms and members. The forms themselves are kept.">
                                                <i class="fa-solid fa-trash me-2"></i> Delete space
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
            No spaces match your search
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
