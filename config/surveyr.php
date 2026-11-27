<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Workspaces
    |--------------------------------------------------------------------------
    |
    | Every form, space, report and zone belongs to a workspace (App\Support\Workspaces).
    | By default there is one workspace that every account joins; turn `multiple`
    | on and each sign-up gets a workspace of its own.
    |
    */

    'workspaces' => [
        'multiple' => (bool) env('SURVEYR_MULTIPLE_WORKSPACES', false),
        'default_name' => env('SURVEYR_WORKSPACE_NAME', env('APP_NAME', 'Surveyr')),
    ],

    /*
    |--------------------------------------------------------------------------
    | SurveyJS
    |--------------------------------------------------------------------------
    |
    | The creator, form library and analytics are licensed by SurveyJS; the key is
    | handed to the browser (resources/js/surveyjs.js) and registered with it.
    |
    */

    'surveyjs' => [
        'license_key' => env('SURVEYJS_LICENSE_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Avatars
    |--------------------------------------------------------------------------
    |
    | Users get a generated picture (DiceBear's "lorelei" style by default).
    | :seed is replaced by the user's random public hash, never their name,
    | email or phone, so no personal data goes to the image service. Point it
    | at a self-hosted DiceBear, or set it empty to show initials only.
    |
    */

    'avatars' => [
        'url' => env('SURVEYR_AVATAR_URL', 'https://api.dicebear.com/7.x/lorelei/svg?seed=:seed'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Uploads
    |--------------------------------------------------------------------------
    |
    | Where files respondents upload are kept (App\Support\FileStorage): local
    | (storage/app/private/uploads), s3 (Amazon S3, or any S3-compatible store
    | through `endpoint`) or r2 (Cloudflare R2). These are the defaults; an
    | admin can switch storage and enter its details under Admin › Settings,
    | which wins over them. Files are never public: they're seen through
    | short-lived signed links. max_kb is the largest file one upload takes.
    |
    */

    'uploads' => [
        'disk' => env('SURVEYR_UPLOADS_DISK', 'local'),
        'max_kb' => (int) env('SURVEYR_UPLOADS_MAX_KB', 51200),
        's3' => [
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'bucket' => env('AWS_BUCKET'),
            'endpoint' => env('AWS_ENDPOINT'),
            'path_style' => (bool) env('AWS_USE_PATH_STYLE_ENDPOINT', false),
        ],
        'r2' => [
            'account' => env('R2_ACCOUNT_ID'),
            'key' => env('R2_ACCESS_KEY_ID'),
            'secret' => env('R2_SECRET_ACCESS_KEY'),
            'bucket' => env('R2_BUCKET'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhooks
    |--------------------------------------------------------------------------
    |
    | A form's webhook is told about each new response (App\Support\Webhooks).
    | Requests only go to public addresses, so a webhook can't reach into this
    | server's own network; turn `allow_private` on to send to localhost or a
    | LAN address while developing.
    |
    */

    'webhooks' => [
        'allow_private' => (bool) env('SURVEYR_WEBHOOKS_ALLOW_PRIVATE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Languages
    |--------------------------------------------------------------------------
    |
    | The interface languages users can pick (App\Support\Locales). A user's own
    | choice wins, then their workspace's default, then the browser's language
    | (sign-in pages), then app.locale. `dir` sets the page direction: add an
    | rtl language (e.g. 'ar' => [..., 'dir' => 'rtl']) and its translations in
    | lang/{code}.json, and the layout flips.
    |
    */

    'locales' => [
        'en' => ['name' => 'English', 'native' => 'English', 'dir' => 'ltr'],
    ],

];
