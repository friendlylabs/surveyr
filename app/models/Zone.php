<?php

namespace App\Models;

class Zone extends Model
{
    protected $table = 'zones';
    protected $fillable = ['name', 'description', 'sheet', 'content', 'user_id', 'editors'];
    protected $casts = [
		'sheet' => 'json',
        'content' => 'json',
        'editors' => 'json'
    ];

    protected $attributes = [
        'sheet' => 
            '[{"cols": {"len": 26}, "name": "sheet1", "rows": {"0": {"cells": {"0": {"text": ""}}}, "len": 1000}, "freeze": "A1", "merges": [], "styles": [], "autofilter": {}, "validations": []}]',
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