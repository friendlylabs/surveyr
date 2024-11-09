<?php

namespace App\Models;

class User extends Model
{
    protected $table = "users";
    protected $fillable = ["username", "fullname", "email", "email_verified", "password", "remember_token", "status", "role", "avatar", "two_fa", "notify_login"];
    protected $hidden = ["password", "remember_token"];
    protected $with = ["role"];
    public $timestamps = true;
    public $casts = [
        "created_at" => "datetime",
        "updated_at" => "datetime"
    ];

    # belongs to user role
    public function role()
    {
        return $this->belongsTo(UserRole::class, "role");
    }

    # has many user tokens
    public function tokens()
    {
        return $this->hasMany(UserToken::class, "user_id");
    }
}