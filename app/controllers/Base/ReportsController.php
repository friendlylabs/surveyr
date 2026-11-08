<?php

namespace App\Controllers\Base;
use App\Controllers\Controller;

use App\Models\Form;
use App\Models\Report;
use App\Models\User;
use App\Models\Collection;
use App\Models\ReportLink;
use App\Services\ReportSchema;

/**
 * Reports are a top-level module: a report draws its visuals from any
 * number of source forms (ids in the `forms` JSON column) and is shared through its
 * own collaborators or through access to any of those forms.
 */
class ReportsController extends Controller
{
    /** Form ids the signed-in user may work with, resolved once per request. */
    private ?array $accessibleFormIds = null;

    public function index(?int $form = null)
    {
        $userId = auth()->id();

        $query = Report::accessibleBy($userId)
            ->with('link')
            ->orderBy('updated_at', 'desc');

        if($form){
            $this->form = Form::find($form);
            if(!$this->form) return $this->errorPage(404);
            if(!in_array((int) $form, $this->accessibleFormIds(), true)) return $this->errorPage(403);

            $query->forForm($form);
        }

        $this->reports = $query->get()->each(function ($report) {
            $report->sections_count = count(ReportSchema::normalize($report->content, $report->primaryFormId())['sections']);
        });
        Report::loadSourceForms($this->reports, ['id', 'title', 'slug', 'user_id']);

        $this->forms = Form::accessibleQuery($userId)->orderBy('title')->get(['id', 'title']);
        $this->users = User::where('id', '!=', $userId)->get();

        return $this->renderPage(
            $this->form ? "Reports: {$this->form->title}" : "Reports",
            "app.reports.index"
        );
    }

