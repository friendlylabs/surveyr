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
                <p>Hi there {{ $name }},</p>
                <p>We wanted to let you know that a new device was used to sign in to your account, If you suspect any suspicious activity on your account, please contact us immediately and secure your account by changing your password.</p>
                <p>Best regards, </p>
				<p>The Team - {{ _env('APP_NAME') }}</p>
            </td>
        </tr>
    </table>

@endsection