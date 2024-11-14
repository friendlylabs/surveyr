@extends('layouts.error')
@section('content')
    <main class="main" id="top">
        <div class="px-3">
            <div class="row min-vh-100 flex-center p-5">
                <div class="col-12 col-xl-10 col-xxl-8">
                    <div class="row justify-content-center align-items-center g-5">
                        <div class="col-12 col-lg-6 text-center order-lg-1">                            
                            <img class="img-fluid w-lg-100 d-dark-none" src="/assets/images/vector/teapot.svg" alt="" width="400" />
                            <img class="img-fluid w-md-50 w-lg-100 d-light-none" src="/assets/images/vector/teapot.svg" alt="" width="540" />
                        </div>
                        <div class="col-12 col-lg-6 text-center text-lg-start">
                            <div class="error-container mb-1 w-50 w-lg-75 d-none d-lg-block">
                                <span class="shadow">418</span>
                                <span class="main">418</span>
                            </div>
                            <h2 class="text-body-secondary fw-bolder mb-3">Form not yet Open</h2>
                            <p class="text-body mb-5">                                
                                I refuse the attempt to brew coffee with a teapot,
                                <br class="d-none d-sm-block" /> Uuhm I mean the server refuses to serve the form, as it is not yet open for submissions.
                            </p>
                            @if(auth()->id())
                                <a class="btn btn-lg btn-primary" href="@route('forms.preview', $formId)">Preview Form</a>
                            @else
                                <a class="btn btn-lg btn-primary" href="@route('app.home')">Go Home</a>
                            @endif  
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection