@extends('layouts.app.main')
@section('content')
    <div class="content">

        <div class="row mb-3 gy-6">

            <div class="col-12 col-xxl-2">
                <div class="row align-items-center g-3 g-xxl-0 h-100 align-content-between">

                    @foreach ($stats as $stat)
                        <div class="col-12 col-sm-6 col-md-3 col-lg-6 col-xl-3 col-xxl-12 mb-3">
                            <div class="d-flex align-items-center">
                                <i class="{{ $stat['icon'] }} fs-4 lh-1"></i>
                                <div class="ms-3">
                                    <div class="d-flex align-items-end">
                                        <h2 class="mb-0 me-2 fs-4">{{ $stat['count'] }}</h2>
                                        <span class="fs-7 fw-semibold text-body">{{ $stat['title'] }}</span>
                                    </div>
                                    <p class="text-body-secondary fs-9 mb-0">{{ $stat['subtitle'] }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach

                </div>
            </div>

            <div class="col-12 col-xl-6 col-xxl-5">
                @if(count($statsChart))
                    <div class="mx-xxl-0">
                        <div id="submissionBarChart" style="width: 100%; height: 400px;"></div>
                    </div>
                @else
                    <div id="submissionBarChart" comment="error suppression" class="d-none" ></div>
                    @include('components.empty', [
                        'title' => 'No submissions yet',
                        'message' => 'Create a form and start collecting data'
                    ])
                @endif
            </div>

            <div class="col-12 col-xl-6 col-xxl-5">
                <div class="card border h-100 w-100 overflow-hidden">
                    <div class="bg-holder d-block bg-card" style="background-image:url(/assets/images/vector/welcome_bg.png);background-position: top right;"></div>
                    <div class="d-dark-none">
                        <div class="bg-holder d-none d-sm-block d-xl-none d-xxl-block bg-card"></div>
                    </div>
                    <div class="d-light-none">
                        <div class="bg-holder d-none d-sm-block d-xl-none d-xxl-block bg-card" style="background-image:url(../assets/img/spot-illustrations/dark_21.png);background-position: bottom right; background-size: auto;"></div>
                    </div>
                    <div class="card-body px-5 position-relative">
                        <h3 class="mb-5 mt-2 fs-3">Welcome to Surveyr</h3>
                        <p class="text-body-tertiary fw-semibold">
                            Create beautiful, functional forms for any purpose—contact, registration, surveys, and more. No coding needed. Build and embed forms in minutes
                        </p>

                        <p class="text-danger fw-semibold mt-4">Disclaimer</p>
                        <p class="text-body-tertiary">
                            While Surveyr is an open tool, it leverages <span class="text-info fw-bold">SurveyJS</span> for its form-building capabilities. With that being said Surveyr must strictly be used for non-commercial purposes unless you obtain a developer license from SurveyJS. For more information, please review the <a href="https://surveyjs.io/licensing" class="fw-bold text-info" target="_blank">SurveyJS License.</a>
                        </p>
                    </div>
                    <div class="card-footer border-0 py-0 px-5 z-1">
                        <p class="text-body-tertiary fw-semibold">Follow <a href="https://github.com/ibnsultan">ibnsultan </a> on Github</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-5">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-header-title">Recent Submissions</h4>
                    </div>
                    <div class="card-body" style="min-height: 400px;">
                        @if($recentSubmissions->count())
                            <div style="overflow-x:auto">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Form Name</th>
                                            <th class="text-center">Submissions</th>
                                            <th class="text-center">Pending Review</th>
                                            <th class="text-end">Last Submission</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($recentSubmissions as $submission)
                                            <tr>
                                                <td class="align-middle ps-1">
                                                    <a href="@route('forms.submissions', $submission->form_id)">
                                                        {{ $submission->form->title }}
                                                    </a>
                                                </td>
                                                <td class="align-middle text-center">{{ $submission->submission }}</td>
                                                <td class="align-middle text-center">{{ $submission->pending_review }}</td>
                                                <td class="align-middle text-end pe-1">{{ carbon()::parse($submission->latest_submission)->diffForHumans() }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>                        
                        @else
                            @include('components.empty', [
                                'title' => 'No submissions yet',
                                'message' => 'Create a form and start collecting data'
                            ])
                        @endif
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection
@script('/vendor/echarts/echarts.min.js','src')
@script('app.dashboard.scripts.charts')