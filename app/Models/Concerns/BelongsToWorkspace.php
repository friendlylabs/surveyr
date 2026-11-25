<?php

namespace App\Models\Concerns;

use App\Models\Workspace;
use App\Support\CurrentWorkspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BelongsToWorkspace
 *
 * For workspace data (forms, spaces, reports, zones, ...): the table has a workspace_id, queries only see rows of the
 * current workspace (App\Support\CurrentWorkspace) and new rows are filled with it.
 * Outside a workspace (console, an unwrapped job) there is no filter, so jobs and commands that act for one workspace
 * wrap their work in CurrentWorkspace::run(); Model::withoutGlobalScope('workspace') reads across workspaces.
 * The creating listener doesn't fire where model events are muted (seeders with WithoutModelEvents): set workspace_id
 * there.
 */
trait BelongsToWorkspace
{
    /** Scopes queries to the current workspace and fills workspace_id on create. */
    public static function bootBelongsToWorkspace(): void
    {
        static::addGlobalScope('workspace', function (Builder $query) {
            $workspaceId = app(CurrentWorkspace::class)->id();

            if ($workspaceId !== null) {
                $query->where($query->qualifyColumn('workspace_id'), $workspaceId);
            }
        });

        static::creating(function ($model) {
            $model->workspace_id ??= app(CurrentWorkspace::class)->id();
        });
    }

    /** Belongs to workspace. */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
