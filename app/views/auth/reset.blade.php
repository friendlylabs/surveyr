@extends('layouts.auth.main')
@section('content')
    <main class="main" id="top">
        <div class="container">
            <div class="row flex-center min-vh-100 py-5">
                <div class="col-sm-10 col-md-8 col-lg-5 col-xl-5 col-xxl-3">
                    <div class="card">
                        <div class="card-body">
                            
                            <a class="d-flex flex-center text-decoration-none mb-4" href="/">
                                <div class="d-flex align-items-center fw-bolder fs-3 d-inline-block">
                                    <img src="/assets/images/brand/logo-dark.png" alt="app logo" height="40" />
                                </div>
                            </a>

                            <p class="fs-9 text-center">
                                <strong>Reset Password</strong><br>
                                Enter your new password
                            </p>

                            <form action="@route('update.password')" id="resetForm" class="p-2">
                                @csrf
                                <input type="hidden" name="token" value="{{ $token }}">                                
                                <div class="mb-3 text-start">
                                    <div class="form-icon-container" data-password="data-password">
                                        <input class="form-control form-icon-input pe-6" name="password" id="password" type="password" placeholder="Password" data-password-input="data-password-input" required>
                                        <span class="fas fa-key text-body fs-9 form-icon"></span>
                                        <button type="button" class="btn px-3 py-0 h-100 position-absolute top-0 end-0 text-body-tertiary" data-password-toggle="data-password-toggle">
                                            <span class="fa fa-eye show"></span>
                                            <span class="fa fa-eye-slash hide"></span>
                                        </button>
                                    </div>
                                </div>

                                <div class="mb-3 text-center">
                                    <button class="btn btn-primary w-100" id="btnReset" type="submit"> Reset Password </button>
                                </div>
                            </form>
                            
                            <div class="row mt-2">
                                <div class="col-sm-12 text-center">
                                    <p class="text-muted mb-0 fs-9">
                                        Remember your password?
                                        <a class="fw-bold" href="@route('login')" class="text-dark ml-1">Sign In</a>
                                    </p>
                                </div>
                            </div>
                        
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection
@push('scripts')
    <script>
        $('#resetForm').submit(function(e) {
            e.preventDefault();
            var form = $(this);
            buttonState('#btnReset', 'loading');

            $.ajax({
                url: form.attr('action'),
                method: 'POST',
                data: form.serialize(),
                success: function(response) {
                    if (response.status) {
                        toast.success({ message: response.message });
                        setTimeout(() => {
                            window.location.href = '{{ route('login') }}';
                        }, 1000);
                    }else{
                        toast.error({ message: response.message });
                    }
                },
                error: function(xhr) {
                    toast.error({ message: 'Unknown Error Occurred' });
                },
                complete: function() {
                    buttonState('#btnReset', 'reset', 'Reset Password');
                }   
            });
            
        });
    </script>
@endpush