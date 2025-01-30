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

use App\Controllers\Controller;
use App\Controllers\Base\FormsController;

use App\Models\Collection;
use App\Models\Form;

class CollectionController extends Controller
{
    protected $formInstance;

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
            $form = Form::publicForm($hash, $slug);
            if(!$form) return $this->jsonError("Form not found");

            $content = request()->params('content');
            if(!$content) return $this->jsonError("Submission data is required");

            $data =[
                'form_id' => $form->id,
                'submission' => $content
            ];

            $collection = Collection::create($data);
            if(!$collection) return $this->jsonError("Failed to save submission");

            # TODO: Hooks and notifications

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
        $form = Form::find($formId);
        if(!$form) return $this->errorPage(404);

        # validate user access
        if(!$this->formInstance->formOwnerShipCheck($form->id) && !$this->formInstance->hasAccessbySpace($form->spaces)){
            return $this->errorPage(403);
        }
        
        # data allocation
        $this->form = $form;
        $this->submissions = Collection::formCollections($formId, true);

        $this->renderPage("Submissions for $form->title", "app.collections.list");
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
        $form = Form::find($formId);
        if(!$form) return $this->errorPage(404);

        # validate user access
        if(!$this->formInstance->formOwnerShipCheck($form->id) && !$this->formInstance->hasAccessbySpace($form->spaces)){
            return $this->errorPage(403);
        }

        # data allocation
        $this->form = $form;
        $this->tabsInfo = $this->tabifyInfo($form->content);
        $this->questions = $this->extractQuestions($form->content);
        $this->collections = Collection::formCollections($formId)->pluck('submission')->toArray();

        $this->renderPage("Visualize Submissions for $form->title", "app.collections.visualize");
    }

    /**
     * Show Submission
     * 
     * @param int $id
     * @return void
     */
    public function show($id)
    {
        # fetch the collection and form data
        $collection = Collection::find($id);
        if(!$collection) return $this->errorPage(404);

        $form = Form::find($collection->form_id);
        if(!$form) return $this->errorPage(404);

        $formContent = $form->content;
        $formContent['mode'] = 'display';

        # validate user access
        if(!$this->formInstance->formOwnerShipCheck($form->id) && !$this->formInstance->hasAccessbySpace($form->spaces)){
            return $this->errorPage(403);
        }

        # data allocation
        $this->form = $form;
        $this->collection = $collection;
        $this->formContent = $formContent;

        $this->renderPage($form->title, "app.collections.show");
    }

    /**
     * Review Submission
     * 
     * @param int $id
     * @return void
     */
    public function review($id)
    {
        try{
            # fetch the collection and form data
            $collection = Collection::find($id);
            if(!$collection) return $this->jsonError("Submission not found");

            $form = Form::find($collection->form_id);
            if(!$form) return $this->jsonError("Form not found");

            # validate user access
            if(!$this->formInstance->formOwnerShipCheck($form->id) && !$this->formInstance->hasAccessbySpace($form->spaces)){
                return $this->jsonError("You don't have access to this form");
            }

            $review = request()->params('review');
            if(!$review || !is_array($form->reviews))
                return $this->jsonError("Review data is required");

            $collection->review = $review;
            if(!$collection->save())
                return $this->jsonError("Failed to save review");

            return $this->jsonSuccess("Review saved successfully");
        }

        catch(\Exception $e){
            return $this->jsonException($e);
        }

    }


    /**
     * Extract questions from form content
     * 
     * @param array $formContent
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
        if(!$this->formInstance->formOwnerShipCheck($form->id)){
            return $this->errorPage(403);
        }

        Collection::where('form_id', $formId)->delete();
        return redirect(route('forms.submissions', $formId));    
    }

    # TODO: Review Scales
    public static function routes(){
        app()::get('/visualize/{form}', ['name'=>'forms.visualize', 'CollectionController@visualize']);

        app()::get('/submissions/show/{form}', ['name'=>'collections.show', 'CollectionController@show']);
        app()::get('/submissions/all/{form}', ['name'=>'forms.submissions', 'CollectionController@list']);
        app()::get('/submissions/clear/{form}', ['name'=>'collections.clear', 'CollectionController@clear']);

        app()::post('/submissions/review/{id}', ['name'=>'collections.review', 'CollectionController@review']);
    }
}