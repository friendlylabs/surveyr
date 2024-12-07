@extends('layouts.app.main')
@section('content')
    <div class="content pb-0">
        <div class="row">
            <div class="col-12 mb-4 position-relative">
                <h3 class="fs-7">{{ $form->title }}</h3>

                <div class="position-absolute end-5 top-0">
                    <!-- export to csv -->
                    <a href="javascript:void(0)" class="btn btn-primary btn-sm" id="exportTableToCsv" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Export to CSV">
                        <i class="fa-solid fa-download"></i>
                    </a>

                    <a href="@route('forms.visualize', $form->id)" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-chart d-inline d-md-none"></i>
                        <span class="d-none d-md-inline">Visualize</span>
                    </a>                    
                </div>
            </div>
        </div>


        @if($collections->count())
            <div class="mt-4 mb-0 mx-n4 px-4 mx-lg-n6 px-lg-6 bg-body-emphasis pt-3 pb-3 border-y h-100" style="min-height: calc(100vh - 168px);">
                <div style="overflow-x: auto;">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th></th>
                                <th>Review</th>
                                @foreach($questions as $key => $question)
                                    <th style="max-width: 300px; overflow-x:auto;">
                                        <div class="">
                                            <span class="" data-bs-toggle="tooltip" title="{{$question}}">
                                                {{ $key }}
                                            </span>
                                        </div>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            <!-- $collections -->
                            @foreach($collections as $collection)
                                <tr>
                                    <td>
                                        <div class="ps-1" style="max-width:200px">
                                            <a href="{{ route('collections.show', $collection->id) }}" class="fw-bold" data-bs-toggle="tooltip" title="View Submission">
                                                <i class="fa-solid fa-eye"></i>
                                            </a>
                                        </div>
                                    </td>
                                    <td>{{ ucfirst($collection->review) }}</td>
                                    @foreach($questions as $key => $question)
                                        <td>
                                            <div class="ps-1" style="max-width:200px">
                                                @if(isset($collection->submission[$key]))
                                                    @php
                                                        $currentValue = $collection->submission[$key];

                                                        // Loop until we find a non-array value
                                                        while (is_array($currentValue)) {
                                                            $currentValue = reset($currentValue); // If it's an array, get the first element
                                                        }
                                                    @endphp

                                                    {{-- Display the final value --}}
                                                    <div>{!! is_array($currentValue) ? '-' : strip_tags($currentValue) !!}</div>
                                                @else
                                                    -
                                                @endif
                                            </div>
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach                                        
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="py-5">
                @include('components.empty', [
                    'emptyTitle' => 'No records Found',
                    'emptyText' => 'No submissions have been made to this form yet.'
                ])
            </div>
        @endif
    </div>
@endsection
@script('app.collections.scripts.list')