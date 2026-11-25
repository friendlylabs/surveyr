<?php

use App\Http\Controllers\App\ComponentsController;
use App\Http\Controllers\App\DashboardController;
use App\Http\Controllers\App\ProfileController;
use App\Http\Controllers\App\WorkspacesController;
use Illuminate\Support\Facades\Route;

Route::prefix('dashboard')->group(fn () => DashboardController::routes());
Route::prefix('profile')->group(fn () => ProfileController::routes());
Route::prefix('components')->group(fn () => ComponentsController::routes());
Route::prefix('workspaces')->group(fn () => WorkspacesController::routes());
