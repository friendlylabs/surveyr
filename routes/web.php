<?php

use App\Http\Controllers\Public\FilesController;
use App\Http\Controllers\Public\FormsController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/app/dashboard');

// forms as respondents reach them
Route::prefix('form')->group(fn () => FormsController::routes());

// uploaded files, through short-lived signed URLs only
Route::prefix('files')->group(fn () => FilesController::routes());
