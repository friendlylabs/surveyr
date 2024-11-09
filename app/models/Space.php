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

    public static function userSpaces($user_id) : object
    {
        return static::where('user_id', $user_id)
            ->orWhereJsonContains('members', $user_id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public static function spaceForms($space_id) : object
    {
        return Form::whereJsonContains('spaces', $space_id)->get();
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