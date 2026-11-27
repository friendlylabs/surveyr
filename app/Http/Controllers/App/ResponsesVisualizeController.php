<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\VisualizationTab;
use App\Support\Analytics\Analytics;
use App\Support\Analytics\ChartRules;
use App\Support\Analytics\Question;
use App\Support\Analytics\Questions;
use App\Support\ResponsesQuery;
use App\Support\SubmissionFilters;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

/**
 * ResponsesVisualizeController
 *
 * Visualize: tabs of charts the form's editors build (VisualizationTabsController, VisualizationChartsController), each
 * chart summarising one question over the responses the responses table lists (the same view, filters, search and
 * archived state, from the query string). A form starts with one empty tab, Default. The server counts
 * (App\Support\Analytics); the page draws the open tab's charts through the headless engine (libs/surveyr_analytics)
 * and ApexCharts, with a data table beside every chart. A choice question's Other texts come a page at a time
 * (answers()).
 */
class ResponsesVisualizeController extends Controller
{
    /** The page, on the tab ?tab= names (the first when none is given). */
    public function index(Request $request, Form $form): View
    {
        Gate::authorize('view', $form);

        $listing = ResponsesQuery::from($request, $form);
        $questions = Questions::from($form->content);
        $this->tabs = $form->visualizationTabs()->get();
        // every form has at least one tab to add charts to
        if ($this->tabs->isEmpty()) {
            $default = new VisualizationTab(['name' => __('Default')]);
            $default->workspace_id = $form->workspace_id;
            $default->form()->associate($form);
            $default->save();
            $this->tabs = collect([$default]);
        }
        $this->tab = $this->tabs->firstWhere('hash', $request->query('tab')) ?? $this->tabs->first();
        $this->canEdit = Gate::allows('update', $form);
        $this->chartable = ChartRules::chartable($questions);
        $this->groupable = ChartRules::groupable($questions);
        $this->questionVisuals = collect($questions)->mapWithKeys(fn (Question $question) => [$question->name() => $question->visuals()])->all();

        $this->form = $form;
        $this->listing = $listing;
        $this->archived = $listing->archived;
        $this->activeView = $listing->activeView;
        $this->fields = $listing->fields;
        $this->filters = $listing->filters;
        $this->filterCount = $listing->filterCount();
        $this->filterLines = SubmissionFilters::describe($listing->filters, $listing->fields);
        $this->views = $form->views()->get();
        $this->canUpdateView = $listing->activeView && Gate::allows('update', $listing->activeView);
        $this->carried = ResponsesQuery::carried($request);

        $this->charts = $this->tab?->charts()->get() ?? collect();
        $this->summaries = Analytics::summarise($form, $listing->query(), $this->charts);
        $this->total = collect($this->summaries)->firstWhere('kind', '!=', 'missing')['total'] ?? $listing->query()->count();

        // the first page of each choice question's Other texts (once per question), so the page and its print show them
        $byName = collect($questions)->keyBy(fn (Question $question) => $question->name());
        $this->answers = collect($this->summaries)
            ->filter(fn (array $summary) => ($summary['otherAnswers'] ?? 0) > 0)
            ->unique('question')
            ->mapWithKeys(fn (array $summary) => [$summary['question'] => self::page(Analytics::answers($byName[$summary['question']], $listing->query()), $form)])
            ->all();

        return view('app.responses.visualize.index', $this->data);
    }

    /** A page of a choice question's typed Other texts (?question=name&page=), over the same responses as the page. */
    public function answers(Request $request, Form $form): JsonResponse
    {
        Gate::authorize('view', $form);

        $question = collect(Questions::from($form->content))->first(fn (Question $question) => $question->name() === $request->query('question')
            && in_array($question->kind, ['choice', 'choices'], true));
        if (! $question) {
            return $this->jsonError(__('This question has no answers to list.'), 422);
        }

        $this->answers = self::page(Analytics::answers($question, ResponsesQuery::from($request, $form)->query(), max(1, $request->integer('page', 1))), $form);

        return $this->jsonSuccess('');
    }

    /**
     * A page of Other texts as the page shows them: the text, the response's reference and when it came in, and a link
     * that opens the response.
     *
     * @return array{items: list<array{text: string, reference: string, date: string, url: string}>, page: int, lastPage: int, total: int, perPage: int}
     */
    private static function page(LengthAwarePaginator $page, Form $form): array
    {
        return [
            'items' => collect($page->items())->map(fn (array $item) => [
                'text' => $item['text'],
                'reference' => $item['response']->reference,
                'date' => $item['response']->created_at->format('j M Y, H:i'),
                'url' => route('forms.responses', [$form, 'response' => $item['response']->hash]),
            ])->all(),
            'page' => $page->currentPage(),
            'lastPage' => $page->lastPage(),
            'total' => $page->total(),
            'perPage' => $page->perPage(),
        ];
    }

    /** Registers the Visualize routes (inside the forms prefix), before the responses' own /{form}/responses/{submission}. */
    public static function routes(): void
    {
        Route::get('/{form}/responses/visualize', [self::class, 'index'])->name('forms.responses.visualize');
        Route::get('/{form}/responses/visualize/answers', [self::class, 'answers'])->name('forms.responses.visualize.answers');
    }
}
