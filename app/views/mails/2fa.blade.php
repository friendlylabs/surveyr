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
				<p>You have requested to log in using Two-Factor Authentication (2FA). Use the code below to complete your login process:</p>
				<table role="presentation" border="0" cellpadding="0" cellspacing="0" class="btn btn-primary">
					<tbody>
						<tr style="text-align:center">
							<td style="font-size: 24px; font-weight: bold; padding: 10px;">
								{{ $token }}
							</td>
						</tr>
					</tbody>
				</table>
				<p>Please note, this code is valid for only a limited time.</p>
				<p>If you did not request this code, please disregard this email or contact our support team immediately.</p>
				<p>Best regards,<br>The Support Team</p>
                <p>{{ getenv('app_name') }}</p>
			</td>
		</tr>
	</table>				

@endsection
