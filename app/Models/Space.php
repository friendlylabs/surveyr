<?php

namespace App\Models;

use App\Enums\SpaceRole;
use App\Models\Concerns\BelongsToWorkspace;
use App\Models\Concerns\HasHash;
use Database\Factories\SpaceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Space
 *
 * A project inside a workspace: it groups forms and decides who can work on them, through each member's role
 * (App\Enums\SpaceRole). A form can sit in several spaces.
 */
#[Fillable(['name', 'description', 'color'])]
#[Hidden(['id', 'workspace_id', 'user_id'])]
class Space extends Model
{
    /** @use HasFactory<SpaceFactory> */
    use BelongsToWorkspace, HasFactory, HasHash;

    /** The colours a space can be tagged with (pc-* token names). */
    public const COLORS = ['brand', 'info', 'success', 'warning', 'danger', 'ink'];

    /** Who created the space. */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** True for the user who created the space; their role in it never changes and they can't be removed. */
    public function isCreator(User $user): bool
    {
        return $this->user_id !== null && $this->user_id === $user->id;
    }

    /**
     * Why $actor can't change or remove $member's role in the space, or null when they can: nobody touches the
     * creator's role, and nobody changes their own.
     */
    public function roleLockedFor(User $member, User $actor): ?string
    {
        return match (true) {
            $this->isCreator($member) => __('The space\'s creator keeps their role.'),
            $member->is($actor) => __('You can\'t change your own role.'),
            default => null,
        };
    }

    /** Members, each with their role in pivot->role (SpaceRole). */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'space_user')
            ->using(SpaceMember::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    /** The forms in this space. */
    public function forms(): BelongsToMany
    {
        return $this->belongsToMany(Form::class, 'form_space');
    }

    /** Adds the user with the role, or changes the role of a member already in. */
    public function addMember(User $user, SpaceRole $role = SpaceRole::Viewer): void
    {
        $this->members()->syncWithoutDetaching([$user->id => ['role' => $role->value]]);
    }

    /** The user's role in this space, or null when they aren't a member. */
    public function roleOf(User $user): ?SpaceRole
    {
        if ($this->relationLoaded('members')) {
            return $this->members->firstWhere('id', $user->id)?->pivot->role;
        }

        return $this->members()->whereKey($user->id)->first()?->pivot->role;
    }
}
