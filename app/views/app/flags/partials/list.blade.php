{{-- Flag groups list (List.js via Phoenix data-list). Expects $groups with forms_count and user. --}}
<div id="flagGroupsList" data-list='{"valueNames":["name","owner",{"name":"forms","attr":"data-forms"},{"name":"modified","attr":"data-modified"}],"page":10,"pagination":true}'>

    <div class="row g-2 align-items-center mb-3">
        <div class="col-12 col-md-5 col-lg-4">
            <div class="search-box w-100">
                <form class="position-relative" onsubmit="return false">
                    <input class="form-control search-input search form-control-sm" type="search" placeholder="Search flag groups" aria-label="Search flag groups">
                    <span class="fas fa-search search-box-icon"></span>
                </form>
            </div>
        </div>
        <div class="col text-end text-body-tertiary fs-9 d-none d-md-block">
            {{ $groups->count() }} {{ $groups->count() === 1 ? 'group' : 'groups' }}
        </div>
    </div>

    <div class="table-responsive scrollbar">
        <table class="table table-hover fs-9 mb-0 border-translucent align-middle">
            <thead>
                <tr class="text-body-tertiary text-uppercase fs-10">
                    <th class="sort ps-0" data-sort="name" style="min-width: 220px;">Group</th>
                    <th>Flags</th>
                    <th class="sort text-center" data-sort="forms">Forms</th>
                    <th class="sort" data-sort="owner">Created by</th>
                    <th class="sort" data-sort="modified">Updated</th>
                    <th class="text-end pe-0"></th>
                </tr>
            </thead>
            <tbody class="list">
                @foreach($groups as $group)
                    @php $canManage = \App\Models\FlagGroup::canManage($group); @endphp
                    <tr>
                        <td class="ps-0 py-2">
                            <span class="fw-bold text-body-emphasis d-block name">{{ $group->name }}</span>
                            @if($group->description)
                                <span class="text-body-tertiary fs-10 text-truncate d-block" style="max-width: 360px;">{{ $group->description }}</span>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex flex-wrap gap-1">
                                @foreach($group->flags as $flag)
                                    <span class="flag-pill" style="--flag: {{ $flag['color'] }}"><i></i>{{ $flag['label'] }}</span>
                                @endforeach
                            </div>
                        </td>
                        <td class="text-center fw-semibold forms" data-forms="{{ $group->forms_count }}">{{ $group->forms_count }}</td>
                        <td class="white-space-nowrap text-body-secondary owner">
                            {{ $group->user_id == auth()->id() ? 'You' : ($group->user->fullname ?? '—') }}
                        </td>
                        <td class="white-space-nowrap text-body-secondary modified" data-modified="{{ $group->updated_at->timestamp }}" title="{{ $group->updated_at->format('M j, Y H:i') }}">
                            {{ $group->updated_at->diffForHumans() }}
                        </td>
                        <td class="text-end pe-0">
                            @if($canManage)
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-phoenix-secondary py-0 px-2" type="button" id="flagActions{{ $group->id }}" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false" aria-label="Flag group actions">
                                        <i class="fa-solid fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="flagActions{{ $group->id }}">
                                        <li>
                                            <button type="button" class="dropdown-item" data-flag-group="{{ json_encode($group->only(['id', 'name', 'description', 'flags'])) }}"
                                                onclick="FlagGroupEditor.open(JSON.parse(this.dataset.flagGroup))">
                                                <i class="fa-solid fa-edit me-2"></i> Edit group
                                            </button>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item text-danger" href="@route('flags.delete', $group->id)" onclick="confirmDelete(event)"
                                                data-delete-msg="{{ $group->forms_count ? 'This group is attached to ' . $group->forms_count . ' ' . ($group->forms_count === 1 ? 'form' : 'forms') . '. Their flag column, and the flags set on their submissions, will be removed.' : 'Delete this flag group?' }}">
                                                <i class="fa-solid fa-trash me-2"></i> Delete group
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="fallback d-none text-center text-body-tertiary py-5">
            <i class="fa-solid fa-magnifying-glass fs-6 mb-2 d-block"></i>
            No flag groups match your search
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
