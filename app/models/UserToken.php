<?php

namespace App\Models;

class UserToken extends Model
{
    protected $table = "user_tokens";
    protected $fillable = ["user_id", "token", "type"];

    public $timestamps = true;
    protected $hidden = ["token"];
    protected $with = ["user"];

    public $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    # belongs to user
    public function user()
    {
        return $this->belongsTo(User::class, "user_id");
    }
}