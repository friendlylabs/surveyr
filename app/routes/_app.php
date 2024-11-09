<?php

app()->redirect('app', 'app/home');
app()::group('app', ['namespace'=>'\App\Controllers\App', function() {

    app()->get('home', ['name'=>'app.home', 'DashboardController@home']);

    app()->get('profile', ['name'=>'app.profile', 'UserController@profile']);

    app()->post('profile', ['name'=>'app.profile.update', 'UserController@updateProfile']);
    app()->post('password', ['name'=>'app.password.update', 'UserController@updatePassword']);
    app()->post('security', ['name'=>'app.security.update', 'UserController@updateSecurity']); 
    
    app()::group('forms', fn() => \App\Controllers\App\FormsController::routes());
    app()::group('spaces', fn() => \App\Controllers\App\SpacesController::routes());
    app()::group('forms', fn() => \App\Controllers\App\CollectionController::routes());
}]);

app()->get('test', function(){
    
});