<?php

/*
|--------------------------------------------------------------------------
| Surveyr Configs
|--------------------------------------------------------------------------
|
| This file contains the configuration for your app. Most of this
| configuration is for Leaf's core but has been made available
| to you for your convenience.
|
| You can link your environment variables to this file by using the
| _env() helper function. This function will return the value set in
| your .env file. You can use the below settings as a reference.
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | AI Form Generation
    |--------------------------------------------------------------------------
    |
    | This option allows you to enable or disable the AI form generation
    | feature.
    |
    */
    'form.generator' => true,

    /*
    |--------------------------------------------------------------------------
    | Surveyr API Config
    |--------------------------------------------------------------------------
    |
    | This is the configuration for the Surveyr API. You can enable or disable
    | the API
    |
    */
    'api.enabled' => function() {
        return class_exists('App\Controllers\Api\BaseController');
    },

];