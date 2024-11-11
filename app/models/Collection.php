<?php

namespace App\Models;
use Leaf\Database as DB;

class Collection extends Model
{
    protected $table = 'collections';
    protected $fillable = [
        "form_id",
        "submission",
        'review'
    ];

    public $timestamps = true;
    protected $casts = [
        "submission" => "json",
        "created_at" => "datetime",
        "updated_at" => "datetime",
    ];

    # filtered review
    # where users is author or collaborator or belongs to space with which a form belongs
    public static function ofReview(string $review, int $userId) : object
    {
        $spaces = Space::where('user_id', $userId)
            ->orWhereJsonContains('members', (string) $userId)->get()->pluck('id')->toArray();
        if(!$spaces || !count($spaces)) $spaces = [0];

        return static::where('review', $review)
            ->where(function ($query) use ($userId, $spaces) {
                $query->whereHas('form', function ($query) use ($userId, $spaces) {
                    $query->where('user_id', $userId)
                        ->orWhereJsonContains('collaborators', (string) $userId)
                        ->orWhereJsonContains('spaces', array_map('strval', $spaces));
                });
            })
            ->orderBy('created_at', 'desc')
            ->get();
    }

    # form collections
    public static function formCollections(int $form_id) : object
    {
        return static::where('form_id', $form_id)->get();
    }
    
    # recent submissions
    public static function recentSubmissions(int $userId, int $limit=10) : object
    {
        $spaces = Space::where('user_id', $userId)
            ->orWhereJsonContains('members', (string) $userId)->get()->pluck('id')->toArray();
        if(!$spaces || !count($spaces)) $spaces = [0];

        if(!$spaces) $spaces = [];
        return static::where(function ($query) use ($userId, $spaces) {
            $query->whereHas('form', function ($query) use ($userId, $spaces) {
                $query->where('user_id', $userId)
                    ->orWhereJsonContains('collaborators', (string) $userId)
                    ->orWhereJsonContains('spaces', array_map('strval', $spaces));
            });
        })
        ->orderBy('created_at', 'desc') // Order by created_at descending
        ->limit($limit)
        ->get();
    }

    # form stats (count)
    public static function formStats($userId, int $limit=5) : object
    {
        $spaces = Space::where('user_id', $userId)
            ->orWhereJsonContains('members', (string) $userId)->get()->pluck('id')->toArray();
        if(!$spaces || !count($spaces)) $spaces = [0];

        return static::with('form')
        ->where(function ($query) use ($userId, $spaces) {
            $query->whereHas('form', function ($query) use ($userId, $spaces) {
                $query->where('user_id', $userId)
                    ->orWhereJsonContains('collaborators', (string) $userId)
                    ->orWhereJsonContains('spaces', array_map('strval', $spaces));
            });
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