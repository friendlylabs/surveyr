<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Models\Workspace;
use App\Support\CurrentWorkspace;
use App\Support\Workspaces;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * ResolveCurrentWorkspace
 *
 * Sets the signed-in user's workspace for the request (App\Support\CurrentWorkspace) and shares it with views as
 * $currentWorkspace: their current one while they're still a member, else the first they belong to, else a new seat
 * (Workspaces::provision; accounts made before workspaces existed, or removed from their last one).
 */
class ResolveCurrentWorkspace
{
    /** Takes the request's CurrentWorkspace holder. */
    public function __construct(private CurrentWorkspace $currentWorkspace) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User) {
            $workspace = $this->resolve($user);

            $this->currentWorkspace->set($workspace);
            View::share('currentWorkspace', $workspace);
        }

        return $next($request);
    }

    /** The user's current workspace while they're still a member, else the first they belong to, else a new one. */
    private function resolve(User $user): Workspace
    {
        $current = $user->currentWorkspace;

        if ($current && $current->hasMember($user)) {
            return $current;
        }

        $first = $user->workspaces()->orderBy('workspaces.id')->first();

        if ($first) {
            $user->switchWorkspace($first);

            return $first;
        }

        return Workspaces::provision($user);
    }
}
