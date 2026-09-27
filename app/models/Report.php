<?php

namespace App\Models;

class Report extends Model
{
    protected $table = 'reports';
    protected $with = [
        'user'
    ];
    
    protected $fillable = [
        "title",
        "description",
        "content",
        "filters",
        'draft',
        "forms",
        "user_id",
        "collaborators",
        "is_public"
    ];

    public $timestamps = true;
    protected $casts = [
        "content" => "json",
        'draft' => "json",
        "filters" => "json",
        "forms" => "json",
        "collaborators" => "json",
        "created_at" => "datetime",
        "updated_at" => "datetime",
    ];

    protected $attributes = [
        "content" => "{}",
        "draft" => "{}",
        "filters" => "{}",
        "forms" => "[]",
        "collaborators" => "[]",
    ];

    /** Reports drawing on a given form. */
    public function scopeForForm($query, int $formId)
    {
        return $query->whereJsonContains('forms', $formId);
    }

    /**
     * Reports a user may open: their own, ones they collaborate on, and
     * any report drawing on a form they have access to.
     */
    public function scopeAccessibleBy($query, int $userId)
    {
        $formIds = Form::accessibleIds($userId);

        return $query->where(function ($q) use ($userId, $formIds) {
            $q->where('user_id', $userId)
                ->orWhereJsonContains('collaborators', (string) $userId);

            // one JSON_CONTAINS per form: the array form would need them all
            foreach ($formIds as $formId) {
                $q->orWhereJsonContains('forms', $formId);
            }
        });
    }

    /** Ids of the source forms, in display order. */
    public function formIds(): array
    {
        return array_values(array_map('intval', array_filter((array) $this->forms, 'is_numeric')));
    }

    /** The first source form: the default a legacy (v1) document is read against. */
    public function primaryFormId(): ?int
    {
        return $this->formIds()[0] ?? null;
    }

    /** Replace the source forms, keeping the given order and dropping duplicates. */
    public function setFormIds(array $formIds): void
    {
        $this->forms = array_values(array_unique(array_map('intval', $formIds)));
        $this->sourceFormsCache = null;
    }

    # belongs to user
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * The source forms, in display order. Not a relation — the ids live in the
     * `forms` JSON column — so forms deleted since simply drop out. Loaded once
     * per instance; see loadSourceForms() to fill a whole list in one query.
     */
    public function sourceForms()
    {
        return $this->sourceFormsCache ??= static::orderLike(
            Form::whereIn('id', $this->formIds() ?: [0])->get(),
            $this->formIds()
        );
    }

    /** Eager-load the source forms of many reports with a single query. */
    public static function loadSourceForms($reports, array $columns = ['*']): void
    {
        $ids = collect($reports)->flatMap(fn ($report) => $report->formIds())->unique()->values()->all();
        $forms = $ids ? Form::whereIn('id', $ids)->get($columns)->keyBy('id') : collect();

        foreach ($reports as $report) {
            $report->sourceFormsCache = static::orderLike(
                collect($report->formIds())->map(fn ($id) => $forms->get($id))->filter(),
                $report->formIds()
            );
        }
    }

    private static function orderLike($forms, array $ids)
    {
        return $forms->sortBy(fn ($form) => array_search((int) $form->id, $ids, true))->values();
    }

    private $sourceFormsCache = null;

    # has one report link
    public function link()
    {
        return $this->hasOne(ReportLink::class, 'report_id');
    }
}
