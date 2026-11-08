@extends('layouts.app.main')

@section('content')
    <div class="content">

        <div class="card">
            <div class="card-body">
                @if($reports->count() > 0)
                    @include('app.reports.partials.list')
                @else
                    <div class="text-center">
                        @include('components.empty', [
                            'alertTitle' => 'No reports yet',
                            'alertMessage' => isset($form)
                                ? 'No report draws on this form yet.'
                                : 'Create a report to write up and share what your forms are collecting.'
                        ])
                        <button class="btn btn-primary btn-sm mb-4" data-bs-toggle="modal" data-bs-target="#createReportModal">
                            <i class="fa-solid fa-plus me-2"></i> New report
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>

@endsection
