<?php

namespace App\Providers;

use App\Models\User;
use App\Support\CurrentWorkspace;
use App\Support\Workspaces;
use App\View\Components\AppLayout;
use App\View\Components\AuthLayout;
use App\View\Components\PublicLayout;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Features;

/**
 * AppServiceProvider
 *
 * App-wide bindings, components, pagination view, gates and the route files.
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // one per request (or job), so the workspace never leaks from one to the next
        $this->app->scoped(CurrentWorkspace::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Blade::component('layout-app', AppLayout::class);
        Blade::component('layout-auth', AuthLayout::class);
        Blade::component('layout-public', PublicLayout::class);

        // @asset('assets/js/pc-core.js'): the file's URL with its last-changed time, so updates reach browsers at once
        Blade::directive('asset', fn (string $path) => "<?php echo e(App\Support\Assets::url({$path})); ?>");

        Paginator::defaultView('vendor.pagination.pc');
        Paginator::defaultSimpleView('vendor.pagination.pc');

        $this->defineGates();
        $this->routesRegistrar();
    }

    /**
     * The route files besides routes/web.php (the public routes, loaded by bootstrap/app.php), each with its prefix and
     * middleware. Public so tests that change config the routes depend on can register them again.
     */
    public function routesRegistrar(): void
    {
        // signed in, (while email verification is on) with a confirmed address, and working in a workspace
        $member = array_values(array_filter([
            'web',
            'auth',
            Features::enabled(Features::emailVerification()) ? EnsureEmailIsVerified::redirectTo('auth.verify-email') : null,
            'workspace',
        ]));

        // sign in, sign up and the rest of the account flows
        Route::middleware('web')->prefix('auth')->group(base_path('routes/auth.php'));

        // the app: forms, spaces, profile, ...
        Route::middleware($member)->prefix('app')->group(base_path('routes/app.php'));

        // the install's administration
        Route::middleware([...$member, 'can:admin'])->prefix('admin')->name('admin.')->group(base_path('routes/admin.php'));
    }

    /**
     * Abilities: add one per feature here and check it with @can in views, ->middleware('can:...') on routes, or
     * Gate::allows() in controllers. `admin` is the install's administrator (users.role_id); workspace abilities go by
     * the member's role in the current workspace (WorkspaceRole).
     */
    private function defineGates(): void
    {
        // with one workspace, the install admin can do anything; with multiple workspaces admin is an install-wide role
        // and gets no pass into other workspaces' data
        Gate::before(fn (User $user) => ! Workspaces::multiple() && $user->hasRole('admin') ? true : null);

        Gate::define('admin', fn (User $user) => $user->hasRole('admin'));

        // the current workspace's members and settings: its Owner and Admins
        Gate::define('manage-workspace', function (User $user) {
            $workspace = app(CurrentWorkspace::class)->get();

            return $workspace !== null && (bool) $user->workspaceRole($workspace)?->canManageWorkspace();
        });
    }
}
