<?php

namespace Tests\Concerns;

use App\Providers\AppServiceProvider;
use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

/**
 * ConfiguresEmailVerification
 *
 * Turns email confirmation on for a test, as with multiple workspaces (it's off with one, the default), then registers the routes
 * again: the verify-email routes and middleware are added at boot from config/fortify.php.
 */
trait ConfiguresEmailVerification
{
    protected function withEmailVerification(): void
    {
        config(['fortify.features' => array_values(array_unique([...config('fortify.features'), Features::emailVerification()]))]);

        $router = $this->app['router'];
        $router->setRoutes(new RouteCollection);
        Route::middleware('web')->group(base_path('routes/web.php'));
        $this->app->getProvider(AppServiceProvider::class)->routesRegistrar();
        $router->getRoutes()->refreshNameLookups();
    }
}
