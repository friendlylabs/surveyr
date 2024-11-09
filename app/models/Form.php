<?php

namespace App\Models;
use Leaf\Database as DB;

// all json_array items are stored as strings: ["1", "2", "3"]

class Form extends Model
{    
    protected $table = 'forms';
    protected $fillable = [
        'title',
        'description',
        'slug',
        'content',
        'user_id',
        'collaborators',
        'spaces',
        'is_locked',
        'current_editor',
        'is_indefinite',
        'submission_url',
        'webhook_url',
        'access_code',
        'start_date',
        'end_date'
    ];

    public $timestamp = true;
    protected $with = ['user'];
    
    protected $casts = [
        'content' => 'json',
        'collaborators' => 'json',
        'spaces' => 'json',
        'is_locked' => 'boolean',
        'is_indefinite' => 'boolean',
        'start_date' => 'datetime',
        'end_date' => 'datetime'
    ];

    protected $attributes = [
        'is_locked' => 0,
        'current_editor' => 0,
        'is_indefinite' => 0,
        'collaborators' => '[]',
        'spaces' => '[]',
        'content' => '[]'
    ];

    # user forms
    public static function userForms($user_id) : object
    {
        return static::where('user_id', $user_id)
            ->orWhereJsonContains('collaborators', $user_id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    # active forms (start_date < now, end_date > now) or (is_indefinite = 1)
    public static function openForms($user_id) : object
    {
        return static::where('user_id', $user_id)
            ->where(function($query) {
                $query->where('start_date', '<', now())
                    ->where('end_date', '>', now())
                    ->orWhere('is_indefinite', 1);
            })->get();
    }

    # public form
    public static function publicForm($md5Id, $slug){
        return static::where(DB::$capsule::raw("MD5(id)"), $md5Id)
            ->where('slug', $slug)
            ->first();
    }

    # remove a space id form multiple forms
    public static function removeSpaceId($space_id) : void
    {
        // UPDATE forms SET spaces = JSON_REMOVE(spaces, JSON_UNQUOTE(JSON_SEARCH(spaces, 'one', 2))) WHERE JSON_SEARCH(spaces, 'one', 2) IS NOT NULL;
        DB::$capsule::table('forms')
            ->whereRaw("JSON_SEARCH(spaces, 'one', $space_id) IS NOT NULL")
            ->update([
                'spaces' => DB::$capsule::raw("JSON_REMOVE(spaces, JSON_UNQUOTE(JSON_SEARCH(spaces, 'one', $space_id)))")
            ]);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}