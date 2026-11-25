<?php

namespace App\Models\Concerns;

/**
 * HasHash
 *
 * For models that show up in urls: a random `hash` column is filled on insert and used as the route key, so urls read
 * /users/3f9a0c... instead of exposing sequential ids. Uses Eloquent's unique-id insert hook rather than a `creating`
 * listener, so it still works where model events are muted (seeders with WithoutModelEvents).
 */
trait HasHash
{
    /** Turns on unique ids. */
    public function initializeHasHash(): void
    {
        $this->usesUniqueIds = true;
    }

    /** Columns filled with a unique id on insert. */
    public function uniqueIds(): array
    {
        return ['hash'];
    }

    /** 20 hex characters (80 random bits); the unique index on the column guards the rare collision. */
    public function newUniqueId(): string
    {
        return bin2hex(random_bytes(10));
    }

    /** Route keys are the hash. */
    public function getRouteKeyName(): string
    {
        return 'hash';
    }
}
