@php
    // one query for every collaborator on the page instead of one per row
    $collaboratorUsers = $collabCol ? \App\Models\Form::collaboratorsOf($forms) : collect();
@endphp

<div id="formsList" data-list='{"valueNames":["title","description","responses","reports","status",{"name":"modified","attr":"data-modified"}],"page":10,"pagination":true,"filter":{"key":"status"}}'>

    {{-- toolbar: search + status filter --}}
    <div class="row g-2 align-items-center mb-3">
        <div class="col-12 col-md-5 col-lg-4">
            <div class="search-box w-100">
                <form class="position-relative" onsubmit="return false">
                    <input class="form-control search-input search form-control-sm" type="search" placeholder="Search forms" aria-label="Search forms">
                    <span class="fas fa-search search-box-icon"></span>
                </form>
            </div>
        </div>
        <div class="col-auto">
            <select class="form-select form-select-sm" data-list-filter aria-label="Filter by status">
                <option value="">All statuses</option>
                <option value="open">Open</option>
                <option value="scheduled">Scheduled</option>
                <option value="closed">Closed</option>
                <option value="indefinite">Indefinite</option>
            </select>
        </div>
        <div class="col text-end text-body-tertiary fs-9 d-none d-md-block">
            {{ $forms->count() }} {{ $forms->count() === 1 ? 'form' : 'forms' }}
        </div>
    </div>

    <div class="table-responsive scrollbar">
        <table class="table table-hover fs-9 mb-0 border-translucent align-middle">
            <thead>
                <tr class="text-body-tertiary text-uppercase fs-10">
                    <th class="sort ps-0" data-sort="title" style="min-width: 260px;">Form</th>
                    <th class="sort text-center" data-sort="responses">Responses</th>
                    <th class="sort text-center" data-sort="reports">Reports</th>
                    @if($collabCol)<th class="text-center">Team</th>@endif
                    <th class="sort" data-sort="status">Status</th>
                    <th class="sort" data-sort="modified">Modified</th>
                    <th class="text-end pe-0"></th>
                </tr>
            </thead>
            <tbody class="list">
                @foreach($forms as $form)
                    @php
                        if ($form->is_indefinite) {
                            [$status, $tone, $when] = ['Indefinite', 'success', null];
                        } elseif ($form->end_date->isPast()) {
                            [$status, $tone, $when] = ['Closed', 'secondary', 'Ended ' . $form->end_date->diffForHumans()];
                        } elseif ($form->start_date->isFuture()) {
                            [$status, $tone, $when] = ['Scheduled', 'info', 'Opens ' . $form->start_date->diffForHumans()];
                        } else {
                            [$status, $tone, $when] = ['Open', 'success', 'Closes ' . $form->end_date->diffForHumans()];
                        }
                        $responses = $form->collections_count ?? $form->collections()->count();
                        $reports   = $form->reports_count ?? $form->reports()->count();
                    @endphp
                    <tr>
                        <td class="ps-0 py-2">
                            <div class="d-flex align-items-center">
                                <div class="avatar avatar-m me-3 d-none d-sm-block">
                                    <div class="avatar-name rounded-3 bg-primary-subtle text-primary fw-bold">
                                        <span>{{ strtoupper(substr($form->title, 0, 1)) }}</span>
                                    </div>
                                </div>
                                <div class="min-w-0" style="max-width: 420px;">
                                    <a class="fw-bold text-body-emphasis text-truncate d-block title" href="@route('forms.submissions', $form->id, $form->slug)">
                                        {{ $form->title }}
                                    </a>
                                    @if($form->description)
                                        <span class="text-body-tertiary fs-10 text-truncate d-block description">
                                            {{ substring($form->description, 100) }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="text-center">
                            <a class="fw-semibold text-body responses" href="@route('forms.submissions', $form->id, $form->slug)">{{ $responses }}</a>
                        </td>
                        <td class="text-center">
                            <a class="fw-semibold text-body reports" href="@route('reports.list', $form->id)">{{ $reports }}</a>
                        </td>
                        @if($collabCol)
                            <td class="text-center">
                                @php
                                    $collaborators = collect($form->collaborators)
                                        ->map(fn ($id) => $collaboratorUsers->get($id))
                                        ->filter();
                                    $extra = $collaborators->count() - 2;
                                @endphp
                                <div class="avatar-group avatar-group-dense d-inline-flex align-items-center">
                                    <div class="avatar avatar-s" data-bs-toggle="tooltip" title="{{ $form->user->fullname }} (owner)">
                                        <img class="rounded-circle" src="{{ urlPath($form->user->avatar) }}" alt="{{ $form->user->fullname }}">
                                    </div>
                                    @foreach($collaborators->take(2) as $collaborator)
                                        <div class="avatar avatar-s" data-bs-toggle="tooltip" title="{{ $collaborator->fullname }}">
                                            <img class="rounded-circle" src="{{ urlPath($collaborator->avatar) }}" alt="{{ $collaborator->fullname }}">
                                        </div>
                                    @endforeach
                                    @if($extra > 0)
                                        <div class="avatar avatar-s" data-bs-toggle="tooltip" title="{{ $extra }} more">
                                            <div class="avatar-name rounded-circle bg-body-secondary text-body"><span>+{{ $extra }}</span></div>
                                        </div>
                                    @endif
                                </div>
                            </td>
                        @endif
                        <td class="white-space-nowrap">
                            <span class="badge badge-phoenix badge-phoenix-{{ $tone }} status">{{ $status }}</span>
                            @if($when)
                                <span class="d-block text-body-tertiary fs-10 mt-1">{{ $when }}</span>
                            @endif
                        </td>
                        <td class="white-space-nowrap text-body-secondary modified" data-modified="{{ $form->updated_at->timestamp }}" title="{{ $form->updated_at->format('M j, Y H:i') }}">
                            {{ $form->updated_at->diffForHumans() }}
                        </td>
                        <td class="text-end pe-0">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-phoenix-secondary py-0 px-2" type="button" id="formActions{{ $form->id }}" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false" aria-label="Form actions">
                                    <i class="fa-solid fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="formActions{{ $form->id }}">
                                    <li>
                                        <a class="dropdown-item" href="@route('forms.preview', $form->id, $form->slug)">
                                            <i class="fa-solid fa-eye me-2"></i> Preview
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="@route('forms.submissions', $form->id, $form->slug)">
                                            <i class="fa-solid fa-chart-line me-2"></i> Submissions
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="@route('reports.list', $form->id, $form->slug)">
                                            <i class="fa-solid fa-align-left me-2"></i> Reports
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item" href="@route('forms.customize', $form->id, $form->slug)">
                                            <i class="fa-solid fa-edit me-2"></i> Edit Form
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="@route('forms.setup', $form->id, $form->slug)">
                                            <i class="fa-solid fa-cogs me-2"></i> Settings
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item text-danger" href="@route('forms.delete', $form->id)" onclick="confirmDelete(event)"
                                            data-delete-msg="Deleting this form will delete all its submissions. Are you sure you want to proceed?">
                                            <i class="fa-solid fa-trash me-2"></i> Delete Form
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
            No forms match your search
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
