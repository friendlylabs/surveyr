<?php

namespace Tests\Concerns;

use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\Route;

// Sets config/auth.php `registration` for a test, from a fixed baseline (open sign-up, any domain) rather than
// whatever the app is configured with, then registers the routes again: they're added at boot from that config.
trait ConfiguresRegistration
{
    protected function withRegistration(array $settings = []): void
    {
        config(['auth.registration' => $settings + [
            'enabled' => true,
            'default_role' => 'user',
            'allow_invitation_only' => false,
            'allow_email_domains' => [],
        ]]);

        $router = $this->app['router'];
        $router->setRoutes(new RouteCollection);
        Route::middleware('web')->group(base_path('routes/web.php'));
        $router->getRoutes()->refreshNameLookups();
    }
}
