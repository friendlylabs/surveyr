<?php

namespace App\Policies;

use App\Models\Space;
use App\Models\User;
use App\Policies\Concerns\ChecksWorkspaceRole;

/**
 * SpacePolicy
 *
 * Who can see and run a space: its members see it, its Managers run it; the workspace's Owner and Admins can do
 * everything. Anyone in the workspace can start a space (and becomes its Manager).
 */
class SpacePolicy
{
    use ChecksWorkspaceRole;

    /** The spaces list: everyone in the workspace (it shows the spaces they're in). */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /** A space's page: its members and the workspace's managers. */
    public function view(User $user, Space $space): bool
    {
        return $this->managesWorkspace($user) || $space->roleOf($user) !== null;
    }

    /** Starting a space: anyone in the workspace. */
    public function create(User $user): bool
    {
        return true;
    }

    /** Renaming the space and managing its members: its Managers and the workspace's managers. */
    public function update(User $user, Space $space): bool
    {
        return $this->managesWorkspace($user) || (bool) $space->roleOf($user)?->canManageSpace();
    }

    /** Deleting the space (its forms stay, outside it): as for update. */
    public function delete(User $user, Space $space): bool
    {
        return $this->update($user, $space);
    }

    /** Adding forms to the space: its Managers and Editors, and the workspace's managers. */
    public function addForms(User $user, Space $space): bool
    {
        return $this->managesWorkspace($user) || (bool) $space->roleOf($user)?->canEditForms();
    }
}
