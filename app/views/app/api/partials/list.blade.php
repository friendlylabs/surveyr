{{-- API keys list (List.js via Phoenix data-list). Expects $apiKeys from ApiKey::by() with activities_count / activities_max_created_at. --}}
<div id="apiKeysList" data-list='{"valueNames":["name","prefix","status",{"name":"requests","attr":"data-requests"},{"name":"used","attr":"data-used"},{"name":"created","attr":"data-created"}],"page":10,"pagination":true}'>

    {{-- toolbar: search + issue --}}
    <div class="row g-2 align-items-center mb-3">
        <div class="col-12 col-md-5 col-lg-4">
            <div class="search-box w-100">
                <form class="position-relative" onsubmit="return false">
                    <input class="form-control search-input search form-control-sm" type="search" placeholder="Search keys" aria-label="Search keys">
                    <span class="fas fa-search search-box-icon"></span>
                </form>
            </div>
        </div>
        <div class="col text-end">
            <button class="btn btn-primary btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#createApiKeyModal">
                <i class="fa-solid fa-plus me-md-2"></i><span class="d-none d-md-inline">Issue key</span>
            </button>
        </div>
    </div>

    <div class="table-responsive scrollbar">
        <table class="table table-hover fs-9 mb-0 border-translucent align-middle">
            <thead>
                <tr class="text-body-tertiary text-uppercase fs-10">
                    <th class="sort ps-0" data-sort="name" style="min-width: 260px;">Key</th>
                    <th class="sort text-center" data-sort="requests">Requests</th>
                    <th class="sort" data-sort="used">Last used</th>
                    <th class="sort" data-sort="status">Status</th>
                    <th class="sort" data-sort="created">Issued</th>
                    <th class="text-end pe-0"></th>
                </tr>
            </thead>
            <tbody class="list">
                @foreach($apiKeys as $key)
                    @php
                        $lastUsed = $key->lastUsedAt();
                        $expiresAt = $key->expiresAt();
                        if ($key->isExpired()) {
                            [$status, $tone, $when] = ['Expired', 'secondary', 'Expired ' . $expiresAt->diffForHumans()];
                        } elseif (!$lastUsed) {
                            [$status, $tone, $when] = ['Unused', 'warning', $expiresAt ? 'Expires ' . $expiresAt->diffForHumans() : null];
                        } else {
                            [$status, $tone, $when] = ['Active', 'success', $expiresAt ? 'Expires ' . $expiresAt->diffForHumans() : null];
                        }
                    @endphp
                    <tr>
                        <td class="ps-0 py-2">
                            <div class="d-flex align-items-center">
                                <div class="avatar avatar-m me-3 d-none d-sm-block">
                                    <div class="avatar-name rounded-3 bg-primary-subtle text-primary">
                                        <i class="fa-solid fa-key fs-9 mt-2"></i>
                                    </div>
                                </div>
                                <div class="min-w-0" style="max-width: 420px;">
                                    <a class="fw-bold text-body-emphasis text-truncate d-block name" href="@route('keys.show', $key->id)">
                                        {{ $key->name }}
                                    </a>
                                    <span class="d-flex align-items-center gap-2 fs-10">
                                        <code class="text-body-tertiary prefix">{{ $key->prefix() }}</code>
                                        <a href="javascript:void(0)" class="text-body-tertiary" data-clipboard="{{ $key->token }}"
                                            data-bs-toggle="tooltip" title="Copy key" onclick="copyToClipboard(this.dataset.clipboard)" aria-label="Copy key">
                                            <i class="fa-regular fa-copy"></i>
                                        </a>
                                    </span>
                                </div>
                            </div>
                        </td>
                        <td class="text-center">
                            <a class="fw-semibold text-body requests" href="@route('keys.show', $key->id)" data-requests="{{ $key->activities_count }}">
                                {{ number_format($key->activities_count) }}
                            </a>
                        </td>
                        <td class="white-space-nowrap text-body-secondary used" data-used="{{ $lastUsed ? $lastUsed->timestamp : 0 }}"
                            title="{{ $lastUsed ? $lastUsed->format('M j, Y H:i') : '' }}">
                            {{ $lastUsed ? $lastUsed->diffForHumans() : 'Never' }}
                        </td>
                        <td class="white-space-nowrap">
                            <span class="badge badge-phoenix badge-phoenix-{{ $tone }} status">{{ $status }}</span>
                            @if($when)
                                <span class="d-block text-body-tertiary fs-10 mt-1">{{ $when }}</span>
                            @endif
                        </td>
                        <td class="white-space-nowrap text-body-secondary created" data-created="{{ $key->created_at->timestamp }}" title="{{ $key->created_at->format('M j, Y H:i') }}">
                            {{ $key->created_at->diffForHumans() }}
                        </td>
                        <td class="text-end pe-0">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-phoenix-secondary py-0 px-2" type="button" id="keyActions{{ $key->id }}" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false" aria-label="Key actions">
                                    <i class="fa-solid fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="keyActions{{ $key->id }}">
                                    <li>
                                        <a class="dropdown-item" href="@route('keys.show', $key->id)">
                                            <i class="fa-solid fa-wave-square me-2"></i> View activity
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" data-clipboard="{{ $key->token }}" onclick="copyToClipboard(this.dataset.clipboard)">
                                            <i class="fa-regular fa-copy me-2"></i> Copy key
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item text-danger" href="@route('keys.revoke', $key->id)" onclick="confirmDelete(event)"
                                            data-delete-msg="Revoking this key stops every integration using it and deletes its activity log. Are you sure?">
                                            <i class="fa-solid fa-ban me-2"></i> Revoke key
                                        </a>
                                    </li>
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
            No keys match your search
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
