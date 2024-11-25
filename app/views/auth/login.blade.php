@extends('layouts.auth.main')
@section('content')
    <main class="main" id="top">
        <div class="container">
            <div class="row flex-center min-vh-100 py-5">
                <div class="col-sm-10 col-md-8 col-lg-5 col-xl-5 col-xxl-3">
                    <div class="card">
                        <div class="card-body">

                            <a class="d-flex flex-center text-decoration-none mb-3" href="/">
                                <div class="d-flex align-items-center fw-bolder fs-3 d-inline-block">
                                    <img src="/assets/images/brand/logo-dark.png" alt="app logo" height="40" />
                                </div>
                            </a>

                            @if(AuthConfig('google.auth'))
                                <a href="{{ route('google.auth') }}" class="btn btn-phoenix-secondary w-100 mb-3">
                                    <span class="fab fa-google text-danger me-2 fs-9"></span>Sign in with google
                                </a>

                                <div class="position-relative">
                                    <hr class="bg-body-secondary mt-5 mb-4" />
                                    <div class="divider-content-center">or use email</div>
                                </div>
                            @endif

                            <form action="{{ route('signin') }}" id="loginForm" class="p-2" onsubmit="submitForm(event)">
                                @csrf

                                <div class="mb-3 text-start">
                                    <label class="form-label" for="email">Email address</label>
                                    <div class="form-icon-container">
                                        <input class="form-control form-icon-input" name="email" id="email" type="email" placeholder="name@example.com" required>
                                        <span class="fas fa-user text-body fs-9 form-icon"></span>
                                    </div>
                                </div>

                                <div class="mb-3 text-start">
                                    <label class="form-label" for="password">Password</label>
                                    <div class="form-icon-container" data-password="data-password">
                                        <input class="form-control form-icon-input pe-6" name="password" id="password" type="password" placeholder="Password" data-password-input="data-password-input" required>
                                        <span class="fas fa-key text-body fs-9 form-icon"></span>
                                        <button type="button" class="btn px-3 py-0 h-100 position-absolute top-0 end-0 text-body-tertiary" aria-label="passwordToggle" data-password-toggle="data-password-toggle">
                                            <span class="fa fa-eye show"></span>
                                            <span class="fa fa-eye-slash hide"></span>
                                        </button>
                                    </div>
                                </div>

                                <div class="row flex-between-center mb-3">
                                    <div class="col-auto">
                                        <a class="fs-9 fw-semibold text-danger" href="{{ route('reset', 'request') }}">Forgot Password?</a>
                                    </div>

                                    @if(AuthConfig('allowRegistration'))
                                        <div class="col-auto">
                                            <div class="text-center">
                                                <a class="fs-9 fw-bold" href="{{ route('register') }}">Create an account</a>
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                <button class="btn btn-primary w-100 mb-3" id="btnLogin" type="submit">Sign In</button>
                            </form>
                        
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection
@script('auth.scripts.auth')