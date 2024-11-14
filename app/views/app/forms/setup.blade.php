@extends('layouts.app.main')

@style('/vendor/flatpickr/flatpickr.min.css','src')
<style>
    #qrcode img {
        display: unset !important;
        margin: 1rem auto;
        border: 2px solid #368d7b;
        padding: 6px;
        border-radius: 5px;
    }
</style>

@section('content')
    <div class="content">
        <div class="row">
            <div class="col-12 mb-4 position-relative">
                <h3 class="fs-7">Form Settings</h3>
                <p class="text-body-tertiary">
                    Configure your form settings.
                </p>

                <div class="position-absolute end-5 top-0">
                    <a href="{{ route('forms.customize', $form->id, $form->slug) }}" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-plus d-inline d-md-none"></i>
                        <span class="d-none d-md-inline">Customize Form</span>
                    </a>
                </div>
            </div>
        </div>

        <div class="row">

            <div class="col-xl-3 col-md-4 col-sm-12 mb-3">
                <div class="card profile-menu-card">
                    <div class="card-body px-0 py-0">
                        <div class="text-center">
                            <div class="mx-auto" id="qrcode"></div>
                        </div>

                        @include('app.forms.partials.setup-overview')
                    </div>
                </div>
            </div>

            <div class="col-xl-9 col-md-8 col-sm-12">
                @include('app.forms.partials.setup-general')
                @include('app.forms.partials.setup-access')
                @include('app.forms.partials.setup-submission')
            </div>
        </div>
    </div>
@endsection
@script('/vendor/flatpickr/flatpickr.min.js','src')
@script('/vendor/qrcode/qrcode.min.js','src')
@script('app.forms.scripts.setup')