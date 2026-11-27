<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;

/**
 * Setting
 *
 * One of the install's settings, changed by an admin in the app, over its .env default: a dot key and its value,
 * encrypted (some are keys and secrets). Read and written through App\Support\Settings.
 */
#[Fillable(['key', 'value'])]
#[Hidden(['id', 'value'])]
class Setting extends Model
{
    /** Column types and casting. */
    protected function casts(): array
    {
        return [
            'value' => 'encrypted:array',
        ];
    }
}
