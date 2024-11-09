<?php

namespace App\Models;
use Leaf\Database as DB;

class Collection extends Model
{
    protected $table = 'collections';
    protected $fillable = [
        "form_id",
        "submission",
    ];

    public $timestamps = true;
    protected $casts = [
        "submission" => "json",
        "created_at" => "datetime",
        "updated_at" => "datetime",
    ];

    # form collections
    public static function formCollections(int $form_id) : object
    {
        return static::where('form_id', $form_id)->get();
    }
    
    # recent submissions
    public static function recentSubmissions(int $userId, int $limit=10) : object
    {
        # collection where user is the owner or collaborator
        return Collection::whereHas('form', function ($query) use ($userId) {
            $query->where('user_id', $userId)
                  ->orWhereJsonContains('collaborators', $userId);
            })
        ->orderBy('created_at', 'desc')
        ->limit(10)
        ->get();
    }

    # form stats (count)
    public static function formStats($userId, int $limit=5) : object
    {
        // SELECT form_id, COUNT(id) FROM collections GROUP BY form_id
        return static::with('form')
            ->whereHas('form', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->select('form_id', DB::$capsule::raw('COUNT(id) as count'))
            ->groupBy('form_id')
            ->orderBy('count', 'desc')
            ->limit($limit)
            ->get();
    }


    # belongs to form
    public function form()
    {
        return $this->belongsTo(Form::class, 'form_id');
    }
}