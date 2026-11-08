{{-- Form overview header: title, status, description, actions and the page tabs. --}}
@php
    [$status, $tone, $when] = $form->statusInfo();
    $tabs = ['overview' => 'Overview', 'submissions' => 'Submissions', 'settings' => 'Settings', 'theme' => 'Theme'];
@endphp

<header class="fo-head">
    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
        <div class="min-w-0">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <h2 class="fo-title text-truncate">{{ $form->title }}</h2>
                <span class="fo-status fo-status-{{ $tone }}" @if($when) title="{{ $when }}" @endif>{{ $status === 'Open' || $status === 'Indefinite' ? 'Live' : $status }}</span>
            </div>
            @if($form->description)
                <p class="fo-desc" title="{{ $form->description }}">{{ $form->description }}</p>
            @endif
        </div>

        <div class="d-flex align-items-center gap-2">
            <div class="dropdown">
                <button class="btn btn-phoenix-secondary btn-sm fo-icon-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="More actions">
                    <i class="fa-solid fa-ellipsis"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="@route('forms.preview', $form->id)" target="_blank"><i class="fa-solid fa-eye me-2"></i> Preview</a></li>
                    <li><a class="dropdown-item" href="@route('forms.visualize', $form->id)"><i class="fa-solid fa-chart-simple me-2"></i> Visualize</a></li>
                    <li><a class="dropdown-item" href="@route('reports.forForm', $form->id)"><i class="fa-solid fa-file-lines me-2"></i> Form reports</a></li>
                    @if($form->canBePurgedBy((int) auth()->id()))
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="@route('collections.clear', $form->id)" onclick="confirmDelete(event)"
                                data-delete-msg="Are you sure you want to clear all submissions? This action cannot be undone.">
                                <i class="fa-solid fa-trash me-2"></i> Purge submissions
                            </a>
                        </li>
                    @endif
                </ul>
            </div>

            <button type="button" class="btn btn-phoenix-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#shareFormModal">
                <i class="fa-solid fa-link me-1"></i> Share
            </button>

            @if($canEdit)
                <a class="btn btn-primary btn-sm" href="@route('forms.customize', $form->id, $form->slug)">
                    <i class="fa-solid fa-pen me-1"></i> Edit form
                </a>
            @endif
        </div>
    </div>

    <nav class="fo-tabs" role="tablist">
        @foreach($tabs as $key => $label)
            <button type="button" role="tab" data-tab="{{ $key }}" class="{{ $tab === $key ? 'active' : '' }}" aria-selected="{{ $tab === $key ? 'true' : 'false' }}">
                {{ $label }}
                @if($key === 'submissions')
                    <span class="fo-tab-count">{{ number_format($overview['total']) }}</span>
                @endif
            </button>
        @endforeach
    </nav>
</header>
