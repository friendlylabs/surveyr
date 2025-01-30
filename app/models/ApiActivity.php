<?php

namespace App\Models;

class ApiActivity extends Model
{
    protected $table = 'api_activities';
    protected $fillable = [
        'handler', 'origin', 'apikey_id', 'status'
    ];

    public $timestamps = true;
    protected $casts = [
        "created_at" => "datetime",
        "updated_at" => "datetime",
    ];

    # belongs to api key
    public function apikey() : object
    {
        return $this->belongsTo(ApiKey::class, 'apikey_id');
    }
}