    public function store()
    {
        try {
            $data = [
                'title'       => trim((string) request()->params('title')),
                'description' => trim((string) request()->params('description')),
                'user_id'     => auth()->id(),
            ];

            if(strlen($data['title']) < 3 || strlen($data['description']) < 10){
                return $this->jsonError("Name and description must be at least 3 and 10 characters, respectively");
            }

            $formIds = $this->formIdsFromRequest(request()->params('forms'));
            if(!$formIds) return $this->jsonError("Pick at least one source form");

            $denied = array_diff($formIds, $this->accessibleFormIds());
            if($denied) return $this->jsonError("You do not have access to one of the selected forms", 403);

            if(request()->params('collaborators')){
                $data['collaborators'] = array_values(array_map('strval', (array) request()->params('collaborators')));
            }

            $data['forms'] = $formIds;

            $report = Report::create($data);
            if(!$report) return $this->jsonError("Failed to create report");

            $this->redirect = route('reports.edit', $report->id);
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

        $this->accessRole = $this->reportAccessRole($this->report);
        if($this->accessRole === 'none') return $this->errorPage(403);

        $this->isPreview = request()->params('preview') === 'true' && $this->accessRole !== 'viewer';

        $this->reportContent = ReportSchema::normalize(
            $this->isPreview ? $this->report->draft : $this->report->content,
            $this->report->primaryFormId()
        );
        $this->svql = $this->report->svql();
        $this->forms = $this->bundlesForDocument($this->report, $this->reportContent, $this->svql);

        return $this->renderPage(
            "Report: {$this->report->title}",
            "app.reports.show"
        );
    }

    public function edit(int $report)
    {
        $this->report = Report::find($report);
        if(!$this->report) return $this->errorPage(404);

        $this->accessRole = $this->reportAccessRole($this->report);
        if(!in_array($this->accessRole, ['owner', 'collaborator'], true)) return $this->errorPage(403);

        # the draft is what gets edited, seeded from the published copy when nothing has been drafted yet
        $published = ReportSchema::normalize($this->report->content, $this->report->primaryFormId());
        $draft     = ReportSchema::normalize($this->report->draft, $this->report->primaryFormId());
        $formIds   = $this->report->formIds();

        $this->reportDoc = $draft['sections'] ? $draft : $published;
        $this->isPublished = count($published['sections']) > 0
            && ReportSchema::fingerprint($this->reportDoc) === ReportSchema::fingerprint($published);

        $this->filters = ReportSchema::normalizeFilters($this->report->filters, $formIds, $this->report->primaryFormId());
        $this->svql = $this->report->svql();
        $this->forms = $this->report->sourceForms()->map(fn ($form) => $this->formBundle($form, $this->filters[(string) $form->id] ?? []))->values()->all();

        $this->availableForms = Form::accessibleQuery(auth()->id())
            ->whereNotIn('id', $formIds ?: [0])
            ->orderByDesc('updated_at')
            ->get(['id', 'title', 'updated_at'])
            ->map(fn ($f) => ['id' => (int) $f->id, 'title' => $f->title, 'updated' => $f->updated_at?->toIso8601String()])
            ->values()->all();

        return $this->renderPage(
            "Edit Report",
            "app.reports.edit"
        );
    }

    public function public(string $hash)
    {
        $reportLink = ReportLink::where('hash', $hash)->first();
        if(!$reportLink) return $this->errorPage(404);

        $this->report = $reportLink->report;
        if(!$this->report) return $this->errorPage(404);

        $this->reportContent = ReportSchema::normalize($this->report->content, $this->report->primaryFormId());
        $this->svql = $this->report->svql();
        $this->forms = $this->bundlesForDocument($this->report, $this->reportContent, $this->svql);

        return $this->renderPage(
            "Show Report",
            "app.reports.public"
        );
    }

    /* ---------------------------------------------------------------------- *
     |  Editor endpoints                                                      |
     * ---------------------------------------------------------------------- */

    /**
     * Save the draft (and optionally publish it). Body: { content, filters, forms?, svql?, publish }.
     * SurveyQL settings live in their own column, so they apply to draft and published copies alike.
     */
    public function build(int $id)
    {
        $report = Report::find($id);
        if(!$report) return $this->jsonError("Report not found", 404);

        if(!$this->canEdit($report)){
            return $this->jsonError("You do not have access to edit this report", 403);
        }

        try {
            $content = $this->jsonParam('content');
            $filters = $this->jsonParam('filters');
            $forms   = $this->jsonParam('forms');
            $svql    = $this->jsonParam('svql');

            if($content === null){
                return $this->jsonError("No report content received");
            }

            # an ordered list of form ids re-syncs the sources first
            if(is_array($forms)){
                $formIds = $this->formIdsFromRequest($forms);
                if(!$formIds) return $this->jsonError("A report needs at least one source form", 422);
                $current = $report->formIds();

                $added = array_diff($formIds, $current);
                if(array_diff($added, $this->accessibleFormIds())){
                    return $this->jsonError("You do not have access to one of the forms", 403);
                }

                $doc = ReportSchema::normalize($content, $report->primaryFormId());
                $stillUsed = array_intersect(array_diff($current, $formIds), ReportSchema::formsUsed($doc));
                if($stillUsed){
                    return $this->jsonError("Remove the visuals from a form before detaching it", 422);
                }

                $queried = ReportSchema::svqlForms(ReportSchema::normalizeSvql(is_array($svql) ? $svql : $report->svql_expressions, $current));
                if(array_intersect(array_diff($current, $formIds), $queried)){
                    return $this->jsonError("Remove the query sources that read a form before detaching it", 422);
                }

                $report->setFormIds($formIds); // persisted with the draft below
            }

            $questionNames = [];
            foreach ($report->sourceForms() as $form) {
                $questionNames[(int) $form->id] = ReportSchema::questionNames($form->content);
            }

            $doc = ReportSchema::sanitize($content, $questionNames, $report->primaryFormId());

            $data = ['draft' => $doc];
            if(is_array($svql)){
                $data['svql_expressions'] = ReportSchema::normalizeSvql($svql, $report->formIds());
            }
            if(is_array($filters)){
                $data['filters'] = ReportSchema::normalizeFilters($filters, $report->formIds(), $report->primaryFormId());
            }

            # publishing promotes the draft to the public copy
            $publish = filter_var(request()->get('publish'), FILTER_VALIDATE_BOOLEAN);
            if($publish) $data['content'] = $doc;

            $report->update($data);

            $this->published = $publish;
            return $this->jsonSuccess($publish ? "Report published" : "Draft saved");
        }
        catch (\Exception $e) {
            return $this->jsonException($e);
        }
    }

    /**
     * Submissions per attached form under a given filter set, used by the
     * editor to refresh its charts. Body: { filters: { formId: {…} } }.
     */
    public function data(int $report)
    {
        $report = Report::find($report);
        if(!$report) return $this->jsonError("Report not found", 404);

        if(!$this->canEdit($report)){
            return $this->jsonError("You do not have access to this report", 403);
        }

        try {
            $filters = ReportSchema::normalizeFilters($this->jsonParam('filters'), $report->formIds(), $report->primaryFormId());

            $collections = [];
            foreach ($report->sourceForms() as $form) {
                $collections[(string) $form->id] = $this->formRows($form, $filters[(string) $form->id] ?? []);
            }

            $this->collections = $collections ?: new \stdClass;
            return $this->jsonSuccess("Data loaded");
        }
        catch (\Exception $e) {
            return $this->jsonException($e);
        }
    }

    /**
     * Attach or detach source forms. Body: { add: formId | formId[] } | { remove: formId }.
     * Adding returns the forms' bundles so the editor can use them without a reload.
     */
    public function forms(int $report)
    {
        $report = Report::find($report);
        if(!$report) return $this->jsonError("Report not found", 404);

        if(!$this->canEdit($report)){
            return $this->jsonError("You do not have access to this report", 403);
        }

        try {
            $add    = array_values(array_unique(array_filter(array_map('intval', (array) request()->get('add')))));
            $remove = (int) request()->get('remove');
            $current = $report->formIds();

            if($add){
                if(array_diff($add, $this->accessibleFormIds())){
                    return $this->jsonError("You do not have access to one of those forms", 403);
                }

                $forms = Form::whereIn('id', $add)->get();
                if($forms->count() !== count($add)) return $this->jsonError("Form not found", 404);

                $new = array_values(array_diff($add, $current));
                if($new){
                    $report->setFormIds([...$current, ...$new]);
                    $report->save();
                }

                $this->forms = $forms->sortBy(fn ($form) => array_search((int) $form->id, $add, true))->map(fn ($form) => $this->formBundle($form, []))->values()->all();
                return $this->jsonSuccess(count($add) === 1 ? "Form added" : "Forms added");
            }

            if($remove){
                $doc = ReportSchema::normalize($report->draft ?: $report->content, $report->primaryFormId());
                if(in_array($remove, ReportSchema::formsUsed($doc), true)){
                    return $this->jsonError("Remove the visuals from this form before detaching it", 422);
                }
                if(in_array($remove, ReportSchema::svqlForms($report->svql()), true)){
                    return $this->jsonError("Remove the query sources that read this form before detaching it", 422);
                }

                $report->setFormIds(array_diff($current, [$remove]));
                $report->save();

                return $this->jsonSuccess("Form removed");
            }

            return $this->jsonError("Nothing to do");
        }
        catch (\Exception $e) {
            return $this->jsonException($e);
        }
    }

    /** Report details (title, description); the document itself goes through build(). */
    public function update(int $report)
    {
        $report = Report::find($report);
        if(!$report) return $this->jsonError("Report not found", 404);

        if(!$this->canEdit($report)){
            return $this->jsonError("You do not have access to this report", 403);
        }

        try {
            $title       = request()->get('title');
            $description = request()->get('description');

            if($title === null && $description === null) return $this->jsonError("Nothing to update");

            if($title !== null){
                $title = trim((string) $title);
                if(strlen($title) < 3 || strlen($title) > 255) return $this->jsonError("Name must be between 3 and 255 characters");
                $report->title = $title;
            }

            if($description !== null){
                $description = trim((string) $description);
                if(strlen($description) < 10) return $this->jsonError("Description must be at least 10 characters");
                $report->description = $description;
            }

            if(!$report->save()) return $this->jsonError("Failed to update report");

            return $this->jsonSuccess("Report updated");
        }
        catch (\Exception $e) {
            return $this->jsonException($e);
        }
    }

    /** Same GET-and-redirect convention as forms.delete, which confirmDelete() in app.js relies on. */
    public function delete(int $report)
    {
        $report = Report::find($report);
        if(!$report) return $this->errorPage(404);

        if($this->reportAccessRole($report) !== 'owner'){
            return $this->errorPage(403);
        }

        $report->link()->delete();
        $report->delete();

        return redirect(route('reports.list'));
    }

    /* ---------------------------------------------------------------------- *
     |  Sharing                                                               |
     * ---------------------------------------------------------------------- */

    public function generateLink(int $report)
    {
        try {
            $report = Report::find($report);
            if(!$report) return $this->jsonError("Report not found", 404);

            if(!$this->canEdit($report)){
                return $this->jsonError("You do not have permission to generate links for this report", 403);
            }

            $existingLink = ReportLink::where('report_id', $report->id)->first();
            if($existingLink){
                $this->hash = $existingLink->hash;
                $this->url = request()->getUrl() . route('reports.public', $existingLink->hash);
                return $this->jsonSuccess("Link already exists");
            }

            $reportLink = ReportLink::create(['report_id' => $report->id]);
            if(!$reportLink) return $this->jsonError("Failed to generate link");

            $this->hash = $reportLink->hash;
            $this->url = request()->getUrl() . route('reports.public', $reportLink->hash);
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

            if(!$this->canEdit($report)){
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

    /* ---------------------------------------------------------------------- *
     |  Access                                                                |
     * ---------------------------------------------------------------------- */

    /**
     * owner        admin, the report's author, or the owner of any source form
     * collaborator listed on the report or on any source form
     * viewer       reaches a source form through a space
     * none         no relation at all
     */
    public function reportAccessRole(Report $report): string
    {
        $user = auth()->user();
        $userId = (int) auth()->id();

        if($user->role === 'admin' || (int) $report->user_id === $userId) return 'owner';

        if(in_array((string) $userId, array_map('strval', $report->collaborators ?? []), true)) return 'collaborator';

        $role = 'none';
        foreach ($report->sourceForms() as $form) {
            if((int) $form->user_id === $userId) return 'owner';
            if(in_array((string) $userId, array_map('strval', $form->collaborators ?? []), true)) $role = 'collaborator';
            elseif($role === 'none' && in_array((int) $form->id, $this->accessibleFormIds(), true)) $role = 'viewer';
        }

        return $role;
    }

    protected function canEdit(Report $report): bool
    {
        return in_array($this->reportAccessRole($report), ['owner', 'collaborator'], true);
    }

    protected function accessibleFormIds(): array
    {
        return $this->accessibleFormIds ??= Form::accessibleIds((int) auth()->id());
    }

    /* ---------------------------------------------------------------------- *
     |  Data                                                                  |
     * ---------------------------------------------------------------------- */

    /** Everything the front end needs to chart one form. */
    protected function formBundle(Form $form, array $filters, ?array $onlyQuestions = null): array
    {
        return [
            'id'      => (int) $form->id,
            'title'   => $form->title,
            'slug'    => $form->slug,
            'updated' => $form->updated_at?->toIso8601String(),
            'survey'  => $form->content,
            'rules'   => $form->viz_rules ?: new \stdClass,
            'flags'   => $form->flagGroup?->flags ?? [],
            'data'    => $this->formRows($form, $filters, $onlyQuestions),
        ];
    }

    /** Submissions of a form, optionally projected to the answers a report needs. */
    protected function formRows(Form $form, array $filters, ?array $onlyQuestions = null): array
    {
        $rows = Collection::formCollections($form->id, true, $filters ?: null)->toArray();
        if($onlyQuestions === null) return $rows;

        $keep = array_flip(array_merge($onlyQuestions, ['id', 'flag', 'created_at']));
        return array_map(fn ($row) => array_intersect_key($row, $keep), $rows);
    }

    /**
     * Bundles for the viewer: only forms the document actually references,
     * with rows trimmed to the questions its visuals (and their groupings) read.
     * Forms read by SurveyQL sources keep whole rows, since a query may touch any question.
     */
    protected function bundlesForDocument(Report $report, array $doc, array $svql = []): array
    {
        $used = ReportSchema::questionsUsed($doc);
        $filters = ReportSchema::normalizeFilters($report->filters, $report->formIds(), $report->primaryFormId());
        $queried = ReportSchema::svqlForms($svql);

        $bundles = [];
        foreach ($report->sourceForms() as $form) {
            if(in_array((int) $form->id, $queried, true)){
                $bundles[] = $this->formBundle($form, $filters[(string) $form->id] ?? []);
                continue;
            }
            if(!isset($used[(int) $form->id])) continue;

            $questions = $used[(int) $form->id];
            foreach ((array) ($form->viz_rules ?? []) as $name => $rule) {
                if(!in_array($name, $questions, true) || !is_array($rule)) continue;
                if(!empty($rule['bind_label_to'])) $questions[] = $rule['bind_label_to'];
                foreach ((array) ($rule['series'] ?? []) as $series) $questions[] = $series;
            }

            $bundles[] = $this->formBundle($form, $filters[(string) $form->id] ?? [], array_values(array_unique($questions)));
        }

        return $bundles;
    }

    /* ---------------------------------------------------------------------- *
     |  Helpers                                                               |
     * ---------------------------------------------------------------------- */

    /** A request value that may arrive as a JSON string (form post) or already decoded (JSON body). */
    protected function jsonParam(string $key): mixed
    {
        $value = request()->get($key);
        if(is_string($value)){
            $decoded = json_decode($value, true);
            return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
        }
        return $value;
    }

    /** Distinct positive ints from a posted list. */
    protected function formIdsFromRequest(mixed $value): array
    {
        if(is_string($value)) $value = json_decode($value, true) ?: explode(',', $value);
        if(!is_array($value)) return [];

        $ids = array_values(array_unique(array_filter(array_map('intval', $value), fn ($id) => $id > 0)));
        return $ids;
    }

    public static function routes()
    {
        app()::get('', ['name'=>'reports.list', 'ReportsController@index']);
        app()::get('form/{form}', ['name'=>'reports.forForm', 'ReportsController@index']);
        app()::get('show/{report}', ['name'=>'reports.show', 'ReportsController@show']);
        app()::get('edit/{report}', ['name'=>'reports.edit', 'ReportsController@edit']);
        app()::get('delete/{report}', ['name'=>'reports.delete', 'ReportsController@delete']);

        app()::post('store', ['name'=>'reports.store', 'ReportsController@store']);
        app()::post('build/{report}', ['name'=>'reports.build', 'ReportsController@build']);
        app()::post('data/{report}', ['name'=>'reports.data', 'ReportsController@data']);
        app()::post('forms/{report}', ['name'=>'reports.forms', 'ReportsController@forms']);
        app()::post('update/{report}', ['name'=>'reports.update', 'ReportsController@update']);
        app()::post('generate-link/{report}', ['name'=>'reports.generateLink', 'ReportsController@generateLink']);
        app()::post('delete-link/{report}', ['name'=>'reports.deleteLink', 'ReportsController@deleteLink']);
    }
}
