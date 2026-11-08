<?php

namespace App\Controllers\Base;

use App\Controllers\Controller;
use Leaf\Database as DB;

use App\Models\Form;
use App\Models\User;
use App\Models\Space;
use App\Models\Theme;
use App\Models\Template;
use App\Models\Collection;
use App\Models\FlagGroup;
use App\Models\CollectionFilter;

use App\Services\CopilotEditor;
use App\Services\ReportSchema;
use App\Utils\DocumentExtractor;

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
        try{
            $data = [
                'title' => request()->params('title'),
                'slug' => slugify(request()->params('title')) . '-' . uniqid(),
                'description' => request()->params('description'),
                'user_id' => auth()->id(),
                // default schedule; the owner adjusts it on the setup page
                'start_date' => now(),
                'end_date' => now()->addDays(7)
            ];

            if(
                in_array('', $data) ||
                strlen($data['title']) < 3 ||
                strlen($data['description']) < 10
            ){
                return $this->jsonError("Name and description must be at least 3 and 10 characters, respectively");
            }
            
            $data['content'] = [
                'title' => $data['title'],
                'description' => $data['description']
            ];

            $form = Form::create($data);
            if(!$form) return $this->jsonError("An unknown error occured, failed to create form");

            $this->redirect = route('forms.setup', $form->id, $form->slug);
            return $this->jsonSuccess("Form created successfully");
        }

        catch(\Exception $e){
            return $this->jsonException($e);
        }
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
        $this->flagGroups = FlagGroup::orderBy('name')->get();
        $this->spaces = Space::userSpaces(auth()->id());
        $this->users = User::where('id', '!=', auth()->id())->get();

        $tab = request()->params('tab');
        $this->tab = in_array($tab, ['overview', 'submissions', 'settings', 'theme'], true) ? $tab : 'overview';

        $this->canEdit = $this->formOwnerShipCheck($id);   // customize is owner / collaborator only
        $this->overview = $this->overviewStats($form);
        $this->savedFilters = CollectionFilter::ofForm((int) $form->id, true);

        $this->form = $form;
        return $this->renderPage("Form Overview", "app.forms.setup");
    }

    /**
     * What the overview tab shows. Only submission dates and flags are
     * recorded, so everything here is derived from `collections`.
     */
    protected function overviewStats(Form $form) : array
    {
        $base = fn () => Collection::where('form_id', $form->id);
        $since = fn (int $days) => now()->subDays($days)->startOfDay();

        $total = $base()->count();
        $last7 = $base()->where('created_at', '>=', $since(6))->count();
        $prev7 = $base()->whereBetween('created_at', [$since(13), $since(6)])->count();

        // { 'YYYY-MM-DD': n } for the last 30 days; the chart fills the gaps
        $series = $base()->where('created_at', '>=', $since(29))
            ->select(DB::$capsule::raw('DATE(created_at) as day'), DB::$capsule::raw('COUNT(id) as n'))
            ->groupBy('day')->pluck('n', 'day')->map(fn ($n) => (int) $n)->all();

        $flags = null;
        if ($group = $form->flagGroup) {
            $counts = $base()->whereNotNull('flag')->select('flag', DB::$capsule::raw('COUNT(id) as n'))
                ->groupBy('flag')->pluck('n', 'flag')->all();

            $flags = [
                'items' => array_map(fn ($flag) => $flag + ['count' => (int) ($counts[$flag['key']] ?? 0)], $group->flags ?? []),
                'unflagged' => $base()->whereNull('flag')->count(),
                'oldestUnflagged' => $base()->whereNull('flag')->min('created_at'),
            ];
        }

        $last = $base()->max('created_at');

        return [
            'total' => $total,
            'last7' => $last7,
            'prev7' => $prev7,
            'lastAt' => $last ? \Carbon\Carbon::parse($last) : null,
            'series' => $series,
            'flags' => $flags,
        ];
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

        // the view warns the user before editing a form that already has submissions
        $this->submissions = Collection::where('form_id', $id)->exists();

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

        $flagGroupId = request()->params('flag_group_id');
        $flagGroupId = $flagGroupId ? (int) $flagGroupId : null;
        if($flagGroupId && !FlagGroup::find($flagGroupId))
            return $this->jsonError("Flag group not found");

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

        // another group's keys mean nothing here, so switching groups clears the old flags
        if((int) $form->flag_group_id !== (int) $flagGroupId)
            Collection::where('form_id', $form->id)->update(['flag' => null]);

        $form->flag_group_id = $flagGroupId;
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
        $this->forms = Form::searchUserForms(auth()->id(), $search);

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
        $form = Form::publicForm($hash);
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

    # register Form Rules
    public function rules(int $id){
        try{

            $data = json_decode(file_get_contents('php://input'), true);
            if(json_last_error() !== JSON_ERROR_NONE || !is_array($data))
                return $this->jsonError("Request body must be valid JSON", 400);

            $form = Form::find($id);
            if(!$form) return $this->jsonError("Form not found");

            if(!$this->formOwnerShipCheck($id) && !$this->hasAccessbySpace($form->spaces))
                return $this->jsonError("You do not have permission to update this form");

            $rules = $data['viz_rules'] ?? null;
            if(!is_array($rules)) return $this->jsonError("Invalid rules format");

            $form->viz_rules = $this->sanitizeVizRules($rules, ReportSchema::questionNames($form->content));
            if($form->save()){
                return $this->jsonSuccess("Form visualization rules updated successfully");
            }

            return $this->jsonError("An unknown error occured, failed to update form visualization rules");
        }

        catch(\Exception $e){
            return $this->jsonException($e);
        }
    }

    /**
     * Server-side mirror of VizEngine.normalizeRule: keeps only rules for questions
     * that exist in the form, only the known fields, and only values from the same
     * vocabulary the rules editor offers. Anything else is dropped rather than stored.
     */
    protected function sanitizeVizRules(array $rules, array $questionNames): array
    {
        $charts = [
            'number'     => ['gauge', 'bullet'],
            'histogram'  => ['vbar', 'bar'],
            'selectBase' => ['bar', 'vbar', 'pie', 'doughnut'],
            'choices'    => [],
            'wordcloud'  => [],
            'text'       => [],
            'boolean'    => ['pie', 'bar', 'doughnut'],
            'matrix'     => ['bar', 'stackedbar', 'pie', 'doughnut'],
            'bound'      => ['bar', 'vbar', 'line', 'pie', 'doughnut'],
        ];
        $aggregates = ['sum', 'avg', 'count', 'min', 'max'];
        $sorts      = ['value_desc', 'value_asc', 'label', 'none'];
        $known      = array_flip($questionNames);

        $clean = [];
        foreach ($rules as $name => $rule) {
            if(!is_string($name) || !isset($known[$name]) || !is_array($rule)) continue;

            // visualizers SurveyJS registers but VizEngine doesn't list still render, without a chart choice
            $visualizer = is_string($rule['visualizer'] ?? null) && preg_match('/^[A-Za-z][A-Za-z0-9_]{0,39}$/', $rule['visualizer'])
                ? $rule['visualizer'] : null;
            $allowedCharts = $charts[$visualizer] ?? [];
            $chartType = in_array($rule['chart_type'] ?? null, $allowedCharts, true) ? $rule['chart_type'] : ($allowedCharts[0] ?? null);

            $label = $rule['bind_label_to'] ?? null;
            $label = is_string($label) && isset($known[$label]) && $label !== $name ? $label : null;

            $series = array_values(array_unique(array_filter(
                is_array($rule['series'] ?? null) ? $rule['series'] : [],
                fn ($s) => is_string($s) && isset($known[$s]) && $s !== $name
            )));

            $limit = filter_var($rule['limit'] ?? 0, FILTER_VALIDATE_INT);

            $clean[$name] = [
                'visible'       => ($rule['visible'] ?? true) !== false,
                'visualizer'    => $visualizer,
                'chart_type'    => $chartType,
                'bind_label_to' => $label,
                'series'        => $series,
                'aggregate'     => in_array($rule['aggregate'] ?? null, $aggregates, true) ? $rule['aggregate'] : 'sum',
                'sort'          => in_array($rule['sort'] ?? null, $sorts, true) ? $rule['sort'] : 'value_desc',
                'limit'         => $limit !== false && $limit > 0 ? $limit : 0,
            ];
        }

        return $clean;
    }

    /**
     * Copilot chat turn. Receives the in-memory survey JSON, the user's
     * instruction and the earlier turns of the same conversation, runs the
     * OpenAI tool-calling loop (CopilotEditor) and returns the list of
     * operations for the client to apply via the SurveyJS Creator API.
     *
     * Reads the raw JSON body directly: the survey carries HTML and
     * expressions that request() sanitising would mangle.
     *
     * @return void
     */
    public function copilot()
    {
        try {
            $body = json_decode(file_get_contents('php://input'), true);
            if (!is_array($body))
                return $this->jsonError("Invalid request body");

            // A brand-new form has no pages yet — that's a valid starting point
            $document = $body['document'] ?? null;
            if (is_string($document)) $document = json_decode($document, true);
            if (!is_array($document))
                return $this->jsonError("Invalid form schema");
            if (!is_array($document['pages'] ?? null)) $document['pages'] = [];

            $instruction = trim((string) ($body['instruction'] ?? ''));
            if (strlen($instruction) < 2)
                return $this->jsonError("Please describe what you want to change");

            // Optional earlier turns; malformed entries are dropped, not fatal
            $history = [];
            foreach ((is_array($body['history'] ?? null) ? $body['history'] : []) as $turn) {
                if (is_array($turn) && in_array($turn['role'] ?? null, ['user', 'assistant'], true) && is_string($turn['text'] ?? null)) {
                    $history[] = ['role' => $turn['role'], 'text' => $turn['text']];
                }
            }

            $schema = is_array($body['schema'] ?? null) ? $body['schema'] : [];
            $result = (new CopilotEditor())->run($document, $instruction, $history, $schema);

            $this->operations = $result['operations'];
            $this->note = $result['note'];
            $this->meta = [
                'iterations' => $result['iterations'],
                'truncated' => $result['truncated'],
                'cached_tokens' => $result['usage']['prompt_tokens_details']['cached_tokens'] ?? null,
                'prompt_tokens' => $result['usage']['prompt_tokens'] ?? null,
                'completion_tokens' => $result['usage']['completion_tokens'] ?? null,
            ];

            return $this->jsonSuccess($result['note'] ?: (empty($result['operations']) ? "No changes proposed for that instruction" : "Edit plan generated"));
        }

        catch(\Exception $e){
            return $this->jsonException($e);
        }
    }

    /**
     * Extract plain text from an uploaded document (pdf/doc/docx).
     * Used by the AI editor to pre-fill the prompt from a document.
     *
     * @return void
     */
    public function extract()
    {
        try {
            $file = $_FILES['document'] ?? null;
            if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                return $this->jsonError('No file uploaded');
            }

            if ($file['size'] > 10 * 1024 * 1024) {
                return $this->jsonError('File is too large (10 MB max)');
            }

            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['pdf', 'doc', 'docx'], true)) {
                return $this->jsonError('Only PDF, DOC, and DOCX files are supported');
            }

            $text = DocumentExtractor::extract($file['tmp_name'], $ext);
            if (trim($text) === '') {
                return $this->jsonError('The document appears to be empty');
            }

            $this->text = $text;
            $this->suggested_title = pathinfo($file['name'], PATHINFO_FILENAME);
            return $this->jsonSuccess('Text extracted successfully');
        } catch (\Exception $e) {
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
            ->where(function ($query) {
                $query->where('user_id', auth()->id())
                    ->orWhereJsonContains('members', (string) auth()->id());
            })
            ->get();

        if($spaces->count()) return true;

        return false;
    }

    public static function routes()
    {
        app()::get('', ['name'=>'forms.list', 'FormsController@index']);
        app()::get('search', ['name'=>'forms.search', 'FormsController@search']);

        app()::post('build', ['name'=>'forms.build', 'FormsController@build']);
        app()::get('delete/{id}', ['name'=>'forms.delete', 'FormsController@delete']);
        app()::get('preview/{id}', ['name'=>'forms.preview', 'FormsController@preview']);
        app()::get('setup/{id}/{slug}', ['name'=>'forms.setup', 'FormsController@setup']);
        app()::get('customize/{id}/{slug}', ['name'=>'forms.customize', 'FormsController@customize']);
        
        app()::post('edit/{id}', ['name'=>'forms.update', 'FormsController@update']);
        app()::post('copilot', ['name'=>'forms.copilot', 'FormsController@copilot']);
        app()::post('extract', ['name'=>'forms.extract', 'FormsController@extract']);
        app()::post('setup/{setting}/{id}', ['name'=>'forms.setup.update', 'FormsController@setting']);
        app()::post('rules/{id}', ['name'=>'forms.rules', 'FormsController@rules']);

        app()::get('template/{id}', ['name'=>'forms.template', 'FormsController@template']);
    }
}