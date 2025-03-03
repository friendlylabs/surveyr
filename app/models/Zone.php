<?php

namespace App\Models;

class Zone extends Model
{
    protected $table = 'zones';
    protected $fillable = ['name', 'description', 'sheet', 'content', 'user_id', 'editors'];
    protected $casts = [
        'content' => 'json',
        'editors' => 'json'
    ];

    protected $attributes = [
        'data' => '[]',
        'content' => '[]',
        'editors' => '[]'
    ];

    public $timestamps = true;

    # belongs to user
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}