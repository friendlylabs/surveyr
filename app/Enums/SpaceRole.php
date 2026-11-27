<?php

namespace App\Enums;

/**
 * SpaceRole
 *
 * A member's role in a space (space_user.role). Managers run the space, its members and every form in it; Editors create
 * and edit its forms and see responses; Viewers see its forms and responses and change nothing.
 */
enum SpaceRole: string
{
    case Manager = 'manager';
    case Editor = 'editor';
    case Viewer = 'viewer';

    /** Display name, i.e. Manager. */
    public function label(): string
    {
        return match ($this) {
            self::Manager => __('Manager'),
            self::Editor => __('Editor'),
            self::Viewer => __('Viewer'),
        };
    }

    /** One line on what the role allows, for role pickers. */
    public function description(): string
    {
        return match ($this) {
            self::Manager => __('Manages the space, its members and every form in it.'),
            self::Editor => __('Creates and edits forms, and sees responses.'),
            self::Viewer => __('Sees forms and responses; changes nothing.'),
        };
    }

    /** True for roles that may change the space's forms. */
    public function canEditForms(): bool
    {
        return $this === self::Manager || $this === self::Editor;
    }

    /** True for the role that manages the space itself. */
    public function canManageSpace(): bool
    {
        return $this === self::Manager;
    }

    /**
     * The roles as options for a select: value => label.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $role) => [$role->value => $role->label()])->all();
    }
}
