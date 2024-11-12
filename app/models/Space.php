<?php

namespace App\Models;

class Space extends Model
{
    protected $table = 'spaces';
    protected $fillable = [
        "name",
        "description",
        "color",
        "user_id",
        "members"
    ];

    public $timestamps = true;
    protected $casts = [
        "members" => "json",
        "created_at" => "datetime",
        "updated_at" => "datetime",
    ];

    protected $attributes = [
        "members" => "[]"
    ];

    public static function userSpaces(int $userId) : object
    {
        return static::where('user_id', $userId)
            ->orWhereJsonContains('members', (string) $userId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public static function absoluteUserSpaces(int $userId) : array
    {
        $spaces = Space::where('user_id', $userId)
            ->orWhereJsonContains('members', (string) $userId)->get()->pluck('id')->toArray();
        if(!$spaces || !count($spaces)) $spaces = [0];

        return $spaces;
    }

    public static function spaceForms($space_id) : object
    {
        return Form::whereJsonContains('spaces', (string) $space_id)->get();
    }

    public static function spaceMembers($space_id) : object
    {
        return User::whereIn('id', static::find($space_id)->members)->get();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function forms()
    {
        return Form::whereJsonContains('spaces', $this->id)->get();
    }
}