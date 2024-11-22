<?php

namespace App\Models;

class Theme extends Model
{
    protected $table = 'themes';
    protected $fillable = ['name', 'theme', 'cover'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
}