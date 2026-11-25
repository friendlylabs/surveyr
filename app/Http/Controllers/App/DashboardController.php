<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Route;

/**
 * DashboardController
 *
 * The first page after sign-in (app/dashboard/index): empty, ready for the app's own first screen.
 */
class DashboardController extends Controller
{
    /** The dashboard page. */
    public function index()
    {
        return view('app.dashboard.index', $this->data);
    }

    /** Registers the dashboard route. */
    public static function routes(): void
    {
        Route::get('/', [self::class, 'index'])->name('dashboard');
    }
}
