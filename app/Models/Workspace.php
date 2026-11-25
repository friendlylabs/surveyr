<?php

namespace App\Models;

use App\Enums\WorkspaceRole;
use App\Models\Concerns\HasHash;
use Database\Factories\WorkspaceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Workspace
 *
 * The tenant. Self-hosted installs have one (App\Support\Workspaces::default()); with multiple workspaces on (SaaS)
 * each sign-up gets its own. Workspace data models use App\Models\Concerns\BelongsToWorkspace.
 */
#[Fillable(['name', 'locale'])]
#[Hidden(['id', 'owner_id'])]
class Workspace extends Model
{
    /** @use HasFactory<WorkspaceFactory> */
    use HasFactory, HasHash;

    /** The account that created the workspace; it can't be removed from it or lose the owner role. */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /** Members, each with their role in pivot->role (WorkspaceRole). */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'workspace_user')
            ->using(WorkspaceMember::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    /** Adds the user with the role, or changes the role of a member already in. */
    public function addMember(User $user, WorkspaceRole $role = WorkspaceRole::Member): void
    {
        $this->members()->syncWithoutDetaching([$user->id => ['role' => $role->value]]);
    }

    /** True when the user is a member. */
    public function hasMember(User $user): bool
    {
        return $this->members()->whereKey($user->id)->exists();
    }
}
