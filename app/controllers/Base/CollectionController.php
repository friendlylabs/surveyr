<?php

namespace App\Controllers\Base;

/**
 * ----------------------------------------------------
 * Collection Controller
 * ----------------------------------------------------
 * 
 * This controller is responsible for handling form submissions
 * and related functionalities.
 */

use Wireblob\Wire;
use App\Controllers\Controller;
use App\Controllers\Base\FormsController;

use App\Models\Collection;
use App\Models\CollectionPayload;
use App\Models\Form;
use App\Models\CollectionFilter;
use App\Services\AttachmentExport;

class CollectionController extends Controller
{
    protected FormsController $formInstance;

    public function __construct()
    {
        parent::__construct();
        $this->formInstance = new FormsController();
    }

    /**
    * Store a new submission
    *
    * @param string $hash
    * @param string $slug
    * @return mixed
    */
    public function store($hash, $slug)
    {
        try{
            $form = Form::publicForm($hash);
            if(!$form) return $this->jsonError("Form not found");

            $content = request()->params('content');
            if(!$content) return $this->jsonError("Submission data is required");

            $data =[
                'form_id' => $form->id
            ];

            $collection = Collection::create($data);
            if(!$collection) return $this->jsonError("Failed to save submission");

            # Create the payload
            $payloadData = [
                'collection_id' => $collection->id,
                'submission' => $content
            ];
            
            $payload = CollectionPayload::create($payloadData);
            if(!$payload) {
                $collection->delete(); // Clean up if payload creation fails
                return $this->jsonError("Failed to save submission data");
            }

            # TODO: Hooks and notifications
            $this->triggerNotifications($form);

            return $this->jsonSuccess("Submission saved successfully");
        }

        catch(\Exception $e){
            return $this->jsonException($e);
        }
    }

    /**
    * Show a form's submissions
    *
    * @param int $formId
    * @return mixed
    */
    public function list($formId)
    {
        # request validation
        $this->form = Form::find($formId);        
        if(!$this->form) return $this->errorPage(404);

        # validate user access
        if(
            !$this->formInstance->formOwnerShipCheck($this->form->id) && 
            !$this->formInstance->hasAccessbySpace($this->form->spaces)
        ){
            return $this->errorPage(403);
        }
        
        # data allocation
        [$filters, $this->activeFilter] = $this->requestFilters($this->form);

        $this->submissions = Collection::formCollections($formId, true, $filters);
        $this->flags = $this->form->flagGroup?->flags;
        $this->hasAttachments = (bool) AttachmentExport::questions($this->form->content);

        $this->renderPage("Form Submissions", "app.collections.list");
    }

    /**
     * Download the files attached to a form's submissions as a ZIP.
     * Takes the selected `ids`, or else the page's `filters` / `view`, same as the list.
     */
    public function exportAttachments($formId)
    {
        try{
            $form = Form::find($formId);
            if(!$form) return $this->jsonError("Form not found", 404);

            if(!$this->formInstance->formOwnerShipCheck($form->id) && !$this->formInstance->hasAccessbySpace($form->spaces))
                return $this->jsonError("You don't have access to this form", 403);

            $questions = AttachmentExport::questions($form->content);
            if(!$questions) return $this->jsonError("This form has no file questions");

            $ids = array_values(array_filter(array_map('intval', (array) request()->params('ids', []))));
            [$filters] = $this->requestFilters($form);

            set_time_limit(0);
            $result = (new AttachmentExport($form->id, $questions))->build($filters, $ids);
            if($result['error']) return $this->jsonError($result['error']);

            $name = ($form->slug ?: 'form-' . $form->id) . '_attachments_' . date('Y-m-d') . '.zip';

            while (ob_get_level()) ob_end_clean();
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . $name . '"');
            header('Content-Length: ' . filesize($result['path']));
            header('X-Attachment-Count: ' . $result['files']);
            readfile($result['path']);
            @unlink($result['path']);
            exit;
        }

