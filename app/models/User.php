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

    # has many user spaces
    public function spaces()
    {
        return $this->hasMany(Space::class, "user_id");
    }

    # has many forms
    public function forms()
    {
        return $this->hasMany(Form::class, "user_id");
    }

    # has many reports
    public function reports()
    {
        return $this->hasMany(Report::class, "user_id");
    }
}