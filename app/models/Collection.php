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
    public static function formCollections(int $form_id, $remap = false) : object
    {
        $collection = static::where('form_id', $form_id)->get();
        if($remap) {
            return $collection->lazy()->map(function ($item) {
                $submission = is_array($item->submission) ? $item->submission : json_decode($item->submission, true);
                $submission['id'] = $item->id;
                $submission['review'] = $item->review;
                return $submission;
            });
        }

        return $collection;
    }
    
    # recent submissions
    public static function recentSubmissions(int $userId, int $limit=5) : object
    {
        # cols: formId, formName, submission:int, pending_review:int, last_submission
        $spaces = Space::absoluteUserSpaces($userId);
        return self::where(function ($query) use ($userId, $spaces) {
            $query->whereHas('form', function ($query) use ($userId, $spaces) {
                $query->where('user_id', $userId)
                      ->orWhereJsonContains('collaborators', (string) $userId)
                      ->orWhereJsonContains('spaces', array_map('strval', $spaces));
            });
        })

        # calculate submission count, pending review count, latest submission
        ->select(
            'form_id',
            DB::$capsule::raw('COUNT(id) as submission'),
            DB::$capsule::raw('SUM(review = "pending") as pending_review'),
            DB::$capsule::raw('MAX(created_at) as latest_submission'))
        
        // form name, slug
        ->with(['form' => function($query) {
            $query->select('id', 'title', 'slug');
        }])

        ->orderBy('latest_submission', 'desc')
        ->groupBy('form_id')
        ->limit($limit)
        ->get();
    }

    # form stats (count)
    public static function formStats($userId, int $limit=5) : object
    {
        $spaces = Space::where('user_id', $userId)
            ->orWhereJsonContains('members', (string) $userId)->get()->pluck('id')->toArray();
        if(!$spaces || !count($spaces)) $spaces = [0];

        $statistics = static::with('form')
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

        // unset form.content, form.questions, form.description, stat.submission
        $statistics->map(function($stat) {
            unset($stat->form->content, $stat->form->questions, $stat->form->description);
            return $stat;
        });

        return $statistics;
    }


    # belongs to form
    public function form()
    {
        return $this->belongsTo(Form::class, 'form_id');
    }
}