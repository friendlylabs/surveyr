<?php

/**
 * App routes: everything a signed-in workspace member uses. Prefixed /app, with the member middleware
 * (AppServiceProvider::routesRegistrar).
 */

use App\Http\Controllers\App\ComponentsController;
use App\Http\Controllers\App\DashboardController;
use App\Http\Controllers\App\ExportsController;
use App\Http\Controllers\App\FormBuilderController;
use App\Http\Controllers\App\FormOverviewController;
use App\Http\Controllers\App\FormResponsesController;
use App\Http\Controllers\App\FormsController;
use App\Http\Controllers\App\FormSettingsController;
use App\Http\Controllers\App\FormWebhooksController;
use App\Http\Controllers\App\ProfileController;
use App\Http\Controllers\App\ResponsesVisualizeController;
use App\Http\Controllers\App\SpacesController;
use App\Http\Controllers\App\SubmissionViewsController;
use App\Http\Controllers\App\VisualizationChartsController;
use App\Http\Controllers\App\VisualizationTabsController;
use App\Http\Controllers\App\WorkspacesController;
use Illuminate\Support\Facades\Route;

Route::prefix('dashboard')->group(fn () => DashboardController::routes());
Route::prefix('forms')->group(function () {
    FormsController::routes();
    FormBuilderController::routes();
    FormSettingsController::routes();
    FormWebhooksController::routes();
    // before the responses: /{form}/responses/{submission} would otherwise catch /visualize
    ResponsesVisualizeController::routes();
    FormResponsesController::routes();
    SubmissionViewsController::routes();
    VisualizationTabsController::routes();
    VisualizationChartsController::routes();
    // last: /{form} would otherwise catch the paths above
    FormOverviewController::routes();
});
Route::prefix('spaces')->group(fn () => SpacesController::routes());
Route::prefix('profile')->group(fn () => ProfileController::routes());
Route::prefix('components')->group(fn () => ComponentsController::routes());
Route::prefix('workspaces')->group(fn () => WorkspacesController::routes());
Route::prefix('exports')->group(fn () => ExportsController::routes());
