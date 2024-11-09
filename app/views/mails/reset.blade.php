@extends('layouts.mail')
@section('content')

    <!-- Test Mail -->
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" class="main">
        <tr>
            <td style="text-align:center; padding-top:1rem;">
                <img width="200" src="/assets/images/logo-dark.png" alt="Logo">
            </td>
        </tr>
        <tr>
            <td class="wrapper">
                <p>Hi {{ $name }},</p>
                <p>We received a request to reset your password. If you didn't make this request, you can ignore this email.</p>
                <p>To reset your password, please click the button below:</p>
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" class="btn btn-primary">
                    <tbody>
                        <tr style="text-align:center">
                            <td><a href="{{getenv('app_url')}}/auth/password/{{ $token }}" target="_blank">Reset Password</a></td>
                        </tr>
                    </tbody>
                </table>
                <p>If you're having trouble clicking the "Reset Password" button, copy and paste the following URL into your web browser:</p>
                <p><a href="{{getenv('app_url')}}/auth/password/{{ $token }}" target="_blank">{{getenv('app_url')}}/auth/password/{{ $token }}</a></p>
                <p>This password reset link will expire in 2 hours.</p>
                <p>If you didn't request a password reset, please disregard this message.</p>
                <p>Best regards,</p>
                <p>{{ getenv('app_name') }}</p>
            </td>
        </tr>
    </table>

@endsection
