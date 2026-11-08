<?php

namespace App\Models;
use Leaf\Database as DB;

class Collection extends Model
{
    protected $table = 'collections';
    protected $fillable = [
        "form_id",
        'flag',
        'is_archived',
        'is_optimized'
    ];

    public $timestamps = true;
    protected $casts = [
        "created_at" => "datetime",
        "updated_at" => "datetime",
        "is_archived" => "boolean",
        "is_optimized" => "boolean",
    ];

    /**
     * A form's submissions narrowed by a filter object
     * ({ from_date, to_date, flag: [], "<question>": [rules] }), as a query
     * so callers can count, page or load it.
     */
    public static function filteredQuery(int $form_id, ?array $filters = null)
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
            
            if (isset($filters['flag']) && is_array($filters['flag']) && !empty($filters['flag'])) {
                // `__none` stands for unflagged submissions
                $flags = array_values(array_diff($filters['flag'], ['__none']));
                $unflagged = in_array('__none', $filters['flag'], true);

                $query->where(function ($q) use ($flags, $unflagged) {
                    if ($flags) $q->whereIn('flag', $flags);
                    if ($unflagged) $q->orWhereNull('flag');
                });
            }
            unset($filters['flag'], $filters['review_type']);

            // Handle dynamic survey question filters (array structure)
            // Core filters have been removed, so we only process dynamic filters
            foreach ($filters as $question => $rules) {
                $query->where(function ($group) use ($question, $rules) {
                    foreach ($rules as $rule) {
                        $group->orWhereHas('payload', function ($q) use ($question, $rule) {
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
                                        
                                        case 'notEquals':
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

        return $query;
    }

    public static function formCollections(int $form_id, bool $remap = false, ?array $filters = null): object
    {
        $collection = static::filteredQuery($form_id, $filters)->with('payload')->orderByDesc('id')->get();

        if ($remap) {
            return $collection->lazy()->map(function ($item) {
                $submission = $item->payload && $item->payload->submission
                    ? (is_array($item->payload->submission)
                        ? $item->payload->submission
                        : json_decode($item->payload->submission, true))
                    : [];
                $submission['id'] = $item->id;
                $submission['flag'] = $item->flag;
                $submission['created_at'] = $item->created_at?->toIso8601String();
                return $submission;
            });
        }

        return $collection;
    }

    # recent submissions
    public static function recentSubmissions(int $userId, int $limit=5) : object
    {
        # cols: formId, formName, submission:int, unflagged:int, last_submission
        $spaces = Space::absoluteUserSpaces($userId);
        return self::where(function ($query) use ($userId, $spaces) {
            $query->whereHas('form', function ($query) use ($userId, $spaces) {
                $query->where('user_id', $userId)
                      ->orWhereJsonContains('collaborators', (string) $userId)
                      ->orWhereJsonContains('spaces', array_map('strval', $spaces));
            });
        })

        # calculate submission count, unflagged count, latest submission
        ->select(
            'form_id',
            DB::$capsule::raw('COUNT(id) as submission_count'),
            DB::$capsule::raw('SUM(flag IS NULL) as unflagged'),
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


    # has one payload
    public function payload()
    {
        return $this->hasOne(CollectionPayload::class, 'collection_id');
    }

    # belongs to form
    public function form()
    {
        return $this->belongsTo(Form::class, 'form_id');
    }

    # accessor for submission data
    public function getSubmissionAttribute()
    {
        return $this->payload ? $this->payload->submission : null;
    }
}