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

    public static function formCollections(int $form_id, bool $remap = false, ?array $filters = null): object
    {
        $query = static::where('form_id', $form_id);

        if ($filters) {
            // Handle core filters first (direct structure)
            if (isset($filters['from_date'])) {
                $query->whereDate('created_at', '>=', $filters['from_date']);
                unset($filters['from_date']);
            }
            
            if (isset($filters['to_date'])) {
                $query->whereDate('created_at', '<=', $filters['to_date']);
                unset($filters['to_date']);
            }
            
            if (isset($filters['review_type']) && is_array($filters['review_type']) && !empty($filters['review_type'])) {
                $query->whereIn('review', $filters['review_type']);
                unset($filters['review_type']);
            }

            // Handle dynamic survey question filters (array structure)
            // Core filters have been removed, so we only process dynamic filters
            foreach ($filters as $question => $rules) {
                $query->where(function ($group) use ($question, $rules) {
                    foreach ($rules as $rule) {
                        $group->orWhere(function ($q) use ($question, $rule) {
                            $key = "JSON_UNQUOTE(JSON_EXTRACT(submission, '$.\"$question\"'))";
                            $val = $rule['value'] ?? null;

                            switch ($rule['type']) {
                                case 'text':
                                case 'fuzzy':
                                    if ($rule['operator'] === 'contains' && is_array($val)) {
                                        $q->where(function ($subQ) use ($key, $val) {
                                            foreach ($val as $v) {
                                                $pattern = '%' . strtolower($v) . '%';
                                                $subQ->orWhereRaw("LOWER($key) LIKE ?", [$pattern]);
                                            }
                                        });
                                    } else {
                                        $pattern = match ($rule['operator']) {
                                            'contains' => '%' . strtolower($val) . '%',
                                            'startsWith' => strtolower($val) . '%',
                                            'endsWith' => '%' . strtolower($val),
                                            default => strtolower($val),
                                        };
                                        $q->whereRaw("LOWER($key) LIKE ?", [$pattern]);
                                    }
                                    break;

                                case 'numeric':
                                    if ($rule['operator'] === 'range' && is_array($val)) {
                                        if (!empty($val['min'])) {
                                            $q->whereRaw("CAST($key AS DECIMAL) >= ?", [$val['min']]);
                                        }
                                        if (!empty($val['max'])) {
                                            $q->whereRaw("CAST($key AS DECIMAL) <= ?", [$val['max']]);
                                        }
                                    } else {
                                        $numericOp = match ($rule['operator']) {
                                            'equals' => '=',
                                            'notEquals' => '!=',
                                            'gt' => '>',
                                            'gte' => '>=',
                                            'lt' => '<',
                                            'lte' => '<=',
                                            default => '=',
                                        };
                                        $q->whereRaw("CAST($key AS DECIMAL) $numericOp ?", [$val]);
                                    }
                                    break;

                                case 'check':
                                    if ($rule['operator'] === 'empty') {
                                        $q->whereRaw("($key IS NULL OR $key = '')");
                                    } else {
                                        $q->whereRaw("($key IS NOT NULL AND $key != '')");
                                    }
                                    break;

                                case 'choice':
                                    switch ($rule['operator']) {
                                        case 'equals':
                                            if (is_array($val)) {
                                                $q->where(function ($subQ) use ($key, $val) {
                                                    foreach ($val as $v) {
                                                        $subQ->orWhereRaw("LOWER($key) = ?", [strtolower($v)]);
                                                    }
                                                });
                                            } else {
                                                $q->whereRaw("LOWER($key) = ?", [strtolower($val)]);
                                            }
                                            break;
                                        
                                        case 'notEqual':
                                            if (is_array($val)) {
                                                foreach ($val as $v) {
                                                    $q->whereRaw("LOWER($key) != ?", [strtolower($v)]);
                                                }
                                            } else {
                                                $q->whereRaw("LOWER($key) != ?", [strtolower($val)]);
                                            }
                                            break;
                                        
                                        case 'contains':
                                            if (is_array($val)) {
                                                $q->where(function ($subQ) use ($key, $val) {
                                                    foreach ($val as $v) {
                                                        $pattern = '%' . strtolower($v) . '%';
                                                        $subQ->orWhereRaw("LOWER($key) LIKE ?", [$pattern]);
                                                    }
                                                });
                                            } else {
                                                $pattern = '%' . strtolower($val) . '%';
                                                $q->whereRaw("LOWER($key) LIKE ?", [$pattern]);
                                            }
                                            break;
                                        
                                        default:
                                            // Fallback to equals for backward compatibility
                                            if (is_array($val)) {
                                                $q->where(function ($subQ) use ($key, $val) {
                                                    foreach ($val as $v) {
                                                        $subQ->orWhereRaw("LOWER($key) = ?", [strtolower($v)]);
                                                    }
                                                });
                                            } else {
                                                $q->whereRaw("LOWER($key) = ?", [strtolower($val)]);
                                            }
                                            break;
                                    }
                                    break;
                            }
                        });
                    }
                });
            }
        }

        $collection = $query->get();

        if ($remap) {
            return $collection->lazy()->map(function ($item) {
                $submission = is_array($item->submission)
                    ? $item->submission
                    : json_decode($item->submission, true);
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