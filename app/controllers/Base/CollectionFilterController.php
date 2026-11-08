<?php

namespace App\Controllers\Base;

/**
 * ----------------------------------------------------
 * Collection Filter Controller
 * ----------------------------------------------------
 *
 * Saved submission filters of a form. They belong to the form, so anyone
 * with access to it may create, change or delete them.
 */

use App\Controllers\Controller;
use App\Controllers\Base\FormsController;

use App\Models\Form;
use App\Models\CollectionFilter;
use App\Services\ReportSchema;

class CollectionFilterController extends Controller
{
    private const RULE_TYPES = ['text', 'fuzzy', 'numeric', 'check', 'choice'];

    protected FormsController $formInstance;

    public function __construct()
    {
        parent::__construct();
        $this->formInstance = new FormsController();
    }

    # Save a new filter on a form
    public function store($formId)
    {
        try{
            $form = Form::find($formId);
            if(!$form) return $this->jsonError("Form not found", 404);
            if(!$this->canAccess($form)) return $this->jsonError("You don't have access to this form", 403);

            $data = $this->validated($form);
            if(is_string($data)) return $this->jsonError($data, 200);

            $filter = CollectionFilter::create($data + ['form_id' => $form->id]);
            if(!$filter) return $this->jsonError("Failed to save the filter");

            $this->filter = $filter->only(['id', 'name', 'description', 'filters']);
            $this->redirect = $this->afterSave($form, $filter);
            return $this->jsonSuccess("Filter saved");
        }

        catch(\Exception $e){
            return $this->jsonException($e);
        }
    }

    # Change a saved filter
    public function update($id)
    {
        try{
            $filter = CollectionFilter::find($id);
            if(!$filter) return $this->jsonError("Filter not found", 404);

            $form = Form::find($filter->form_id);
            if(!$form || !$this->canAccess($form)) return $this->jsonError("You don't have access to this form", 403);

            $data = $this->validated($form);
            if(is_string($data)) return $this->jsonError($data, 200);

            $filter->fill($data);
            if(!$filter->save()) return $this->jsonError("Failed to update the filter");

            $this->filter = $filter->only(['id', 'name', 'description', 'filters']);
            $this->redirect = $this->afterSave($form, $filter);
            return $this->jsonSuccess("Filter updated");
        }

        catch(\Exception $e){
            return $this->jsonException($e);
        }
    }

    # Delete a saved filter
    public function delete($id)
    {
        $filter = CollectionFilter::find($id);
        if(!$filter) return $this->errorPage(404);

        $form = Form::find($filter->form_id);
        if(!$form || !$this->canAccess($form)) return $this->errorPage(403);

        $filter->delete();
        return response()->redirect(route('forms.setup', $form->id, $form->slug) . '?tab=submissions');
    }

    protected function canAccess(Form $form) : bool
    {
        return $this->formInstance->formOwnerShipCheck($form->id)
            || $this->formInstance->hasAccessbySpace($form->spaces);
    }

    # back to wherever the save came from: the submissions table or the overview
    protected function afterSave(Form $form, CollectionFilter $filter) : string
    {
        return request()->params('return') === 'submissions'
            ? route('forms.submissions', $form->id) . '?view=' . $filter->id
            : route('forms.setup', $form->id, $form->slug) . '?tab=submissions';
    }

    /**
     * The posted name / description / filters, cleaned; or an error message.
     * `filters` arrives as the JSON FilterBuilder.compileFilters() produces.
     */
    protected function validated(Form $form) : array|string
    {
        $name = trim(html_entity_decode((string) request()->params('name')));
        $description = trim(html_entity_decode((string) request()->params('description')));

        if(mb_strlen($name) < 2 || mb_strlen($name) > 120)
            return "The filter name must be between 2 and 120 characters";

        if(mb_strlen($description) > 300)
            return "The description can be at most 300 characters";

        $raw = json_decode(html_entity_decode((string) request()->params('filters')), true);
        if(!is_array($raw)) return "The filter rules could not be read";

        // same cleaning as report filters, then drop rules the query can't run
        $filters = ReportSchema::normalizeFilters([(string) $form->id => $raw], [(int) $form->id])[(string) $form->id] ?? [];

        foreach ($filters as $key => $rules) {
            if(in_array($key, ['from_date', 'to_date', 'flag'], true)) continue;

            $rules = array_values(array_filter($rules, fn ($rule) =>
                in_array($rule['type'] ?? null, self::RULE_TYPES, true) && is_string($rule['operator'] ?? null)
            ));

            if($rules) $filters[$key] = $rules;
            else unset($filters[$key]);
        }

        if(!$filters) return "Add at least one condition: a date, a flag or a rule";

        return [
            'name' => $name,
            'description' => $description !== '' ? $description : null,
            'filters' => $filters,
        ];
    }

    public static function routes()
    {
        app()::post('/filters/store/{form}', ['name'=>'collections.filters.store', 'CollectionFilterController@store']);
        app()::post('/filters/update/{id}', ['name'=>'collections.filters.update', 'CollectionFilterController@update']);
        app()::get('/filters/delete/{id}', ['name'=>'collections.filters.delete', 'CollectionFilterController@delete']);
    }
}
