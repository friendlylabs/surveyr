<?php

namespace App\Models;

class DeviceCode extends Model
{
    protected $primaryKey = 'code';
    protected $table = 'device_codes';
    protected $fillable = ['code', 'user_id'];

    public $timestamps = true;
    protected $cast = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public static function reset($userId){
        $deviceCode = DeviceCode::where('user_id', $userId)->first();
        if($deviceCode) $deviceCode->delete();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}