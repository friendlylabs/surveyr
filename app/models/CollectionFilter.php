<?php

namespace App\Models;

/**
 * A saved submission filter of a form. Shared by everyone with access to the
 * form; `filters` is in the shape Collection::formCollections() takes.
 */
class CollectionFilter extends Model
{
    protected $table = 'collection_filters';
    protected $fillable = [
        "name",
        "description",
        "form_id",
        "filters"
    ];

    public $timestamps = true;
    protected $casts = [
        "filters" => "json",
        "created_at" => "datetime",
        "updated_at" => "datetime",
    ];

    protected $attributes = [
        "filters" => "{}"
    ];

    # a form's saved filters, newest first, each with how many submissions it matches
    public static function ofForm(int $formId, bool $withCounts = false) : object
    {
        $filters = static::where('form_id', $formId)->orderBy('created_at', 'desc')->get();

        if ($withCounts) {
            $filters->each(function ($filter) use ($formId) {
                $filter->match_count = Collection::filteredQuery($formId, $filter->filters ?: null)->count();
            });
        }

        return $filters;
    }

    public function form()
    {
        return $this->belongsTo(Form::class, 'form_id');
    }
}
