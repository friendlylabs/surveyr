<?php

namespace App\Models;

use App\Enums\WorkspaceRole;
use App\Models\Concerns\HasHash;
use App\Notifications\ResetPassword;
use App\Notifications\VerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\Features;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * User
 *
 * An account, with an install-wide role and one or more workspaces.
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token', 'role_id', 'current_workspace_id', 'two_factor_secret', 'two_factor_recovery_codes'])]
class User extends Authenticatable implements MustVerifyEmail, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasHash, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /** Column types and casting. */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'disabled_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    /** Disabled by an admin: can't sign in (users are disabled, never deleted). */
    public function isDisabled(): bool
    {
        return $this->disabled_at !== null;
    }

    /** True once two-factor authentication is set up and confirmed with a first code. */
    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_secret !== null && $this->two_factor_confirmed_at !== null;
    }

    /** Queued version of Laravel's reset email; all mail goes through the queue. */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPassword($token));
    }

    /**
     * Queued version of Laravel's verification email; nothing is sent while email verification is off
     * (config/fortify.php).
     */
    public function sendEmailVerificationNotification(): void
    {
        if (Features::enabled(Features::emailVerification())) {
            $this->notify(new VerifyEmail);
        }
    }

    /** Belongs to role. */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /** True when the user's role is any of the given names (admin, member). */
    public function hasRole(string ...$names): bool
    {
        return in_array($this->role?->name, $names, true);
    }

    /** Workspaces the user is a member of, with their role in pivot->role (WorkspaceRole). */
    public function workspaces(): BelongsToMany
    {
        return $this->belongsToMany(Workspace::class, 'workspace_user')
            ->using(WorkspaceMember::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    /** The workspace the user last worked in (ResolveCurrentWorkspace keeps it pointing at one they belong to). */
    public function currentWorkspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class, 'current_workspace_id');
    }

    /** The user's role in the workspace, or null when they aren't a member. */
    public function workspaceRole(Workspace $workspace): ?WorkspaceRole
    {
        return $this->workspaces()->whereKey($workspace->id)->first()?->pivot->role;
    }

    /**
     * Makes a workspace the user belongs to the current one; false (and nothing changes) when they aren't a member.
     */
    public function switchWorkspace(Workspace $workspace): bool
    {
        if (! $workspace->hasMember($this)) {
            return false;
        }

        $this->forceFill(['current_workspace_id' => $workspace->id])->save();
        $this->setRelation('currentWorkspace', $workspace);

        return true;
    }
}