        catch(\Exception $e){
            return $this->jsonException($e);
        }
    }

    /**
     * The filters a submissions page applies, and the saved filter it came from.
     * Ad-hoc `?filters=<base64>` wins; `?view=<id>` loads one of the form's saved filters.
     *
     * @return array{0: ?array, 1: ?CollectionFilter}
     */
    protected function requestFilters(Form $form) : array
    {
        $view = request()->params('view');
        $saved = $view ? CollectionFilter::where('form_id', $form->id)->find((int) $view) : null;

        $filters = json_decode(urldecode(base64_decode(request()->params('filters') ?? '')), true);
        if(is_array($filters)) return [$filters, $saved];

        return [$saved?->filters ?: null, $saved];
    }

    /**
     * Visualize a form's submissions
     *
     * @param int $formId
     * @return mixed
     */
    public function visualize($formId)
    {
        # request validation
        $this->form = Form::find($formId);
        if(!$this->form) return $this->errorPage(404);

        # validate user access
        if(
            !$this->formInstance->formOwnerShipCheck($this->form->id) && 
            !$this->formInstance->hasAccessbySpace($this->form->spaces)
        ){
            return $this->errorPage(403);
        }

        # data allocation
        // $this->tabsInfo = null;
        // $this->questions = $this->extractQuestions($form->content);

        # data allocation
        [$filters, $this->activeFilter] = $this->requestFilters($this->form);

        $this->collections = 
            Collection::formCollections($formId, false, $filters)
            ->pluck('submission')->toArray();

        return $this->renderPage($this->form->title, "app.collections.visualize");
    }

    /**
     * Show Submission (AJAX)
     * 
     * @param int $id
     * @return \Leaf\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            # fetch the collection and form data
            $collection = Collection::find($id);
            if(!$collection) {
                return $this->jsonError("Submission not found");
            }

            $form = Form::find($collection->form_id);
            if(!$form) {
                return $this->jsonError("Form not found");
            }

            $formContent = $form->content;
            $formContent['mode'] = 'display';

            # validate user access
            if(
                !$this->formInstance->formOwnerShipCheck($form->id) && 
                !$this->formInstance->hasAccessbySpace($form->spaces)
            ){
                return response()->json([
                    'status' => false,
                    'message' => "You don't have access to this form",
                    'html' => null
                ]);
            }

            # data allocation
            $this->form = $form;
            $this->collection = $collection;
            $this->formContent = $formContent;

            // $html = view('app.collections.show', $this->data);

            return response()->json([
                'status' => true,
                'message' => 'Submission loaded successfully',
                'submission' => $collection->submission,
            ]);
        }
        
        catch (\Exception $e) {
            return $this->jsonException($e);
        }
    }

    /**
     * Flag a submission (AJAX). An empty flag clears it.
     * 
     * @param int $id submission id
     * @return \Leaf\Http\JsonResponse
     */
    public function flag($id)
    {
        try{
            $collection = Collection::find($id);
            if(!$collection) return $this->jsonError("Submission not found", 200);

            $form = $this->flaggableForm($collection->form_id);
            if(is_string($form)) return $this->jsonError($form, 200);

            $flag = $this->requestedFlag($form);
            if($flag === false) return $this->jsonError("That flag is not part of this form's flag group", 200);

            $collection->flag = $flag;
            if(!$collection->save()) return $this->jsonError("Failed to save flag");

            $this->flag = $flag;
            return $this->jsonSuccess($flag ? "Flag saved" : "Flag cleared");
        }

        catch(\Exception $e){
            return $this->jsonException($e);
        }
    }

    /**
     * Flag several submissions of one form at once (AJAX).
     * 
     * @param int $formId
     * @return \Leaf\Http\JsonResponse
     */
    public function flagBulk($formId)
    {
        try{
            $form = $this->flaggableForm($formId);
            if(is_string($form)) return $this->jsonError($form, 200);

            $flag = $this->requestedFlag($form);
            if($flag === false) return $this->jsonError("That flag is not part of this form's flag group", 200);

            $ids = array_filter(array_map('intval', (array) request()->params('ids', [])));
            if(!$ids) return $this->jsonError("Select at least one submission", 200);

            $count = Collection::where('form_id', $form->id)->whereIn('id', $ids)->update(['flag' => $flag]);

            $this->flag = $flag;
            return $this->jsonSuccess(($flag ? "Flagged " : "Cleared the flag on ") . $count . " " . ($count === 1 ? "submission" : "submissions"));
        }

        catch(\Exception $e){
            return $this->jsonException($e);
        }
    }

    /** The form, if the user may flag its submissions and it has a flag group; else an error message. */
    protected function flaggableForm($formId) : Form|string
    {
        $form = Form::find($formId);
        if(!$form) return "Form not found";

        if(!$this->formInstance->formOwnerShipCheck($form->id) && !$this->formInstance->hasAccessbySpace($form->spaces))
            return "You don't have access to this form";

        if(!$form->flagGroup) return "This form has no flag group";

        return $form;
    }

    /** The posted flag key, null to clear, or false when it is not in the form's group. */
    protected function requestedFlag(Form $form) : string|null|false
    {
        $flag = (string) request()->params('flag', '');
        if($flag === '') return null;

        return in_array($flag, $form->flagGroup->flagKeys(), true) ? $flag : false;
    }

    /**
     * Extract questions from form content
     * 
     * @param array $formQuestions
     * @return array
     */
    protected function extractQuestions(array $formQuestions) : array
    {
        $questions = [];
        foreach($formQuestions as $question){
            if(!isset($question['title'])) continue;
            $questions[$question['name']] = trim($question['title']) != '' ? $question['title'] : null; 
        }

        return $questions;
    }

    /**
     * Tabify form 
     * Generate tabs from form content
     * 
     * @param array $formContent
     * @return array
     */
    function tabifyInfo($formContent) {
        
        $tabs = [];
        
        foreach ($formContent['pages'] as $page) {
            
            $questions = array_map(function($element) {
                return $element['name'];
            }, $page['elements']);
            
            // TODO: Tabify visualization
            $tabs[] = [
                'name' => $page['title'] ?? 'Page',
                'questions' => $questions
            ];
        }
        
        return $tabs;
    }

    /**
     * Clear all submissions for a form
     * 
     * @param int $formId
     * @return void
     */
    public function clear($formId)
    {
        $form = Form::find($formId);
        if(!$form) return $this->errorPage(404);

        # validate user access
        if(!$form->canBePurgedBy((int) auth()->id())){
            return $this->errorPage(403);
        }

        # Get all collection IDs for this form
        $collectionIds = Collection::where('form_id', $formId)->pluck('id');
        
        # Delete payloads first
        CollectionPayload::whereIn('collection_id', $collectionIds)->delete();
        
        # Then delete collections
        Collection::where('form_id', $formId)->delete();
        
        return redirect(route('forms.submissions', $formId));    
    }

    protected function triggerNotifications(Form $form)
    {
        // check if Wire is configured
        if(!_env('WIRE_APP_KEY') || !_env('WIRE_APP_SECRET') || !_env('WIRE_APP_ID')) {
            return; // Wire is not configured, skip notification
        }

        $wire = new Wire(
            _env('WIRE_APP_KEY'),
            _env('WIRE_APP_SECRET'),
            _env('WIRE_APP_ID'),
            [ 'host' => 'eu-central-1.wireblob.com' ]
        );

        $wire->trigger('notification', 'new-submission', [
            'form_id' => $form->id,
            'form_title' => $form->title,
            'message' => "New submission received for form: {$form->title}"
        ]);
    }

    public static function routes(){
        app()::get('/visualize/{form}', ['name'=>'forms.visualize', 'CollectionController@visualize']);
        
        app()::get('/submissions/show/{form}', ['name'=>'collections.show', 'CollectionController@show']);
        app()::get('/submissions/all/{form}', ['name'=>'forms.submissions', 'CollectionController@list']);
        app()::get('/submissions/clear/{form}', ['name'=>'collections.clear', 'CollectionController@clear']);

        app()::post('/submissions/flag/{id}', ['name'=>'collections.flag', 'CollectionController@flag']);
        app()::post('/submissions/flag-bulk/{form}', ['name'=>'collections.flagBulk', 'CollectionController@flagBulk']);
        app()::post('/submissions/attachments/{form}', ['name'=>'collections.attachments', 'CollectionController@exportAttachments']);
    }
}