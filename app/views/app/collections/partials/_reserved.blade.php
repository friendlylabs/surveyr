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