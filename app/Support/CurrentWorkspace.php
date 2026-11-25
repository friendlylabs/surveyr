<?php

namespace App\Support;

use App\Models\Workspace;
use Closure;

/**
 * CurrentWorkspace
 *
 * The workspace this request (or job) works in, bound as a singleton. ResolveCurrentWorkspace sets it for app pages;
 * queued jobs and commands that touch workspace data wrap their work in run(). BelongsToWorkspace models scope their
 * queries to it and fill workspace_id from it.
 *   app(CurrentWorkspace::class)->get()                     the Workspace, or null outside a workspace
 *   app(CurrentWorkspace::class)->run($workspace, fn () => Form::count())
 */
class CurrentWorkspace
{
    private ?Workspace $workspace = null;

    /** Sets the workspace; null clears it. */
    public function set(?Workspace $workspace): void
    {
        $this->workspace = $workspace;
    }

    /** The workspace, or null outside one. */
    public function get(): ?Workspace
    {
        return $this->workspace;
    }

    /** The workspace id, or null outside one. */
    public function id(): ?int
    {
        return $this->workspace?->id;
    }

    /** True while a workspace is set. */
    public function check(): bool
    {
        return $this->workspace !== null;
    }

    /**
     * Run the callback inside the workspace, then put back whichever was set before.
     *
     * @template TResult
     *
     * @param  Closure(): TResult  $callback
     * @return TResult
     */
    public function run(Workspace $workspace, Closure $callback): mixed
    {
        $previous = $this->workspace;
        $this->workspace = $workspace;

        try {
            return $callback();
        } finally {
            $this->workspace = $previous;
        }
    }
}
