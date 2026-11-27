<?php

/**
 * Admin routes: the install's administration. Prefixed /admin and named admin.*, with the member middleware and the
 * admin gate (AppServiceProvider::routesRegistrar).
 */

use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UsersController;
use Illuminate\Support\Facades\Route;

Route::prefix('users')->group(fn () => UsersController::routes());
Route::prefix('settings')->group(fn () => SettingsController::routes());
