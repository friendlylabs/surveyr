{{-- Submissions tab: "All" plus the form's saved filters. Expects $savedFilters (with match_count) and $overview. --}}
<div class="fo-card p-0">
    <div class="fo-card-head px-4 pt-4">
        <div>
            <h3>Submissions</h3>
            <p>Open every response, or a saved filter shared with everyone on this form</p>
        </div>
        <button type="button" class="btn btn-primary btn-sm" onclick="FormFilterEditor.open()">
            <i class="fa-solid fa-plus me-1"></i> New filter
        </button>
    </div>

    <ul class="fo-views">
        <li>
            <a class="fo-view" href="@route('forms.submissions', $form->id)">
                <span class="fo-view-icon"><i class="fa-solid fa-layer-group"></i></span>
                <span class="fo-view-body">
                    <span class="fo-view-name">All submissions</span>
                    <span class="fo-view-desc">Every response to this form</span>
                </span>
                <span class="fo-view-count">{{ number_format($overview['total']) }}</span>
                <i class="fa-solid fa-chevron-right fo-view-go"></i>
            </a>
        </li>

        @foreach($savedFilters as $saved)
            <li>
                <a class="fo-view" href="@route('forms.submissions', $form->id)?view={{ $saved->id }}">
                    <span class="fo-view-icon"><i class="fa-solid fa-filter"></i></span>
                    <span class="fo-view-body">
                        <span class="fo-view-name">{{ $saved->name }}</span>
                        @if($saved->description)
                            <span class="fo-view-desc">{{ $saved->description }}</span>
                        @endif
                        <span class="fo-chips" data-filter-chips="{{ json_encode($saved->filters ?: new \stdClass) }}"></span>
                    </span>
                    <span class="fo-view-meta">updated {{ $saved->updated_at->diffForHumans() }}</span>
                    <span class="fo-view-count">{{ number_format($saved->match_count) }}</span>
                </a>
                <div class="dropdown fo-view-menu">
                    <button class="btn btn-sm btn-phoenix-secondary py-0 px-2" type="button" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false" aria-label="Filter actions">
                        <i class="fa-solid fa-ellipsis-v"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="@route('forms.submissions', $form->id)?view={{ $saved->id }}"><i class="fa-solid fa-table me-2"></i> Open</a></li>
                        <li><a class="dropdown-item" href="@route('forms.visualize', $form->id)?view={{ $saved->id }}"><i class="fa-solid fa-chart-simple me-2"></i> Visualize</a></li>
                        <li>
                            <button type="button" class="dropdown-item" data-saved-filter="{{ json_encode($saved->only(['id', 'name', 'description', 'filters'])) }}"
                                onclick="FormFilterEditor.open(JSON.parse(this.dataset.savedFilter))">
                                <i class="fa-solid fa-pen me-2"></i> Edit
                            </button>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="@route('collections.filters.delete', $saved->id)" onclick="confirmDelete(event)"
                                data-delete-msg="Delete the saved filter &quot;{{ $saved->name }}&quot;? Submissions are not affected.">
                                <i class="fa-solid fa-trash me-2"></i> Delete
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
        @endforeach
    </ul>

    @if(!$savedFilters->count())
        <p class="fo-views-hint">
            Save a filter to come back to a slice of responses, for example last month's, or only the ones flagged for follow-up.
        </p>
    @endif
</div>
