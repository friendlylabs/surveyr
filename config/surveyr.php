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

];
