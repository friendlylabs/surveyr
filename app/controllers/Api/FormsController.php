<?php

namespace App\Controllers\Api;
use App\Controllers\Api\BaseController;

use App\Models\Form;
use Leaf\Database as DB;

class FormsController extends BaseController
{
    public function __construct()
    {
        AuthController::authorize();
        parent::__construct();
    }

    /**
     * Fetches all forms created by the user
     * 
     * @return void
     */
    public function index(){
        $forms = Form::userForms(auth()->id(), true);
        if(!$forms->count())
            return self::jsonError("No forms found", 200);

        // remap to form id to md5
        $forms = $forms->map(function ($form) {
            return array_merge($form->toArray(), ['id' => md5($form->id)]);
        });        
        

        $this->forms = $forms;
        return self::jsonSuccess("Forms fetched successfully");
    }

    /**
     * Fetches a form by its ID
     * 
     * @param string $formId
     * @return void
     */
    public function fetch(string $formId)
    {
        try{
            $form = Form::where(DB::$capsule::raw("MD5(id)"), $formId)->first();
            if(!$form) return self::jsonError("Form not found", 404);

            # validate form ownership
            if(!$this->formOwnerShipCheck($form->id) && !$this->hasAccessbySpace($form->spaces))
                return self::jsonError("You don't have access to this form", 403);

            $surveyMode = 'allowed';
            $formContent = $form->content;

            # check if form is open
            $this->formStatus = $formStatus = 
                ($form->start_date < now() && $form->end_date > now()) || $form->is_indefinite;

            if(!$formStatus){
                if($form->end_date < now()){
                    $this->message = "Form submission has ended";
                    $formContent['mode'] = 'display';             // read-only mode
                    $surveyMode = 'restricted';
                }else{
                    return response()->markup(view('app.forms.closed',[
                        'formId' => $form->id,
                    ]), 418);
                }
            }

            $unset = ['id', 'user', 'content'];
            foreach($unset as $key) unset($form->$key);

            # data allocation
            $this->form = $form;
            $this->publicForm = true;
            $this->surveyMode = $surveyMode;
            $this->formContent = $formContent;

            return self::jsonSuccess("Form fetcheded successfully");
        }

        catch(\Exception $e){
            return self::jsonException($e);
        }
    }

    public static function routes()
    {
        app()::get('', ['name'=>'api.forms.list', 'FormsController@index']);
        app()::get('fetch/{formId}', ['name'=>'api.forms.fetch', 'FormsController@fetch']);
    }
}