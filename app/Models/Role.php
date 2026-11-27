<?php

namespace App\Models;

use App\Models\Concerns\HasHash;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Role
 *
 * The install-wide role on users.role_id; roles inside a workspace are App\Enums\WorkspaceRole. Pages refer to a role by
 * its hash, never its id.
 */
#[Fillable(['name', 'description'])]
#[Hidden(['id'])]
class Role extends Model
{
    use HasHash;

    /** Display names for the role keys stored in `name`. */
    const LABELS = [
        'admin' => 'Admin',
        'member' => 'Member',
        'user' => 'User',
    ];

    /** Human-readable role name, i.e. member -> Member. */
    public function label(): Attribute
    {
        return Attribute::get(fn () => self::LABELS[$this->name] ?? Str::headline($this->name));
    }

    /** Has many users. */
    public function users()
    {
        return $this->hasMany(User::class);
    }
}
