@extends('layouts.mail')
@section('content')

    <table role="presentation" border="0" cellpadding="0" cellspacing="0" class="main">
        <tr>
            <td style="text-align:center; padding-top:1rem;">
                <img width="200" src="/assets/images/logo-dark.png" alt="Logo">
            </td>
        </tr>
        <tr>
            <td class="wrapper">
                <p>Hello {{ $name }},</p>
                <p>Thank you for registering with us. Please confirm your email address by clicking the button below:</p>
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" class="btn btn-primary">
                    <tbody>
                        <tr style="text-align:center">
                            <td>
                                <a href="{{ '/auth/verify-email?token=' . $token }}" target="_blank" style="background-color:#3498db; color:white; padding:10px 20px; text-decoration:none;">Verify Email</a>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div style="margin-top: 20px;">
                    <p>Alternatively, you can copy and paste the following link in your browser: <br>
                        <a href="{{ getenv('app_url') . '/auth/verify-email?token=' . $token }}" target="_blank">{{ getenv('app_url') . '/auth/verify-email?token=' . $token }}</a>
                    </p>
                </div>

                <p>If you did not create an account, no further action is required.</p>
                <p>Best regards, </p>
				<p>The Team - {{ getenv('app_name') }}</p>
            </td>
        </tr>
    </table>

@endsection