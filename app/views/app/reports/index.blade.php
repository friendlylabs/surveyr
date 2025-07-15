@extends('layouts.app.main')
@section('content')
    <div class="content pb-0">
        <div class="row">
            <div class="col-12 mb-4 position-relative">
                <h3 class="fs-7">{{ $form->title }}</h3>

                <div class="position-absolute end-5 top-0">
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createReportModal">
                        <i class="fa fa-plus d-md-none"></i>
                        <span class="d-none d-md-inline">Create Report</span>
                    </button>
                </div>
            </div>
        </div>

        @if($reports->count())
            <div class="mt-4 mb-0 mx-n4 pt-4 mx-lg-n6 px-lg-6 bg-body-emphasis border-y"
                style="height:calc(100vh - 168px);">
                <div class="card border-0 p-0">
                    <div class="card-body p-0">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Created By</th>
                                    <th>Last Updated</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($reports as $report)
                                    <tr>
                                        <td>
                                            <a href="@route('reports.show', $report->id)">{{ $report->title }}</a>
                                        </td>
                                        <td>{{ $report->user->fullname }}</td>
                                        <td>{{ $report->updated_at->diffForHumans() }}</td>
                                        <td class="text-end">
                                            <!-- dropdown -->
                                            <div class="dropdown">
                                                <button class="btn btn-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fa fa-ellipsis-v"></i>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li>
                                                        <a class="dropdown-item" href="@route('reports.show', $report->id)">
                                                            <i class="fa fa-eye me-2"></i>
                                                            View Report
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="@route('reports.edit', $report->id)">
                                                            <i class="fa fa-pencil-alt me-2"></i>
                                                            Edit Report
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a href="@route('reports.delete', $report->id)" class="dropdown-item text-danger" onclick="confirmDelete(event)">
                                                            <i class="fa fa-trash me-2"></i>
                                                            Delete Report
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @else
            <div class="py-5 my-5">
                @include('components.empty', [
                    'alertTitle' => 'No records Found',
                    'alertMessage' => 'No reports have been created for this form yet.'
                ])
            </div>
        @endif
    </div>

    @include('app.reports.partials.add')
@endsection