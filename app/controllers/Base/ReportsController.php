<?php

namespace App\Controllers\Base;
use App\Controllers\Controller;
use App\Controllers\Base\FormsController;

use App\Models\Form;
use App\Models\Report;
use App\Models\User;
use App\Models\Collection;

class ReportsController extends Controller
{
    protected $formInstance;

    public function __construct()
    {
        parent::__construct();
        $this->formInstance = new FormsController();
    }

    public function index($form)
    {
        $this->form = Form::find($form);
        if(!$this->form) return $this->errorPage();

        # access check
        if(
            !$this->formInstance->formOwnerShipCheck($this->form->id) &&
            !$this->formInstance->hasAccessbySpace($this->form->spaces)
        ){
            return $this->errorPage(403);
        }

        # data allocation
        $this->reports = Report::forForm($this->form->id);
        $this->users = User::where('id', '!=', auth()->id())->get();
        
        return $this->renderPage(
            "Reports: {$this->form->title}",
            "app.reports.index"
        );
    }

    public function store()
    {
        try {
            # request validation
            $data = [
                'title' => request()->params('title'),
                'description' => request()->params('description'),
                'form_id' => request()->params('form_id'),
                'user_id' => auth()->id()
            ];

            if(
                in_array('', $data) ||
                strlen($data['title']) < 3 ||
                strlen($data['description']) < 10
            ){
                return $this->jsonError("Name and description must be at least 3 and 10 characters, respectively");
            }

            if(request()->params('collaborators')){
                $data['collaborators'] = request()->params('collaborators');
            }

            # data and access check
            $form = Form::find($data['form_id']);
            if(!$form) return $this->jsonError("Form not found", 404);

            if(
                !$this->formInstance->formOwnerShipCheck($form->id) &&
                !$this->formInstance->hasAccessbySpace($form->spaces)
            ){
                return $this->jsonError("You do not have permission to create reports for this form", 403);
            }

            # create report
            $report = Report::create($data);
            if(!$report) return $this->jsonError("Failed to create report");

            $this->redirect = route('reports.show', $report->id);
            return $this->jsonSuccess("Report created successfully");            
        }

        catch (\Exception $e) {
            return $this->jsonException($e);
        }
    }

    public function show($report)
    {
        $this->report = Report::find($report);
        if(!$this->report) return $this->errorPage(404);

        # access check
        if(
            !$this->formInstance->formOwnerShipCheck($this->report->form->id) &&
            !$this->formInstance->hasAccessbySpace($this->report->form->spaces)
        ){
            return $this->errorPage(403);
        }

        # data allocation
        $this->accessRole = $this->reportAccessRole($this->report);
        if(request()->params('preview') === 'true' && $this->accessRole !== 'viewer'):
            $this->reportContent = $this->report->draft;
            else: $this->reportContent = $this->report->content;
        endif;

        $filters = is_array($this->report->filters ?? []) || count($this->report->filters) 
            ? $this->report->filters : null;

        $this->accessRole = $this->reportAccessRole($this->report);            
        $this->collections = Collection::formCollections(
                $this->report->form->id, false, $filters
            )->pluck('submission')->toArray();
        
        return $this->renderPage(
            "Report: {$this->report->title}",
            "app.reports.show"
        );
    }

    public function edit($report)
    {
        $this->report = Report::find($report);
        if(!$this->report) return $this->errorPage(404);

        # access check
        $this->form = $this->report->form;
        if(
            !$this->formInstance->formOwnerShipCheck($this->form->id) &&
            !$this->formInstance->hasAccessbySpace($this->form->spaces)
        ){
            return $this->errorPage(403);
        }

        $this->accessRole = $this->reportAccessRole($this->report);
        if($this->accessRole === 'viewer'){
            return $this->errorPage(403);
        }
        
        return $this->renderPage(
            "Edit Report: {$this->report->title}",
            "app.reports.edit"
        );
    }

    public function build($id)
    {
        $report = Report::find($id);
        if(!$report) return $this->jsonError("Report not found");

        # access check
        $accessRole = $this->reportAccessRole($report);
        if(
            (!$this->formInstance->formOwnerShipCheck($report->form->id) &&
            !$this->formInstance->hasAccessbySpace($report->form->spaces)) ||
            $this->accessRole === 'viewer'
        ){
            return $this->jsonError("You do not have access to edit this report", 403);
        }

        try {
            # request validation
            $data = [
                'content' => json_decode($_REQUEST['content'], true) ?? null,
                'filters' => json_decode($_REQUEST['filters'], true) ?? null,
            ];

            if(in_array('', $data))
                return $this->jsonSuccess("No data to update");

            if(!request()->params('publish')){
                $data['draft'] = $data['content'];
                unset($data['content']);
            }

            # update report
            $report->update($data);
            return $this->jsonSuccess("Report updated successfully");
        }

        catch (\Exception $e) {
            return $this->jsonException($e);
        }
    }

    public function update($report)
    {
        return $this->jsonSuccess("This feature is not implemented yet");
    }

    public function reportAccessRole($report)
    {
        if(auth()->user()->role === 'admin'){
            return 'owner';
        }

        // merge unique collaborators from both report and form
        $collaborators = array_unique(array_merge(
            $report->collaborators ?? [],
            $report->form->collaborators ?? []
        ));

        if($report->form->user_id == auth()->id()  || $report->user_id == auth()->id()){
            return 'owner';
        }elseif(in_array(auth()->id(), $collaborators)){
            return 'collaborator';
        }
        
        return 'viewer';
    }

    public static function routes()
    {
        app()::group('reports', function() {
            app()::get('all/{form}', ['name'=>'reports.list', 'ReportsController@index']);
            app()::get('show/{report}', ['name'=>'reports.show', 'ReportsController@show']);
            app()::get('edit/{report}', ['name'=>'reports.edit', 'ReportsController@edit']);

            app()::post('store', ['name'=>'reports.store', 'ReportsController@store']);
            app()::post('build/{report}', ['name'=>'reports.build', 'ReportsController@build']);
            app()::post('update/{report}', ['name'=>'reports.update', 'ReportsController@update']);
            app()::post('delete/{report}', ['name'=>'reports.delete', 'ReportsController@delete']);
        });
    }
}