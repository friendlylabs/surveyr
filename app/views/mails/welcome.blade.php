@extends('layouts.mail')
@section('content')

    <table role="presentation" border="0" cellpadding="0" cellspacing="0" class="main">
        <tr>
            <td style="text-align:center; padding-top:1rem;">
                <img width="200" src="{{ _env('APP_URL') }}/assets/images/brand/logo-dark.png" alt="Logo">
            </td>
        </tr>
        <tr>
            <td class="wrapper">
                <p>Hi {{ $name }},</p>
                <p>Your account has been successfully created by an administrator. Below are your login credentials:</p>
                <p><strong>Username:</strong> {{ $username }}</p>
                <p><strong>Password:</strong> {{ $password }}</p>
                <p>For security reasons, we recommend changing your password after your first login.</p>
                <p>You can log in to your account <a href="{{ _env('APP_URL') }}" target="_blank">here</a>.</p>
                <p>If you have any questions or need further assistance, feel free to contact us.</p>
                <p>Welcome aboard!</p>
                <p>Best regards,</p>
                <p>{{ _env('APP_NAME') }}</p>
            </td>
        </tr>
        
    </table>

@endsection
