<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Jobs\ExportResponses;
use App\Models\Form;
use App\Models\Submission;
use App\Models\Upload;
use App\Support\AttachmentsExport;
use App\Support\ResponsesExport;
use App\Support\ResponsesQuery;
use App\Support\SubmissionFilters;
use Carbon\CarbonInterval;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * FormResponsesController
 *
 * A form's responses, as in the legacy app: a table with every question as a column (sortable, scrolling sideways), a
 * response opening in a side panel, and archiving in place of deleting, with an Archived view to restore from. The
 * filter panel narrows it by date and by answers (?filters=, base64url JSON), and a saved view (?view=) brings back a
 * set of filters by name (SubmissionViewsController). The same list exports as a styled Excel workbook, with its
 * attachments zipped in the background when asked (ExportResponses). Anyone
 * who can see the form sees its responses; those who can edit it archive and restore them; its owner, collaborators
 * and workspace managers can purge them all for good.
 */
class FormResponsesController extends Controller
{
    /** Rows per page. */
    const PER_PAGE = 25;

    /** How long a file's signed link works, in minutes. */
    const FILE_LINK_MINUTES = 5;

    /** Columns that aren't questions, and can be sorted on. */
    const SORTS = ['number', 'submitted'];

    /** The responses table (?archived=1: the archived ones). */
    public function index(Request $request, Form $form): View
    {
        Gate::authorize('view', $form);

        $this->form = $form;
        $responses = $this->listed($request, $form);
        // nothing to filter yet: the list hides its search and filters
        $this->hasResponses = $form->submissions()->{$this->archived ? 'archived' : 'active'}()->exists();
        $this->views = $form->views()->get();
        $this->canUpdateView = $this->activeView && Gate::allows('update', $this->activeView);

        $this->responses = $responses->with('collector')->paginate(self::PER_PAGE)->withQueryString();

        $this->archivedCount = $this->archived ? null : $form->submissions()->archived()->count();
        $this->canArchive = Gate::allows('archiveResponses', $form);
        $this->canPurge = Gate::allows('purgeResponses', $form);
        // file questions and signature pads, dynamic panels' too: the attachments export and option show only then
        $this->hasAttachments = AttachmentsExport::questions($form->content) !== [];

        return view('app.responses.index', $this->data);
    }

    /**
     * The responses as a styled Excel workbook (ResponsesExport): those the table lists (the view, filters, search and
     * order in the query string), or with responses[] just the ticked ones, in the table's order. details=1 starts each
     * row with the reference, when it came in, who collected it and from where; without, only the answers. With
     * attachments=1 (a form with file questions) the workbook and every file are zipped in the background
     * (ExportResponses) and the link emailed; otherwise the workbook downloads straight away.
     */
    public function export(Request $request, Form $form): BinaryFileResponse|JsonResponse
    {
        Gate::authorize('view', $form);

        $selected = array_values(array_filter((array) $request->input('responses', []), 'is_string'));
        $details = $request->boolean('details');
        $responses = $this->listed($request, $form)->when($selected, fn (Builder $query) => $query->whereIn('hash', $selected));
        $withAttachments = $request->boolean('attachments') && AttachmentsExport::questions($form->content) !== [];

        $about = array_filter([
            __('Exported') => now()->format('j M Y, H:i'),
            __('Exported by') => $request->user()->name,
            __('Showing') => $selected ? __('Selected responses') : ($this->archived ? __('Archived responses') : __('All responses')),
            __('View') => $this->activeView?->name,
            __('Filters') => implode('; ', SubmissionFilters::describe($this->filters, $this->fields)),
            __('Search') => (string) $request->query('q'),
            __('Attachments') => $withAttachments ? __('In the attachments folder beside this workbook') : null,
        ]);

        if ($withAttachments) {
            ExportResponses::dispatch($request->user(), $form, $responses->pluck('id')->all(), $about, $details);
            $this->data = [];

            return $this->jsonSuccess(__("We're preparing your export. We'll email :email a download link when it's ready.", ['email' => $request->user()->email]));
        }

        $export = new ResponsesExport($form, $details, author: $request->user()->name);

        return response()->download($export->write($responses->with('collector')->lazy(500), $about), $export->filename())->deleteFileAfterSend();
    }

