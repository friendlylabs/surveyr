@extends('layouts.app.main')
@section('content')
    <div class="content">

        {{-- platform at a glance --}}
        <div class="row g-3 mb-3">
            @foreach ($stats as $stat)
                <div class="col-6 col-xl-3">
                    <a href="{{ $stat['route'] }}" class="card h-100 text-decoration-none dash-tile dash-tile-{{ $stat['tone'] }}">
                        <div class="card-body position-relative py-3">
                            <i class="ph-duotone {{ $stat['icon'] }} dash-tile-icon text-{{ $stat['tone'] }}"></i>
                            <p class="text-body-secondary fs-9 fw-semibold mb-0">{{ $stat['title'] }}</p>
                            <span class="dash-tile-value text-body-emphasis d-block">{{ number_format($stat['count']) }}</span>
                            <i class="ph-duotone {{ $stat['icon'] }} dash-tile-mark" aria-hidden="true"></i>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>

        <div class="row g-3 mb-3">
            {{-- submissions over time --}}
            <div class="col-12 col-xl-8">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-3">
                            <div>
                                <p class="text-body-tertiary text-uppercase fs-10 fw-bold mb-1">Submissions</p>
                                <div class="d-flex align-items-baseline gap-2 flex-wrap">
                                    <span class="dash-hero text-body-emphasis">{{ number_format($submissions['total']) }}</span>
                                    <span class="fs-9 text-body-secondary">
                                        {{ number_format($submissions['recent']) }} in the last 30 days
                                        
                                    </span>
                                </div>
                            </div>

                            @if($submissions['total'])
                                <div class="btn-group btn-group-sm dash-range" role="group" aria-label="Time range">
                                    <button type="button" class="btn btn-phoenix-secondary active" data-range="30">30 days</button>
                                    <button type="button" class="btn btn-phoenix-secondary" data-range="90">90 days</button>
                                    <button type="button" class="btn btn-phoenix-secondary" data-range="365">12 months</button>
                                </div>
                            @endif
                        </div>

                        @if($submissions['total'])
                            <div id="submissionsChart" style="width: 100%; height: 300px;"></div>
                        @else
                            <div class="d-flex align-items-center justify-content-center" style="min-height: 300px;">
                                @include('components.empty', [
                                    'alertTitle' => 'No submissions yet',
                                    'alertMessage' => 'Submissions to any of your forms will chart here over time.'
                                ])
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- about --}}
            <div class="col-12 col-xl-4">
                <div class="card h-100 overflow-hidden">
                    <div class="bg-holder d-block bg-card" style="background-image:url(/assets/images/vector/welcome_bg.png);background-position: top right;"></div>
                    <div class="card-body position-relative d-flex flex-column">
                        <h5 class="mb-3">Welcome to Surveyr</h5>
                        <p class="text-body-secondary fs-9">
                            Build forms, organise them into spaces, keep working data in zones and turn what you collect into shareable reports — no code needed.
                        </p>
                        <p class="text-body-secondary fs-9">
                            Collect offline with <strong>Surveyr Collect</strong> and sync when you are back online.
                            <a href="https://play.google.com/store/apps/details?id=co.surveyr.collect" class="fw-bold" target="_blank" rel="noopener">Get it on Google Play</a>
                        </p>
                        <p class="text-body-tertiary fs-12 mt-auto mb-0">
                            Surveyr is open source and builds on proprietary SurveyJS components; using it requires a SurveyJS developer license.
                            See the <a href="https://surveyjs.io/licensing" rel="nofollow" target="_blank">licensing page</a> and
                            <a href="https://surveyjs.io/Developer-License-Agreement.pdf" rel="nofollow" target="_blank">EULA</a>.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            {{-- most active forms --}}
            <div class="col-12">
                <div class="card h-100">
                    <div class="card-header d-flex align-items-center justify-content-between" style="margin: 0 1.5rem;">
                        <h5 class="card-header-title mb-0">Most active forms</h5>
                        <a href="@route('forms.list')" class="fs-9 fw-semibold">All forms</a>
                    </div>
                    <div class="card-body">
                        @if($activeForms->count())
                            @php $recentTotal = max(1, $activeForms->sum('recent_count')); @endphp
                            <div class="table-responsive scrollbar">
                                <table class="table table-hover fs-9 mb-0 align-middle" style="min-width: 760px;">
                                    <thead>
                                        <tr class="text-body-tertiary text-uppercase fs-10">
                                            <th class="ps-0">Form</th>
                                            <th>Owner</th>
                                            <th>Status</th>
                                            <th class="text-end">Total</th>
                                            <th style="width: 220px;">Last 30 days</th>
                                            <th class="text-end pe-0 white-space-nowrap">Last submission</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($activeForms as $row)
                                            @php
                                                $form = $row->form;
                                                if ($form->is_indefinite) {
                                                    [$status, $tone, $when] = ['Indefinite', 'success', null];
                                                } elseif ($form->end_date->isPast()) {
                                                    [$status, $tone, $when] = ['Closed', 'secondary', 'Ended ' . $form->end_date->diffForHumans()];
                                                } elseif ($form->start_date->isFuture()) {
                                                    [$status, $tone, $when] = ['Scheduled', 'info', 'Opens ' . $form->start_date->diffForHumans()];
                                                } else {
                                                    [$status, $tone, $when] = ['Open', 'success', 'Closes ' . $form->end_date->diffForHumans()];
                                                }
                                                $share = round($row->recent_count / $recentTotal * 100);
                                                $latest = carbon()::parse($row->latest_submission);
                                                $owner = $form->user;
                                            @endphp
                                            <tr>
                                                <td class="ps-0 py-2">
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar avatar-m me-3 d-none d-sm-block">
                                                            <div class="avatar-name rounded-3 bg-primary-subtle text-primary fw-bold">
                                                                <span>{{ strtoupper(substr($form->title, 0, 1)) }}</span>
                                                            </div>
                                                        </div>
                                                        <div class="min-w-0" style="max-width: 320px;">
                                                            <a class="fw-semibold text-body-emphasis text-truncate d-block" href="@route('forms.setup', $form->id, $form->slug)">
                                                                {{ $form->title }}
                                                            </a>
                                                            @if($when)
                                                                <span class="text-body-tertiary fs-10 d-block">{{ $when }}</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="white-space-nowrap">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <div class="avatar avatar-s">
                                                            @if($owner && $owner->avatar)
                                                                <img class="rounded-circle" src="{{ urlPath($owner->avatar) }}" alt="">
                                                            @else
                                                                <div class="avatar-name rounded-circle"><span>{{ $owner ? strtoupper(substr($owner->fullname, 0, 1)) : '-' }}</span></div>
                                                            @endif
                                                        </div>
                                                        <span class="text-body-secondary">{{ $owner ? $owner->fullname : 'Unknown' }}</span>
                                                    </div>
                                                </td>
                                                <td class="white-space-nowrap">
                                                    <span class="badge badge-phoenix badge-phoenix-{{ $tone }}">{{ $status }}</span>
                                                </td>
                                                <td class="text-end fw-semibold">{{ number_format($row->submission_count) }}</td>
                                                <td>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <span class="fw-semibold text-end" style="min-width: 2.5ch;">{{ number_format($row->recent_count) }}</span>
                                                        <div class="progress flex-grow-1" style="height: 6px;" role="progressbar" aria-label="Share of submissions in the last 30 days" aria-valuenow="{{ $share }}" aria-valuemin="0" aria-valuemax="100">
                                                            <div class="progress-bar" style="width: {{ $share }}%"></div>
                                                        </div>
                                                        <span class="text-body-tertiary fs-10" style="min-width: 3.5ch;">{{ $share }}%</span>
                                                    </div>
                                                </td>
                                                <td class="text-end pe-0 text-body-secondary white-space-nowrap" title="{{ $latest->format('M j, Y H:i') }}">
                                                    {{ $latest->diffForHumans() }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            @include('components.empty', [
                                'alertTitle' => 'No submissions yet',
                                'alertMessage' => 'Forms will rank here once they start receiving submissions.'
                            ])
                        @endif
                    </div>
                </div>
            </div>

        </div>

    </div>

    <script>
        window.dashboardSeries = {!! json_encode($series) !!};
    </script>
@endsection

@script('/vendor/echarts/echarts.min.js','src')
@script('app.dashboard.scripts.charts')
