<?php

namespace App\Models;

class Report extends Model
{
    protected $table = 'reports';
    protected $with = [
        'user', 'form'
    ];
    
    protected $fillable = [
        "title",
        "description",
        "content",
        "filters",
        'draft',
        "form_id",
        "user_id",
        "collaborators",
        "is_public"
    ];

    public $timestamps = true;
    protected $casts = [
        "content" => "json",
        'draft' => "json",
        "filters" => "json",
        "collaborators" => "json",
        "created_at" => "datetime",
        "updated_at" => "datetime",
    ];

    protected $attributes = [
        "content" => "{}",
        "draft" => "{}",
        "filters" => "{}",
        "collaborators" => "[]",
    ];

    public static function forForm(int $formId): object
    {
        return static::where('form_id', $formId)->get();
    }

    # belongs to user
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    # belongs to form
    public function form()
    {
        return $this->belongsTo(Form::class, 'form_id');
    }
}
