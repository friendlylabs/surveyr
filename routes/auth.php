<?php

/**
 * Auth routes: sign in, sign up, passwords, email links, two-factor and passkeys. Prefixed /auth and in the web group
 * (AppServiceProvider::routesRegistrar).
 */

use App\Http\Controllers\Auth\AuthController;

AuthController::routes();
