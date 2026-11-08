<?php

namespace App\Models;

/**
 * A user-made set of flags a form can opt into. `flags` is a list of
 * `{key, label, color}`; submissions store the key, so labels and colours
 * can change without touching `collections.flag`.
 */
class FlagGroup extends Model
{
    protected $table = 'flag_groups';
    protected $fillable = [
        "name",
        "description",
        "flags",
        "user_id"
    ];

    public $timestamps = true;
    protected $casts = [
        "flags" => "json",
        "created_at" => "datetime",
        "updated_at" => "datetime",
    ];

    protected $attributes = [
        "flags" => "[]"
    ];

    # every group, newest first, with the forms using each counted
    public static function listing() : object
    {
        return static::with('user')->withCount('forms')->orderBy('created_at', 'desc')->get();
    }

    public function flagKeys() : array
    {
        return array_column($this->flags ?? [], 'key');
    }

    public function findFlag(?string $key) : ?array
    {
        foreach ($this->flags ?? [] as $flag) {
            if (($flag['key'] ?? null) === $key) return $flag;
        }
        return null;
    }

    # the creator or staff may edit / delete; everyone may attach
    public static function canManage(FlagGroup $group) : bool
    {
        $user = auth()->user();
        if (!$user) return false;

        return (int) $group->user_id === (int) $user->id
            || in_array($user->role, ['admin', 'moderator']);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function forms()
    {
        return $this->hasMany(Form::class, 'flag_group_id');
    }
}
