<?php

app()->redirect('app', 'app/home');
app()::group('app', ['namespace'=>'\App\Controllers\Base', function() {

    app()->get('home', ['name'=>'app.home', 'DashboardController@home']);

    app()->get('profile', ['name'=>'app.profile', 'UserController@profile']);

    app()->post('profile', ['name'=>'app.profile.update', 'UserController@updateProfile']);
    app()->post('password', ['name'=>'app.password.update', 'UserController@updatePassword']);
    app()->post('security', ['name'=>'app.security.update', 'UserController@updateSecurity']); 
    
    app()::group('users', fn() => \App\Controllers\Base\UsersController::routes());
    app()::group('zones', fn() => \App\Controllers\Base\ZonesController::routes());
    app()::group('spaces', fn() => \App\Controllers\Base\SpacesController::routes());

    app()::group('media', fn() => \App\Controllers\Base\MediaController::routes());

    // app()::group('forms', fn() => \App\Controllers\Base\FormsController::routes());
    // app()::group('forms', fn() => \App\Controllers\Base\CollectionController::routes());    // form submissions and collections
    // app()::group('forms', fn() => \App\Controllers\Base\ReportsController::routes());        // form reports

    app()::group('forms', function() {
        \App\Controllers\Base\FormsController::routes();
        \App\Controllers\Base\CollectionController::routes();
        \App\Controllers\Base\ReportsController::routes();
    });

    app()::group('intergrations', fn() => \App\Controllers\Base\IntergrationController::routes());
}]);