<?php

app()->redirect('/', '/auth/login');

app()->redirect('login', '/auth/login');
app()->redirect('register', '/auth/register');

app()::group('auth', fn() => \App\Controllers\AuthController::routes());

app()->get('policy/terms', ['name' => 'policy.terms',function() {
    echo 'Terms of Service';
}]);

app()->get('policy/privacy', ['name' => 'policy.privacy',function() {
    echo 'Privacy Policy';
}]);

# ------------------------------------------------------------------------------
# Public App Routes
# ------------------------------------------------------------------------------


app()::group('p', ['namespace' => '\App\Controllers\Base', function() {
    app()->get('forms/{hash}/{slug}', ['name' => 'forms.show', 'FormsController@show']);
    app()->post('forms/{hash}/{slug}', ['name' => 'forms.collect', 'CollectionController@store']);
}]);