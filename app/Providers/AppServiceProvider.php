<?php

namespace App\Providers;

use App\Models\User;
use App\Support\CurrentWorkspace;
use App\Support\Workspaces;
use App\View\Components\AppLayout;
use App\View\Components\AuthLayout;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/**
 * AppServiceProvider
 *
 * App-wide bindings, components, pagination view and gates.
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

        Paginator::defaultView('vendor.pagination.pc');
        Paginator::defaultSimpleView('vendor.pagination.pc');

        $this->defineGates();
    }

    /**
     * Abilities: add one per feature here and check it with @can in views, ->middleware('can:...') on routes, or
     * Gate::allows() in controllers. `admin` is the install's administrator (users.role_id); workspace abilities go by
     * the member's role in the current workspace (WorkspaceRole).
     */
    private function defineGates(): void
    {
        // self-hosted, the install admin can do anything; with multiple workspaces (SaaS) admin is a platform role
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
