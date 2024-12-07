@extends('layouts.app.main')

@style('/vendor/flatpickr/flatpickr.min.css','src')
<style>
    #qrcode {
        margin: 1rem auto;
        border: 2px solid #368d7b;
        padding: 6px;
        border-radius: 5px;
        width: 166px;
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
                    <a href="{{ route('forms.customize', $form->id, $form->slug) }}" class="btn btn-outline-dark" target="_blank"
                        data-bs-toggle="tooltip" data-bs-placement="bottom" title="Customize Form">
                        <i class="fa-solid fa-gear"></i>
                    </a>
                    <a class="btn btn-primary" href="@route('forms.preview', $form->id, $form->slug)" target="_blank">
                        <i class="fa-solid fa-eye d-inline d-md-none"></i>
                        <span class="d-none d-md-inline fs-12">Preview</span>
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

                        <!-- download #qrcode img -->
                        <div class="text-center mb-3">
                            <a href="javascript:void(0)" class="btn btn-sm btn-outline-primary" onclick="downloadQRCode(`{{$form->slug}}`)">
                                <i class="fa fa-download"></i>
                                Download QR Code
                            </a>
                        </div>

                        @include('app.forms.partials.setup-overview')
                    </div>
                </div>
            </div>

            <div class="col-xl-9 col-md-8 col-sm-12">
                @include('app.forms.partials.setup-general')
                @include('app.forms.partials.setup-access')
                @include('app.forms.partials.setup-submission')
                @include('app.forms.partials.setup-appearance')
            </div>
        </div>
    </div>
@endsection
@script('/vendor/flatpickr/flatpickr.min.js','src')
@script('/vendor/qrcode/qrcode.min.js','src')
@script('app.forms.scripts.setup')