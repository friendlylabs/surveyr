@extends('layouts.app.main')

@section('content')
    <div class="content">

        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h3 class="fs-7 mb-1">Flags</h3>
                <p class="text-body-tertiary fs-9 mb-0">Flag groups mark submissions for follow-up. Attach a group to a form from its setup page.</p>
            </div>
            @if($groups->count())
                <button class="btn btn-primary btn-sm" onclick="FlagGroupEditor.open()">
                    <i class="fa-solid fa-plus me-2"></i> New flag group
                </button>
            @endif
        </div>

        <div class="card">
            <div class="card-body">
                @if($groups->count())
                    @include('app.flags.partials.list')
                @else
                    <div class="text-center">
                        @include('components.empty', [
                            'alertTitle' => 'No flag groups yet',
                            'alertMessage' => 'Create a group of flags, then attach it to the forms whose submissions need them.'
                        ])
                        <button class="btn btn-primary btn-sm mb-4" onclick="FlagGroupEditor.open()">
                            <i class="fa-solid fa-plus me-2"></i> New flag group
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @include('app.flags.partials.create')
@endsection

@script('app.flags.scripts.editor')
