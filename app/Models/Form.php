<?php

namespace App\Models;

use App\Enums\SpaceRole;
use App\Models\Concerns\BelongsToWorkspace;
use App\Models\Concerns\HasHash;
use Database\Factories\FormFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Form
 *
 * A form in a workspace: its SurveyJS definition (content), schedule, access and submission settings. It can sit in
 * several spaces; who may see or change it follows from its owner, its collaborators and the user's roles across its
 * spaces (FormPolicy).
 */
#[Fillable(['title', 'description', 'content', 'theme', 'opens_at', 'closes_at', 'webhook_url', 'settings'])]
#[Hidden(['id', 'workspace_id', 'user_id', 'legacy_key', 'access_code', 'webhook_secret'])]
class Form extends Model
{
    /** @use HasFactory<FormFactory> */
    use BelongsToWorkspace, HasFactory, HasHash;

    /** Column types and casting. */
    protected function casts(): array
    {
        return [
            'content' => 'array',
            'settings' => 'array',
            'theme' => 'array',
            'opens_at' => 'datetime',
            'closes_at' => 'datetime',
            'archived_at' => 'datetime',
            'revision' => 'integer',
            'access_code' => 'encrypted',
            'webhook_secret' => 'encrypted',
        ];
    }

    /** Keeps the slug in step with the title; deleting the form deletes the files uploaded to it. */
    protected static function booted(): void
    {
        static::saving(function (Form $form) {
            if ($form->isDirty('title') || ! $form->slug) {
                $form->slug = Str::slug($form->title) ?: 'form';
            }
        });
        static::deleting(function (Form $form) {
            Upload::withoutGlobalScopes()->where('form_id', $form->id)->lazyById()->each->delete();
        });
    }

    /** The form's owner. */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** The spaces the form sits in. */
    public function spaces(): BelongsToMany
    {
        return $this->belongsToMany(Space::class, 'form_space');
    }

    /** People the form is shared with directly, outside any space. */
    public function collaborators(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'form_user')->withPivot('created_at');
    }

    /**
     * archived, or open, scheduled or closed from the schedule; a form without one is open.
     */
    public function status(): string
    {
        return match (true) {
            $this->isArchived() => 'archived',
            $this->opens_at?->isFuture() => 'scheduled',
            $this->closes_at?->isPast() => 'closed',
            default => 'open',
        };
    }

    /** Archived: out of the list and no longer collecting. */
    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    /** The form's responses, archived ones too (Submission::active() for the list's). */
    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    /** Each call of its webhook (App\Support\Webhooks). */
    public function webhookDeliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class);
    }

    /** The form's saved views of its responses, by name. */
    public function views(): HasMany
    {
        return $this->hasMany(SubmissionView::class)->orderBy('name');
    }

    /** Whether the form has collected data (archived responses count); a form with data can only be archived, never deleted. */
    public function hasResponses(): bool
    {
        return $this->submissions()->exists();
    }

    /**
     * The status filter's choices, as value => label (forms lists).
     *
     * @return array<string, string>
     */
    public static function statusOptions(): array
    {
        return ['open' => __('Open'), 'scheduled' => __('Scheduled'), 'closed' => __('Closed'), 'archived' => __('Archived')];
    }

    /**
     * A forms list's search (title or description) and status filter. Archived forms leave the list unless the
     * Archived status is asked for.
     */
    public function scopeFiltered(Builder $query, ?string $search, ?string $status): Builder
    {
        return $query
            ->when($search, fn (Builder $query, $search) => $query->where(fn (Builder $query) => $query
                ->where('forms.title', 'like', "%{$search}%")
                ->orWhere('forms.description', 'like', "%{$search}%")))
            ->archived($status === 'archived')
            ->when($status === 'open', fn (Builder $query) => $query
                ->where(fn (Builder $query) => $query->whereNull('forms.opens_at')->orWhere('forms.opens_at', '<=', now()))
                ->where(fn (Builder $query) => $query->whereNull('forms.closes_at')->orWhere('forms.closes_at', '>', now())))
            ->when($status === 'scheduled', fn (Builder $query) => $query->where('forms.opens_at', '>', now()))
            ->when($status === 'closed', fn (Builder $query) => $query->where('forms.closes_at', '<=', now()));
    }

    /** Archived forms only, or (false) only the ones still in use. */
    public function scopeArchived(Builder $query, bool $archived = true): Builder
    {
        return $archived ? $query->whereNotNull('forms.archived_at') : $query->whereNull('forms.archived_at');
    }

    /** The user's strongest role across the form's spaces, or null when they're in none of them. */
    public function spaceRoleOf(User $user): ?SpaceRole
    {
        $roles = $this->spaces()
            ->join('space_user', 'space_user.space_id', '=', 'spaces.id')
            ->where('space_user.user_id', $user->id)
            ->pluck('space_user.role')
            ->map(fn ($role) => SpaceRole::from($role));

        foreach ([SpaceRole::Manager, SpaceRole::Editor, SpaceRole::Viewer] as $role) {
            if ($roles->contains($role)) {
                return $role;
            }
        }

        return null;
    }

    /**
     * Forms the user can see: their own, those shared with them, and those in any of their spaces. Workspace
     * Owners/Admins see every form (the caller skips this scope for them).
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->where(fn (Builder $query) => $query
            ->where('forms.user_id', $user->id)
            ->orWhereHas('collaborators', fn (Builder $query) => $query->whereKey($user->id))
            ->orWhereHas('spaces.members', fn (Builder $query) => $query->whereKey($user->id)));
    }
}
