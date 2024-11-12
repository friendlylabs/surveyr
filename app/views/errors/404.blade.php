@extends('layouts.auth.main')
@section('content')
    <main class="main" id="top">
        <div class="px-3">
            <div class="row min-vh-100 flex-center p-5">
                <div class="col-12 col-xl-10 col-xxl-8">
                    <div class="row justify-content-center align-items-center g-5">
                        <div class="col-12 col-lg-6 text-center order-lg-1">
                            <img class="img-fluid w-lg-100 d-dark-none" src="/assets/images/vector/404-illustration.png" alt="" width="400" />
                            <img class="img-fluid w-md-50 w-lg-100 d-light-none" src="/assets/images/vector/dark_404-illustration.png" alt="" width="540" /></div>
                        <div class="col-12 col-lg-6 text-center text-lg-start">
                            <img class="img-fluid mb-6 w-50 w-lg-75 d-dark-none" src="/assets/images/vector/404.png" alt="" />
                            <img class="img-fluid mb-6 w-50 w-lg-75 d-light-none" src="/assets/images/vector/dark_404.png" alt="" />
                            <h2 class="text-body-secondary fw-bolder mb-3">Page Missing!</h2>
                            <p class="text-body mb-5">But no worries! Our ostrich is looking everywhere <br class="d-none d-sm-block" />while you wait safely. </p>
                            <a class="btn btn-lg btn-primary" href="@route('app.home')">Go Home</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection