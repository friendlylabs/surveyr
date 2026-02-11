<?php

namespace App\Models;

class CollectionPayload extends Model
{
    protected $table = 'collection_payloads';
    protected $primaryKey = 'collection_id';
    protected $fillable = [
        "collection_id",
        "submission"
    ];

    public $timestamps = false;
    protected $casts = [
        "submission" => "json",
    ];

    # belongs to collection
    public function collection()
    {
        return $this->belongsTo(Collection::class, 'collection_id');
    }
}