    /**
     * Just the attachments of the responses the table lists (or with responses[] the ticked ones), as the legacy app
     * exported them: zipped in the background (ExportResponses, AttachmentsExport) and the link emailed. Refused at once
     * when the form takes no files, or when none of these responses has one.
     */
    public function attachments(Request $request, Form $form): JsonResponse
    {
        Gate::authorize('view', $form);

        $questions = AttachmentsExport::questions($form->content);
        if (! $questions) {
            return $this->jsonError(__('This form has no file questions.'), 422);
        }

        $selected = array_values(array_filter((array) $request->input('responses', []), 'is_string'));
        $responses = $this->listed($request, $form)->when($selected, fn (Builder $query) => $query->whereIn('hash', $selected));
        $this->data = [];

        // only those with an answer to a file question, when every file question sits at the top (a plain JSON path)
        $paths = array_column($questions, 'path');
        if (collect($paths)->every(fn (array $path) => count($path) === 1 && preg_match('/^[\pL\pN _.-]+$/u', $path[0]) === 1)) {
            $responses->where(fn (Builder $query) => collect($paths)->each(
                fn (array $path) => $query->orWhere(fn (Builder $answered) => $answered->whereNotNull('data->'.$path[0])->where('data->'.$path[0], '<>', '[]')->where('data->'.$path[0], '<>', ''))
            ));
        }
        $ids = $responses->pluck('id')->all();
        if (! $ids) {
            return $this->jsonError(__('There are no attachments in these responses.'), 422);
        }

        ExportResponses::dispatch($request->user(), $form, $ids, kind: ExportResponses::ATTACHMENTS);

        return $this->jsonSuccess(__("We're gathering the attachments. We'll email :email a download link when they're ready.", ['email' => $request->user()->email]));
    }

    /** A file of a response's answer: once access is checked, off to a link that works for a few minutes (Public\FilesController). */
    public function file(Request $request, Form $form, Submission $submission): RedirectResponse
    {
        Gate::authorize('view', $form);

        $question = (string) $request->query('question');
        $index = (int) $request->query('file');
        $file = $submission->file($question, $index);
        abort_unless($file, 404);

        // kept in storage, or in the answer itself (signatures, older responses)
        return redirect()->away(isset($file['upload'])
            ? URL::temporarySignedRoute('files.upload', now()->addMinutes(self::FILE_LINK_MINUTES), ['upload' => $file['upload']->hash])
            : URL::temporarySignedRoute('files.response', now()->addMinutes(self::FILE_LINK_MINUTES), [
                'submission' => $submission->hash, 'question' => $question, 'file' => $index,
            ]));
    }

    /** A file sent with a response, by its upload (the side panel shows files this way): off to a link that works for a few minutes. */
    public function upload(Form $form, Submission $submission, Upload $upload): RedirectResponse
    {
        Gate::authorize('view', $form);

        return redirect()->away(URL::temporarySignedRoute('files.upload', now()->addMinutes(self::FILE_LINK_MINUTES), ['upload' => $upload->hash]));
    }

    /** One response, for the side panel (JSON); opened as a page, it's the table with the panel open on it. */
    public function show(Request $request, Form $form, Submission $submission): JsonResponse|RedirectResponse
    {
        Gate::authorize('view', $form);

        if (! $request->expectsJson()) {
            return redirect()->route('forms.responses', array_filter([$form, 'archived' => $submission->archived_at ? 1 : null, 'response' => $submission->hash]));
        }

        // its neighbours in the same view, newest first as the table opens
        $siblings = $form->submissions()->{$submission->archived_at ? 'archived' : 'active'}();
        $seconds = $submission->meta['seconds'] ?? null;

        return response()->json([
            'success' => true,
            'response' => [
                'hash' => $submission->hash,
                'reference' => $submission->reference,
                'submitted' => $submission->created_at->format('j M Y, H:i'),
                'ago' => $submission->created_at->diffForHumans(),
                'took' => $seconds !== null ? CarbonInterval::seconds($seconds)->cascade()->forHumans(['short' => true]) : null,
                'collector' => $submission->collector?->name,
                'ip' => $submission->ip_address,
                'archived' => $submission->archived_at !== null,
                'data' => $submission->data ?: (object) [],
                'newer' => (clone $siblings)->where('number', '>', $submission->number)->orderBy('number')->value('hash'),
                'older' => (clone $siblings)->where('number', '<', $submission->number)->orderByDesc('number')->value('hash'),
            ],
        ]);
    }

