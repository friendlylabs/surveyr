
<style>
    .blurred-text {
        white-space: nowrap;
        overflow: hidden;
        display: inline-block;
        max-width: 100%;
        
        /* Gradient mask for a blur-like fade effect */
        -webkit-mask-image: linear-gradient(to right, black 40%, rgba(0, 0, 0, 0));
        mask-image: linear-gradient(to right, black 40%, rgba(0, 0, 0, 0));
    }

    .p-cursor{
        cursor: pointer;
    }
</style>

<div class="row">
    @foreach($zones as $zone)
        <div class="col-xl-3 col-md-4 col-sm-6 col-12 mb-3">
            <div class="card" style="border-top: 7px solid black;">
                <div class="card-body pb-1 posotion-relative">
                    <a href="@route('zones.show', $zone->id)" class="fs-7 fw-bold text-primary">
                        {{ $zone->name }}
                    </a>                        
                    <span class="d-inline-block text-truncate w-100 mt-1">
                        <span>{{ $zone->description }}</span>
                    </span>

                    @if($zone->user_id == auth()->id())
                        <div class="position-absolute top-5 end-3">
                            <div class="dropdown">
                                <button class="btn btn-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fa-solid fa-ellipsis text-info"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="{{ route('zones.show', $zone->id) }}">
                                            <i class="fa-solid fa-edit me-2"></i>
                                            Manage Datazone
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item text-danger" onclick="confirmDelete(event)" href="{{ route('zones.delete', $zone->id) }}">
                                            <i class="fa-solid fa-trash me-2"></i>
                                            Delete Datazone
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="card-footer border-0">
                    <div class="blurred-text p-cursor" data-bs-toggle="tooltip" title="Copy to clipboard" onclick="copyToClipboard(this)">
                        {{request()->getUrl()}}@route('public.zone', md5($zone->id))
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>