<?php

namespace App\Models;

class ReviewType extends Model
{
    protected $table = 'review_types';
    protected $fillable = [
        "name",
        "properties"
    ];

    protected $casts = [
        "properties" => "json"
    ];
}