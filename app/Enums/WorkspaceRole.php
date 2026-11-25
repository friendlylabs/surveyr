<?php

namespace App\Enums;

/**
 * WorkspaceRole
 *
 * A member's role in a workspace (workspace_user.role). Owner and Admin run the workspace (members, settings);
 * Members work with its forms, reports and zones as far as each item's own sharing allows.
 */
enum WorkspaceRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Member = 'member';

    /** Display name, i.e. Owner. */
    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::Admin => 'Admin',
            self::Member => 'Member',
        };
    }

    /** True for the roles that manage the workspace itself: its members and settings. */
    public function canManageWorkspace(): bool
    {
        return in_array($this, [self::Owner, self::Admin], true);
    }
}
