<?php

/*
|--------------------------------------------------------------------------
| Mail Config
|--------------------------------------------------------------------------
|
| This file contains the configuration for your mailer.
|
| You can link your environment variables to this file by using the
| _env() helper function. This function will return the value set in
| your .env file. You can use the below settings as a reference.
|
*/

return [
    /*
    |--------------------------------------------------------------------------
    | Mail driver
    |--------------------------------------------------------------------------
    |
    | This tells leaf mail which mail driver to use.
    |
    */
    'driver' => _env('MAIL_DRIVER', 'smtp'),

    /*
    |--------------------------------------------------------------------------
    | Mailer hostname
    |--------------------------------------------------------------------------
    |
    | This is the hostname for your mailer
    |
    */
    'host' => _env('MAIL_HOST', 'smtp.mailtrap.io'),

    /*
    |--------------------------------------------------------------------------
    | Mailer port
    |--------------------------------------------------------------------------
    |
    | Port to use for mailer connection
    |
    */
    'port' => _env('MAIL_PORT', 2525),

    /*
    |--------------------------------------------------------------------------
    | Keep Alive
    |--------------------------------------------------------------------------
    |
    | This config is used to keep the connection to your mail server alive.
    | This is useful if you are sending multiple emails. It takes in a boolean.
    |
    */
    'keepAlive' => true,

    /*
    |--------------------------------------------------------------------------
    | Mailer Debug
    |--------------------------------------------------------------------------
    |
    | Enable or disable debug mode. Supported values are:
    | 'SERVER', false or any value supported by PHPMailer's
    | SMTPDebug config
    |
    */
    'debug' => _env('MAIL_DEBUG', 'SERVER'),

    /*
    |--------------------------------------------------------------------------
    | Mailer Encryption
    |--------------------------------------------------------------------------
    |
    | This is the encryption used for your mailer. Supported values are:
    | 'STARTTLS' or any value supported by PHPMailer's SMTPSecure config
    |
    */
    'security' => _env('MAIL_ENCRYPTION', 'STARTTLS'),

    /*
    |--------------------------------------------------------------------------
    | Auth
    |--------------------------------------------------------------------------
    |
    | This config handles the authentication details for your mailer.
    | It supports authentication with username and password and also
    | OAuth authentication.
    |
    | For OAuth authentication, you will need to add an OAuth
    | provider like league/oauth2-google to your project.
    |
    | An example OAuth config is shown below:
    |
    | use League\OAuth2\Client\Provider\Google;
    | use PHPMailer\PHPMailer\OAuth;
    |
    | 'auth' => new OAuth(
    |   [
    |     'userName' => 'mail@gmail.com',
    |     'clientSecret' => 'CLIENT_SECRET',
    |     'clientId' => 'CLIENT_ID',
    |     'refreshToken' => 'GMAIL_REFRESH_TOKEN',
    |     'provider' => new Google(
    |       [
    |         'clientId' => 'CLIENT_ID',
    |         'clientSecret' => 'CLIENT_SECRET',
    |       ]
    |    ),
    |  ]
    |)
    */
    'auth' => [
        'username' => _env('MAIL_USERNAME'),
        'password' => _env('MAIL_PASSWORD'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Default addresses
    |--------------------------------------------------------------------------
    |
    | This config is used to set default values for the
    | `recipientEmail`, `recipientName`,
    | `senderEmail`, `senderName`,
    | `replyToName`, and `replyToEmail` of your emails.
    |
    */
    'defaults' => [
        'senderName' => _env('MAIL_SENDER_NAME'),
        'senderEmail' => _env('MAIL_SENDER_EMAIL'),
        'replyToName' => _env('MAIL_REPLY_TO_NAME'),
        'replyToEmail' => _env('MAIL_REPLY_TO_EMAIL'),
    ],
];
