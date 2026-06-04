<?php

namespace App\Controllers\Base;
use App\Controllers\Controller;
use App\Controllers\Base\FormsController;

use App\Models\Form;
use App\Models\Report;
use App\Models\User;
use App\Models\Collection;
use App\Models\ReportLink;

class ReportsController extends Controller
{
    protected FormsController $formInstance;

    public function __construct()
    {
        parent::__construct();
        $this->formInstance = new FormsController();
    }

    public function index(int $form)
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
        $this->reports = Report::with('link')->where('form_id', $this->form->id)->get();
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

    public function show(int $report)
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
                $this->report->form->id, true, $filters
            )->toArray();
        
        return $this->renderPage(
            "Report: {$this->report->title}",
            "app.reports.show"
        );
    }

    public function edit(int $report)
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

    public function build(int $id)
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

    public function public(string $hash)
    {
        $reportLink = ReportLink::where('hash', $hash)->first();
        if(!$reportLink) return $this->errorPage(404);

        $this->report = $reportLink->report;
        if(!$this->report) return $this->errorPage(404);

        # data allocation
        $this->reportContent = $this->report->content;
        
        $filters = is_array($this->report->filters ?? []) || count($this->report->filters) 
            ? $this->report->filters : null;

        $this->collections = Collection::formCollections(
                $this->report->form->id, true, $filters
            )->toArray();
        
        return $this->renderPage(
            "Report: {$this->report->title}",
            "app.reports.public"
        );
    }

    public function update(int $report)
    {
        return $this->jsonSuccess("This feature is not implemented yet");
    }

    public function generateLink(int $report)
    {
        try {
            $report = Report::find($report);
            if(!$report) return $this->jsonError("Report not found", 404);

            # access check
            $accessRole = $this->reportAccessRole($report);
            if($accessRole === 'viewer'){
                return $this->jsonError("You do not have permission to generate links for this report", 403);
            }

            # check if link already exists
            $existingLink = ReportLink::where('report_id', $report->id)->first();
            if($existingLink){
                $this->hash = $existingLink->hash;
                $this->url = request()->getUrl() . route('reports.public', $existingLink->hash);
                return $this->jsonSuccess("Link already exists");
            }

            # create new link
            $reportLink = ReportLink::create(['report_id' => $report->id]);
            if(!$reportLink) return $this->jsonError("Failed to generate link");

            $this->hash = $reportLink->hash;
            $this->url = request()->getUrl() . route('reports.public', $reportLink->hash);
            route('reports.public', $reportLink->hash);
            return $this->jsonSuccess("Link generated successfully");
        }
        catch (\Exception $e) {
            return $this->jsonException($e);
        }
    }

    public function deleteLink(int $report)
    {
        try {
            $report = Report::find($report);
            if(!$report) return $this->jsonError("Report not found", 404);

            # access check
            $accessRole = $this->reportAccessRole($report);
            if($accessRole === 'viewer'){
                return $this->jsonError("You do not have permission to delete links for this report", 403);
            }

            $reportLink = ReportLink::where('report_id', $report->id)->first();
            if(!$reportLink) return $this->jsonError("No link found for this report", 404);

            $reportLink->delete();
            return $this->jsonSuccess("Link deleted successfully");
        }
        catch (\Exception $e) {
            return $this->jsonException($e);
        }
    }

    public function reportAccessRole(Report $report)
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
            app()::post('generate-link/{report}', ['name'=>'reports.generateLink', 'ReportsController@generateLink']);
            app()::post('delete-link/{report}', ['name'=>'reports.deleteLink', 'ReportsController@deleteLink']);
        });
    }
}