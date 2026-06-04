<?php

namespace App\Models;

class ReportLink extends Model
{
    protected $table = 'report_links';

    protected $fillable = [
        'report_id',
        'hash',
    ];

    public function report()
    {
        return $this->belongsTo(Report::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($reportLink) {
            $reportLink->hash = sprintf(
                '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );
        });
    }
}