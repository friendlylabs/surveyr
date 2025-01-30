<?php

namespace App\Models;

class ApiKey extends Model
{
    protected $table = 'api_keys';
    protected $fillable = [
        'name', 'user_id', 'token', 'secret'
    ];

    public $timestamps = true;
    protected $casts = [
        "created_at" => "datetime",
        "updated_at" => "datetime",
    ];

    protected $hidden = [
        'token', 'secret'
    ];

    public static function by(int $user_id) : object
    {
        return static::where('user_id', $user_id)->get();
    }

    # has many activities
    public function activities() : object
    {
        return $this->hasMany(ApiActivity::class, 'apikey_id');
    }
}