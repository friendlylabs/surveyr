<?php

namespace App\Support;

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;

/**
 * Workspaces
 *
 * The workspace rules, from config/surveyr.php `workspaces`. Self-hosted (multiple off): one workspace, which every
 * account joins (its first account as Owner). SaaS (multiple on): each new account gets a workspace of its own.
 */
class Workspaces
{
    /** True when accounts get workspaces of their own (SaaS); false for the self-hosted single workspace. */
    public static function multiple(): bool
    {
        return (bool) config('surveyr.workspaces.multiple', false);
    }

    /** The self-hosted install's one workspace: the oldest, created (unowned) on first use. */
    public static function default(): Workspace
    {
        return Workspace::oldest('id')->first()
            ?? Workspace::create(['name' => (string) config('surveyr.workspaces.default_name', config('app.name'))]);
    }

    /**
     * Give a new account its workspace and make it current: self-hosted, a seat in the one workspace (as Owner when
     * nobody owns it yet); SaaS, a workspace of its own, as Owner.
     */
    public static function provision(User $user): Workspace
    {
        return DB::transaction(function () use ($user) {
            if (self::multiple()) {
                $workspace = new Workspace(['name' => self::personalName($user)]);
                $workspace->owner_id = $user->id;
                $workspace->save();
                $workspace->addMember($user, WorkspaceRole::Owner);
            } else {
                $workspace = self::default();

                if ($workspace->owner_id === null) {
                    $workspace->forceFill(['owner_id' => $user->id])->save();
                }

                if (! $workspace->hasMember($user)) {
                    $workspace->addMember($user, $workspace->owner_id === $user->id ? WorkspaceRole::Owner : WorkspaceRole::Member);
                }
            }

            $user->forceFill(['current_workspace_id' => $workspace->id])->save();

            return $workspace;
        });
    }

    /** "Amina's workspace". */
    private static function personalName(User $user): string
    {
        $first = strtok(trim($user->name), ' ') ?: $user->name;

        return "{$first}'s workspace";
    }
}
