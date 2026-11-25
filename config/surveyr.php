<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Workspaces
    |--------------------------------------------------------------------------
    |
    | Every form, space, report and zone belongs to a workspace (App\Support\Workspaces).
    | Self-hosted runs with one workspace that every account joins; turn `multiple` on
    | (SaaS) and each sign-up gets a workspace of its own.
    |
    */

    'workspaces' => [
        'multiple' => (bool) env('SURVEYR_MULTIPLE_WORKSPACES', false),
        // the single workspace's name, while `multiple` is off
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
