<?php

namespace App\Models;

class UserRole extends Model
{
    protected $table = 'user_roles';
    protected $fillable = ['name', 'description', 'is_admin'];
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function users()
    {
        return $this->hasMany(User::class, 'role');
    }
}