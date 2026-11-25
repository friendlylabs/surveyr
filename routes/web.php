<?php

use App\Http\Controllers\AuthController;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

// signed in, (while email verification is on) with a confirmed address, and working in a workspace
$member = array_values(array_filter([
    'auth',
    Features::enabled(Features::emailVerification()) ? EnsureEmailIsVerified::redirectTo('auth.verify-email') : null,
    'workspace',
]));

Route::redirect('/', '/app/dashboard');

Route::prefix('auth')->group(fn () => AuthController::routes());
Route::prefix('app')->middleware($member)->group(fn () => require __DIR__.'/app.php');
Route::prefix('admin')->name('admin.')->middleware([...$member, 'can:admin'])->group(fn () => require __DIR__.'/admin.php');
