<?php

namespace App\Models;

class Template extends Model
{
    protected $table = 'templates';
    protected $fillable = [
        "name",
        "description",
        "content",
        "preview",
        "category"
    ];

    public $timestamps = true;
    protected $casts = [
        "created_at" => "datetime",
        "updated_at" => "datetime",
    ];

}