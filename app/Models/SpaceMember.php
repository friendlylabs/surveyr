<?php

namespace App\Models;

use App\Enums\SpaceRole;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * SpaceMember
 *
 * space_user: a membership, with the member's role in that space.
 */
class SpaceMember extends Pivot
{
    protected $table = 'space_user';

    /** Column types and casting. */
    protected function casts(): array
    {
        return [
            'role' => SpaceRole::class,
        ];
    }
}
