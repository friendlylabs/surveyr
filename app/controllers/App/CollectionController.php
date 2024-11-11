<?php

namespace App\Controllers\App;

use App\Controllers\Controller;
use App\Models\Collection;
use App\Models\Form;

class CollectionController extends Controller
{
    public function __construct()
    {
        parent::__construct();
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
    public function show($formId)
    {
        # request validation
        $form = Form::find($formId);
        if(!$form) return $this->errorPage(404);

        $this->active = 'forms';

        # data allocation
        $this->form = $form;
        $this->collections = Collection::formCollections($formId);
        $this->questions = $this->extractQuestions($form->questions);

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

        $this->active = 'forms';

        # data allocation
        $this->form = $form;
        $this->tabsInfo = $this->tabifyInfo($form->content);
        $this->questions = $this->extractQuestions($form->content);
        $this->collections = Collection::formCollections($formId)->pluck('submission')->toArray();

        $this->renderPage("Visualize Submissions for $form->title", "app.collections.visualize");
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

    # TODO: Review Scales
    public static function routes(){
        app()::get('/submissions/{form}', ['name'=>'forms.submissions', 'CollectionController@show']);
        app()::get('/visualize/{form}', ['name'=>'forms.visualize', 'CollectionController@visualize']);
    }
}