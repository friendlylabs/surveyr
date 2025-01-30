<?php

app()::group('api', ['namespace'=>'\App\Controllers\Api', function() {

    app()::group('auth', fn() => \App\Controllers\Api\AuthController::routes());
    
    app()::group('forms', fn() => \App\Controllers\Api\FormsController::routes());
    app()::group('collection', fn() => \App\Controllers\Api\CollectionController::routes());

}]);