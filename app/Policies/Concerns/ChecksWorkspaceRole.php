<?php

namespace App\Policies\Concerns;

use App\Models\User;
use App\Support\CurrentWorkspace;

/**
 * ChecksWorkspaceRole
 *
 * For policies: whether the user runs the current workspace (its Owner or an Admin), who can manage everything in it.
 */
trait ChecksWorkspaceRole
{
    /** True when the user is the current workspace's Owner or an Admin. */
    protected function managesWorkspace(User $user): bool
    {
        $workspace = app(CurrentWorkspace::class)->get();

        return $workspace !== null && (bool) $user->workspaceRole($workspace)?->canManageWorkspace();
    }
}
