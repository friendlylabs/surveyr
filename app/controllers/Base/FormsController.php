<?php

namespace App\Controllers\Base;

use Faker\Factory as Faker;
use App\Controllers\Controller;

use App\Models\Form;
use App\Models\User;
use App\Models\Space;
use App\Models\Theme;
use App\Models\Template;
use App\Models\Collection;
use App\Models\ReviewType;

use App\Utils\OpenaiUtil;

class FormsController extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Show user forms
     * 
     * @return void
     */
    public function index(){
        
        $this->collabCol = true;
        $this->templates = Template::all();
        $this->forms = Form::userForms(auth()->id());

        return $this->renderPage("My Forms", "app.forms.index");
    }

    /**
     * Start a new form
     * 
     * @return void
     */
    public function build()
    {
        $faker = Faker::create();
        $title = $faker->words(3, true);
        $data = [
            'title' => $title,
            'slug' => 'Untitled Form',
            'description' => 'Untitled Form',
            'user_id' => auth()->id(),
            'content' => [
                "title" => $title,
                "description" => 'Untitled Form'
            ],
            'start_date' => now()->addDays(7),
            'end_date' => now()->addDays(14)
        ];

        $form = Form::create($data);
        if($form) return redirect(route('forms.setup', $form->id, $form->slug));
        
        throw new \Exception("Unknown error occurred, failed to create form");
    }

    /**
     * Start form from template
     * 
     * @param int $id
     * @return void
     */
    public function template($id)
    {
        $template = Template::find($id);
        if(!$template) return $this->errorPage(404);

        // check if template file exists
        if(!file_exists($template->content))
            return throw new \Exception("Template file not found");

        $content = file_get_contents($template->content);
        $data = json_decode($content, true);

        $data = [
            'title' => $data['title'],
            'slug' => slugify($data['title']) . '-' . uniqid(),
            'description' => $data['description'] ?? $data['title'],
            'user_id' => auth()->id(),
            'content' => $data,
            'start_date' => now()->addDays(7),
            'end_date' => now()->addDays(14)
        ];

        $form = Form::create($data);
        if($form) return redirect(route('forms.setup', $form->id, $form->slug));

        throw new \Exception("Unknown error occurred, failed to create form");        
    }

    /**
     * Setup form
     * 
     * @param int $id
     * @param string $slug
     * @return void
     */
    public function setup($id, $slug)
    {
        $form = Form::find($id);
        if(!$form) return $this->errorPage(404);

        if(!$this->formOwnerShipCheck($id) && !$this->hasAccessbySpace($form->spaces))
            return $this->errorPage(403);

        $this->themes = Theme::all();
        $this->reviewTypes = ReviewType::all();
        $this->spaces = Space::userSpaces(auth()->id());
        $this->users = User::where('id', '!=', auth()->id())->get();

        $this->form = $form;
        return $this->renderPage("Setup Form: $form->title", "app.forms.setup");
    }

    /**
     * Form customization
     * 
     * @param int $id
     * @param string $slug
     * @return void
     */
    public function customize($id, $slug)
    {
        $form = Form::find($id);
        if(!$form) return $this->errorPage(404);

        if(!$this->formOwnerShipCheck($id))return $this->errorPage(403);

        // alert user if form has submissions
        $collections = Collection::where('form_id', $id)->count();
        if($collections){
            echo <<<HTML
            <script>
                if(!confirm('This form already has submissions, editing the form may affect the submissions and even break out your form. Do you want to continue?')){
                    window.location.href = '/app/forms';
                }
            </script>
            HTML;
        }
        
        $this->form = $form;
        return $this->renderPage("Edit Form: $form->title", "app.forms.edit");
    }

    /**
     * Update form
     * 
     * @param int $id
     * @return void
     */
    public function update($id)
    {
        try{
            $form = Form::find($id);
            if(!$form) return $this->jsonError("Form not found");

            if(!$this->formOwnerShipCheck($id) && !$this->hasAccessbySpace($form->spaces))
                return $this->jsonError("You do not have permission to update this form");

            $content = html_entity_decode(request()->params('content'));
            $questions = html_entity_decode(request()->params('questions'));

            if(!$content || !json_decode($content, true))
                return $this->jsonError("An unknown error occured, failed to update form");

            $content = json_decode(html_entity_decode($content));
            $questions = json_decode(html_entity_decode($questions));

            # data insertion
            $form->title = $content->title ?? $form->title;
            $form->description = $content->description ?? $form->description;
            $form->content = $content ?? $form->content;
            $form->questions = $questions;

            if($form->save())
                return $this->jsonSuccess("Form updated successfully");

            return $this->jsonError("An unknown error occured, failed to update form");
        }

        catch(\Exception $e){
            return $this->jsonException($e);
        }
    }

    /**
     * Form preview
     * 
     * @param int $id
     * @return void
     */
    public function preview($id)
    {
        $form = Form::find($id);
        if(!$form) return $this->errorPage(404);

        // check form ownership
        if(!$this->formOwnerShipCheck($id) && !$this->hasAccessbySpace($form->spaces))
            return $this->errorPage(403);

        $this->form = $form;
        $this->active = 'forms';
        return $this->renderPage("$form->title", "app.forms.preview");
    }

    /**
     * Setting update
     * 
     * @param string $setting
     * @param int $id
     * @return void
     */
    public function setting($setting, $id)
    {
        try{
            $form = Form::find($id);
            if(!$form) return $this->jsonError("Form not found");

            if(!$this->formOwnerShipCheck($id) && !$this->hasAccessbySpace($form->spaces))
                return $this->jsonError("You do not have permission to update this form");

            switch($setting){
                case 'general': return $this->updateGeneral($form); break;
                case 'access': return $this->updateAccess($form); break;
                case 'submission': return $this->updateSubmission($form); break;
                case 'theme': return $this->updateTheme($form); break;
                
                default: return $this->jsonError("Invalid setting option");
            }
        }

        catch(\Exception $e){
            return $this->jsonException($e);
        }
    }

    /**
    * Update form general properties
    *
    * @param object $form
    * @return void
    */
    protected function updateGeneral($form)
    {
        $data = [
            'title' => request()->params('title'),
            'description' => request()->params('description'),
            'slug' => slugify(request()->params('title')) . '-' . uniqid()
        ];

        if(in_array(null, $data) && strlen($data['title']) < 3 && strlen($data['description']) < 10)
            return $this->jsonError("Name and description must be at least 3 and 10 characters, respectively");

        $data['collaborators'] = request()->params('collaborators', []);
        $data['spaces'] = request()->params('spaces', []);

        // update form content
        $formContent = $form->content;
        $formContent['title'] = $data['title'] == '' ? $form->title : $data['title'];
        $formContent['description'] = $data['description'] == '' ? $form->description : $data['description'];
        $data['content'] = $formContent;

        $form->fill($data);
        if($form->save()){
            return $this->jsonSuccess("Form updated successfully");
        }

        return $this->jsonError("An unknown error occured, failed to update form");
    }

    /**
     * Update form access properties
     * 
     * @param object $form
     * @return void
     */
    protected function updateAccess($form)
    {
        $data = [
            'access_code' => (request()->params('access_code') == '') ? null : request()->params('access_code'),
            'start_date' => (request()->params('start_date') == null || request()->params('start_date') == '') ? $form->start_date : request()->params('start_date')
        ];

        if(request()->params('is_indefinite', 0)):
            $data['is_indefinite'] = 1; $data['end_date'] = null;

            else: $data['end_date'] = request()->params('end_date'); $data['is_indefinite'] = 0;

                if(is_null($data['end_date']) || $data['end_date'] == '')
                    return $this->jsonError('Either set the form to indefinite of provide the EndDate');
        endif;

        $form->fill($data);
        if($form->save()){
            return $this->jsonSuccess("Form access updated successfully");
        }

        return $this->jsonError("An unknown error occured, failed to update form access");
    }

    /**
     * Update form submission properties
     * 
     * @param object $form
     * @return void
     */
    protected function updateSubmission($form)
    {
        $formContent = $form->content;

        $submission_url = request()->params('submission_url');
        if($submission_url && !filter_var($submission_url, FILTER_VALIDATE_URL))
            return $this->jsonError("Invalid submission URL");

        if(!$submission_url) $submission_url = null;

        $redirection_url = request()->params('redirection_url');
        if($redirection_url && !filter_var($redirection_url, FILTER_VALIDATE_URL))
            return $this->jsonError("Invalid redirection URL");
        else{
            $formContent['navigateToUrl'] = $redirection_url;
        }

        if(isset($formContent['navigateToUrl']) && !$redirection_url) unset($formContent['navigateToUrl']);

        $webhook_url = request()->params('webhook_url');
        if($webhook_url && !filter_var($webhook_url, FILTER_VALIDATE_URL))
            return $this->jsonError("Invalid webhook URL");

        if(!$webhook_url) $webhook_url = null;

        $form->submission_url = $submission_url;
        $form->webhook_url = $webhook_url;
        $form->content = $formContent;

        if($form->save()){
            return $this->jsonSuccess("Form submission properties updated successfully");
        }

        return $this->jsonError("An unknown error occured, failed to update form submission properties");        
    }

    /**
     * Update form theme
     * 
     * @param object $form
     * @return void
     */
    protected function updateTheme($form)
    {
        $theme = request()->params('theme');
        if(!$theme) return $this->jsonError("Invalid theme");

        $form->theme = $theme;
        if($form->save()){
            return $this->jsonSuccess("Form theme updated successfully");
        }

        return $this->jsonError("An unknown error occured, failed to update form theme");
    }

    /**
     * Search forms
     * 
     * @return void
     */
    public function search()
    {
        $search = request()->params('q');
        if(!$search) return;

        # get only id, title/name & description
        $this->forms = Form::select('id', 'title', 'description', 'slug')
            ->where('title', 'like', "%$search%")
            ->orWhere('description', 'like', "%$search%")
            ->limit(10)
            ->get();

        $this->spaces = Space::select('id', 'name', 'description', 'color')
            ->where('name', 'like', "%$search%")
            ->orWhere('description', 'like', "%$search%")
            ->limit(5)
            ->get();

        return $this->jsonSuccess("Search results");
    }

    /**
     * Show form
     * 
     * @param int $id as md5 hash
     * @param string $slug
     * @return void
     */
    public function show($hash, $slug){
        $form = Form::publicForm($hash, $slug);
        if(!$form) return $this->errorPage(404);

        $surveyMode = 'allowed';
        $formContent = $form->content;

        // check if form is open (start_date < now, end_date > now) or (is_indefinite = 1)
        $this->formStatus = $formStatus = ($form->start_date < now() && $form->end_date > now()) || $form->is_indefinite;
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

        $this->form = $form;
        $this->publicForm = true;
        $this->surveyMode = $surveyMode;
        $this->formContent = $formContent;
        return $this->renderPage($form->title, "app.forms.show");
    }

    /**
     * Delete form
     * 
     * @param int $id
     * @return void
     */
    public function delete($id){
        $form = Form::find($id);
        if(!$form) return $this->errorPage(404);

        # check form ownership
        if($form->user_id != auth()->id())
            return $this->errorPage(403);

        if(!$form->delete())
            return $this->errorPage(500);

        // delete form collections
        Collection::where('form_id', $id)->delete();
        return redirect(route('forms.list'));
    }

    /**
     * Generate form
     * 
     * @return void
     */
    public function generate(){

        try{
            $data = [
                'title' => request()->params('title'),
                'description' => request()->params('description')
            ];

            if(in_array(null, $data) && strlen($data['title']) < 10)
                return $this->jsonError("All fields are required and the title must be at least 10 characters");

            if(strlen($data['description']) < 50)
                return $this->jsonError("Please provide a detailed description of the form for better generation");

            $formData = OpenaiUtil::formGenerator($data['title'], $data['description']);
            if(!$formData) return $this->jsonError("An unknown error occured, failed to generate form");

            $form = Form::create([
                'title' => $data['title'],
                'slug' => slugify($data['title']) . '-' . uniqid(),
                'description' => $data['title'],
                'user_id' => auth()->id(),
                'content' => $formData,
                'start_date' => now()->addDays(7),
                'end_date' => now()->addDays(14)
            ]);

            $this->redirect = route('forms.setup', $form->id, $form->slug);
            if($form) return $this->jsonSuccess("Form generated successfully", ['form' => $form]);

            return $this->jsonError("An unknown error occured, failed to generate form");
        }

        catch(\Exception $e){
            return $this->jsonException($e);
        }        
    }

    
    # check form ownership
    public function formOwnerShipCheck($id) : bool
    {
        $form = Form::find($id);
        if(!$form) return false;

        return ($form->user_id == auth()->id()) || in_array((string) auth()->id(), $form->collaborators);
    }

    # check ownership by space
    public function hasAccessbySpace(array $spaces) : bool
    {
        $spaces = Space::whereIn('id', $spaces)
            ->where('user_id', auth()->id())
            ->orWhereJsonContains('members', (string) auth()->id())
            ->get();

        if($spaces->count()) return true;

        return false;
    }

    public static function routes()
    {
        app()::get('', ['name'=>'forms.list', 'FormsController@index']);
        app()::get('build', ['name'=>'forms.build', 'FormsController@build']);
        app()::get('search', ['name'=>'forms.search', 'FormsController@search']);

        app()::get('delete/{id}', ['name'=>'forms.delete', 'FormsController@delete']);
        app()::get('preview/{id}', ['name'=>'forms.preview', 'FormsController@preview']);
        app()::get('setup/{id}/{slug}', ['name'=>'forms.setup', 'FormsController@setup']);
        app()::get('customize/{id}/{slug}', ['name'=>'forms.customize', 'FormsController@customize']);
        
        app()::post('edit/{id}', ['name'=>'forms.update', 'FormsController@update']);
        app()::post('generate', ['name'=>'forms.generate', 'FormsController@generate']);
        app()::post('setup/{setting}/{id}', ['name'=>'forms.setup.update', 'FormsController@setting']);

        app()::get('template/{id}', ['name'=>'forms.template', 'FormsController@template']);
    }
}