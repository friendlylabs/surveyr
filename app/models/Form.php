<?php

namespace App\Models;

use Leaf\Database as DB;

class Form extends Model
{    
    protected $table = 'forms';
    protected $fillable = [
        'title',
        'description',
        'slug',
        'content',
        'questions',    // summary of content, indexes only questions
        'user_id',
        'collaborators',
        'spaces',
        'is_locked',
        'current_editor',
        'is_indefinite',
        'reviews',
        'webhook_url',
        'access_code',
        'theme',
        'start_date',
        'end_date',
        'viz_rules'
    ];

    public $timestamp = true;
    protected $with = ['user'];
    
    protected $casts = [
        'content' => 'json',
        'questions' => 'json',
        'collaborators' => 'json',
        'viz_rules' => 'json',
        'spaces' => 'json',
        'reviews' => 'json',
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
        'content' => '[]',
        'reviews' => '["reviewed", "pending"]'
    ];

    /**
     * Forms a user may work with: their own, ones they collaborate on, and
     * ones shared into any of their spaces. One JSON_CONTAINS per space —
     * passing the whole array would require the form to sit in *every* space.
     */
    public static function accessibleQuery(int $userId)
    {
        $spaces = Space::absoluteUserSpaces($userId);

        return static::where(function ($query) use ($userId, $spaces) {
            $query->where('user_id', $userId)
                ->orWhereJsonContains('collaborators', (string) $userId);

            foreach ($spaces as $space) {
                $query->orWhereJsonContains('spaces', (string) $space);
            }
        });
    }

    public static function accessibleIds(int $userId): array
    {
        return static::accessibleQuery($userId)->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    # user forms
    public static function userForms($userId) : object
    {
        return static::accessibleQuery((int) $userId)
            ->withCount('collections')->withReportsCount()
            ->orderBy('created_at', 'desc')->get();
    }

    # search user forms
    public static function searchUserForms($userId, $string) : object
    {
        return static::accessibleQuery((int) $userId)->where(function($query) use ($string) {
            $query->where('title', 'like', "%$string%")
                ->orWhere('description', 'like', "%$string%");
        })->orderBy('created_at', 'desc')->get();
    }

    # active forms (start_date < now, end_date > now) or (is_indefinite = 1)
    public static function openForms($userId) : object
    {
        return static::where('user_id', $userId)
            ->where(function($query) {
                $query->where('start_date', '<', now())
                    ->where('end_date', '>', now())
                    ->orWhere('is_indefinite', 1);
            })->get();
    }

    # public form
    public static function publicForm($md5Id){
        return static::where(DB::$capsule::raw("MD5(id)"), $md5Id)
            ->first();
    }

    # users collaborating on any of the given forms, keyed by id (single query)
    public static function collaboratorsOf($forms) : object
    {
        $ids = collect($forms)->pluck('collaborators')->flatten()->filter()->unique();
        return $ids->isEmpty() ? collect() : User::whereIn('id', $ids)->get()->keyBy('id');
    }

    # only the super admin (user 1), the author or a direct collaborator may purge submissions
    public function canBePurgedBy(int $userId) : bool
    {
        return $userId === 1
            || (int) $this->user_id === $userId
            || in_array((string) $userId, array_map('strval', $this->collaborators ?? []), true);
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

    # belongs to user
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    # has many collections
    public function collections()
    {
        return $this->hasMany(Collection::class);
    }

    # reports drawing on this form (ids live in reports.forms, so this is a query, not a relation)
    public function reports()
    {
        return Report::forForm((int) $this->id);
    }

    # adds `reports_count`, the withCount() equivalent for the JSON link
    public function scopeWithReportsCount($query)
    {
        return $query->addSelect([
            'reports_count' => Report::selectRaw('COUNT(*)')
                ->whereRaw('JSON_CONTAINS(reports.forms, CAST(forms.id AS JSON))'),
        ]);
    }
}