<?php

namespace App\Models;

use App\Enums\WorkspaceRole;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * WorkspaceMember
 *
 * workspace_user: a membership, with the member's role in that workspace.
 */
class WorkspaceMember extends Pivot
{
    protected $table = 'workspace_user';

    /** Column types and casting. */
    protected function casts(): array
    {
        return [
            'role' => WorkspaceRole::class,
        ];
    }
}
