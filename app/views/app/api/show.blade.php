@extends('layouts.app.main')

@section('content')
    @php
        $expiresAt = $apiKey->expiresAt();
        if ($apiKey->isExpired()) {
            [$status, $tone] = ['Expired', 'secondary'];
        } elseif (!$stats['last_used']) {
            [$status, $tone] = ['Unused', 'warning'];
        } else {
            [$status, $tone] = ['Active', 'success'];
        }
    @endphp

    <div class="content">

        {{-- key summary --}}
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
                    <div class="d-flex align-items-center min-w-0">
                        <div class="avatar avatar-xl me-3 d-none d-sm-block">
                            <div class="avatar-name rounded-3 bg-primary-subtle text-primary">
                                <i class="fa-solid fa-key fs-7"></i>
                            </div>
                        </div>
                        <div class="min-w-0">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <h4 class="mb-0 text-truncate">{{ $apiKey->name }}</h4>
                                <span class="badge badge-phoenix badge-phoenix-{{ $tone }}">{{ $status }}</span>
                            </div>
                            <div class="d-flex align-items-center gap-2 fs-9 mt-1">
                                <code class="text-body-tertiary">{{ $apiKey->prefix() }}</code>
                                <a href="javascript:void(0)" class="text-body-tertiary" data-clipboard="{{ $apiKey->token }}"
                                    data-bs-toggle="tooltip" title="Copy key" onclick="copyToClipboard(this.dataset.clipboard)" aria-label="Copy key">
                                    <i class="fa-regular fa-copy"></i>
                                </a>
                            </div>
                            <div class="text-body-tertiary fs-10 mt-2">
                                Issued {{ $apiKey->created_at->format('M j, Y') }} by {{ $apiKey->user->fullname }}
                                @if($expiresAt)
                                    &bull; {{ $apiKey->isExpired() ? 'Expired' : 'Expires' }} {{ $expiresAt->format('M j, Y') }}
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        @if($activities->count())
                            <a class="btn btn-phoenix-secondary btn-sm" href="@route('keys.activity.clear', $apiKey->id)" onclick="confirmDelete(event)"
                                data-delete-msg="This clears the activity log for this key. The key itself keeps working.">
                                <i class="fa-solid fa-broom me-2"></i> Clear log
                            </a>
                        @endif
                        <a class="btn btn-phoenix-danger btn-sm" href="@route('keys.revoke', $apiKey->id)" onclick="confirmDelete(event)"
                            data-delete-msg="Revoking this key stops every integration using it and deletes its activity log. Are you sure?">
                            <i class="fa-solid fa-ban me-2"></i> Revoke
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- usage tiles --}}
        <div class="row g-3 mb-4">
            @foreach([
                ['label' => 'Total requests', 'value' => number_format($stats['total']), 'icon' => 'fa-wave-square', 'tone' => 'primary'],
                ['label' => 'Last 7 days', 'value' => number_format($stats['week']), 'icon' => 'fa-calendar-week', 'tone' => 'info'],
                ['label' => 'Errors', 'value' => number_format($stats['errors']), 'icon' => 'fa-triangle-exclamation', 'tone' => $stats['errors'] ? 'danger' : 'secondary'],
                ['label' => 'Last used', 'value' => $stats['last_used'] ? $stats['last_used']->diffForHumans() : 'Never', 'icon' => 'fa-clock', 'tone' => 'success'],
            ] as $tile)
                <div class="col-6 col-lg-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center gap-3">
                                <div class="avatar avatar-m d-none d-sm-block">
                                    <div class="avatar-name rounded-circle bg-{{ $tile['tone'] }}-subtle text-{{ $tile['tone'] }}">
                                        <i class="fa-solid {{ $tile['icon'] }} mt-2"></i>
                                    </div>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-body-tertiary text-uppercase fs-10 fw-bold mb-0">{{ $tile['label'] }}</p>
                                    <h5 class="mb-0 text-truncate">{{ $tile['value'] }}</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- activity log --}}
        <div class="card">
            <div class="card-body">
                @if($activities->count())
                    @include('app.api.partials.activity')
                @else
                    <div class="text-center">
                        @include('components.empty', [
                            'alertTitle' => 'No activity yet',
                            'alertMessage' => 'Requests made with this key will show up here, with the endpoint, origin and response status.'
                        ])
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