    /** Archives the given responses (archive=1), or restores them (archive=0): from the table's bulk pill or the panel. */
    public function archive(Request $request, Form $form): JsonResponse
    {
        Gate::authorize('archiveResponses', $form);

        $data = $request->validate([
            'responses' => ['required', 'array', 'max:500'],
            'responses.*' => ['string'],
            'archive' => ['required', 'boolean'],
        ]);

        $archive = (bool) $data['archive'];
        $count = $form->submissions()->whereIn('hash', $data['responses'])
            ->{$archive ? 'active' : 'archived'}()
            ->update(['archived_at' => $archive ? now() : null]);

        // back to the view it came from, without the panel that was open
        $this->redirect = $this->withoutPanel(url()->previous());

        return $this->jsonSuccess($archive
            ? trans_choice(':count response archived|:count responses archived', $count)
            : trans_choice(':count response restored|:count responses restored', $count));
    }

    /** Deletes every response of the form, archived ones too, for good. */
    public function purge(Form $form): JsonResponse
    {
        Gate::authorize('purgeResponses', $form);

        // their files go first, from wherever they're kept
        Upload::withoutGlobalScopes()->where('form_id', $form->id)->whereNotNull('submission_id')->lazyById()->each->delete();
        $count = DB::transaction(fn () => $form->submissions()->delete());
        $this->redirect = route('forms.responses', $form);

        return $this->jsonSuccess(trans_choice(':count response deleted|:count responses deleted', $count));
    }

    /**
     * The responses the table lists, in its order: the active or archived ones (?archived=1), a saved view's filters
     * (?view=) or the panel's own (?filters=, which change a view without saving it), the search (?q=) and ?sort= / ?dir=.
     * Sets what the page shows about them (archived, questions, sort, fields, activeView, filters, filterCount).
     */
    private function listed(Request $request, Form $form): Builder
    {
        $listing = ResponsesQuery::from($request, $form);
        $this->archived = $listing->archived;
        $this->questions = $listing->questions;
        [$this->sort, $this->direction] = $this->sorting($request);

        $this->fields = $listing->fields;
        $this->activeView = $listing->activeView;
        $this->filters = $listing->filters;
        $this->filterCount = $listing->filterCount();

        return $listing->query()
            ->when($this->sort === 'submitted', fn (Builder $query) => $query->orderBy('created_at', $this->direction))
            ->when(! in_array($this->sort, self::SORTS, true), fn (Builder $query) => $query->orderBy('data->'.$this->sort, $this->direction))
            ->orderBy('number', $this->sort === 'number' ? $this->direction : 'desc');
    }

    /**
     * The ?sort= column (number, submitted or a question's name) and ?dir=; newest first by default. A question is only
     * sortable when its name is plain (letters, digits, spaces, _ . -), as it goes into a JSON path.
     *
     * @return array{0: string, 1: 'asc'|'desc'}
     */
    private function sorting(Request $request): array
    {
        $sortable = array_merge(self::SORTS, array_column(array_filter($this->questions, fn (array $question) => self::sortable($question)), 'name'));

        $validated = validator($request->only('sort', 'dir'), [
            'sort' => ['nullable', 'string', Rule::in($sortable)],
            'dir' => ['nullable', Rule::in(['asc', 'desc'])],
        ])->valid();

        return [$validated['sort'] ?? 'submitted', $validated['dir'] ?? 'desc'];
    }

    /** Whether the table can sort on the question. */
    public static function sortable(array $question): bool
    {
        return $question['kind'] !== 'file' && preg_match('/^[\pL\pN _.-]+$/u', $question['name']) === 1;
    }

    /** The URL without ?response= (the open panel). */
    private function withoutPanel(string $url): string
    {
        $parts = parse_url($url);
        parse_str($parts['query'] ?? '', $query);
        unset($query['response']);

        return strtok($url, '?').($query ? '?'.http_build_query($query) : '');
    }

    /** Registers the responses routes (inside the forms prefix); a response is only found through its own form. */
    public static function routes(): void
    {
        Route::scopeBindings()->group(function () {
            Route::get('/{form}/responses', [self::class, 'index'])->name('forms.responses');
            Route::post('/{form}/responses/archive', [self::class, 'archive'])->name('forms.responses.archive');
            Route::post('/{form}/responses/purge', [self::class, 'purge'])->name('forms.responses.purge');
            Route::post('/{form}/responses/export', [self::class, 'export'])->name('forms.responses.export');
            Route::post('/{form}/responses/attachments', [self::class, 'attachments'])->name('forms.responses.attachments');
            Route::get('/{form}/responses/{submission}/file', [self::class, 'file'])->name('forms.responses.file');
            Route::get('/{form}/responses/{submission}/uploads/{upload}', [self::class, 'upload'])->name('forms.responses.upload');
            Route::get('/{form}/responses/{submission}', [self::class, 'show'])->name('forms.responses.show');
        });
    }
}
