<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\VisualizationChart;
use App\Models\VisualizationTab;
use App\Support\Analytics\ChartRules;
use App\Support\Analytics\Questions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

/**
 * VisualizationChartsController
 *
 * The charts on a Visualize tab: adding one (a question and its visual, or a grouped chart), changing and removing it;
 * the page's masonry arranges them, in the order they were added. Rules are cleaned against the form's questions
 * (ChartRules). Those who can edit the form do this; each change returns to the tab with the filters in force.
 */
class VisualizationChartsController extends Controller
{
    /** Adds a chart at the end of the tab. */
    public function store(Request $request, Form $form, VisualizationTab $visualizationTab): JsonResponse
    {
        Gate::authorize('update', $form);

        $chart = new VisualizationChart($this->validated($request, $form));
        $chart->workspace_id = $form->workspace_id;
        $chart->form()->associate($form);
        $chart->tab()->associate($visualizationTab);
        $chart->position = (int) $visualizationTab->charts()->max('position') + 1;
        $chart->save();

        $this->redirect = VisualizationTabsController::visualize($form, $visualizationTab);

        return $this->jsonSuccess(__('Chart added'));
    }

    /** Changes a chart's question, rules or title. */
    public function update(Request $request, Form $form, VisualizationChart $visualizationChart): JsonResponse
    {
        Gate::authorize('update', $form);

        $visualizationChart->update($this->validated($request, $form));
        $this->redirect = VisualizationTabsController::visualize($form, $visualizationChart->tab);

        return $this->jsonSuccess(__('Chart updated'));
    }

    /** Copies a chart, rules and title, to the end of its tab. */
    public function duplicate(Form $form, VisualizationChart $visualizationChart): JsonResponse
    {
        Gate::authorize('update', $form);

        $copy = $visualizationChart->replicate(['hash']);
        $copy->position = (int) $visualizationChart->tab->charts()->max('position') + 1;
        $copy->save();
        $this->redirect = VisualizationTabsController::visualize($form, $visualizationChart->tab);

        return $this->jsonSuccess(__('Chart copied'));
    }

    /** Removes a chart from its tab. */
    public function destroy(Form $form, VisualizationChart $visualizationChart): JsonResponse
    {
        Gate::authorize('update', $form);

        $tab = $visualizationChart->tab;
        $visualizationChart->delete();
        $this->redirect = VisualizationTabsController::visualize($form, $tab);

        return $this->jsonSuccess(__('Chart removed'));
    }

    /**
     * The chart's fields from the request: its rules cleaned against the form's questions, and an optional title.
     *
     * @return array{kind: string, title: ?string, rules: array<string, mixed>}
     *
     * @throws ValidationException when the question can't be charted
     */
    private function validated(Request $request, Form $form): array
    {
        $kind = $request->input('kind') === 'grouped' ? 'grouped' : 'question';
        $data = $request->validate([
            'question' => [$kind === 'question' ? 'required' : 'nullable', 'string'],
            'label' => [$kind === 'grouped' ? 'required' : 'nullable', 'string'],
            'mode' => ['nullable', 'in:numbers,split'],
            'series' => ['nullable', 'array', 'max:'.ChartRules::MAX_SERIES],
            'series.*.question' => ['nullable', 'string'],
            'series.*.aggregate' => ['nullable', 'string'],
            'split' => ['nullable', 'string'],
            'visual' => ['nullable', 'string'],
            'sort' => ['nullable', 'string'],
            'limit' => ['nullable', 'integer', 'min:0', 'max:'.ChartRules::MAX_LIMIT],
            'title' => ['nullable', 'string', 'max:160'],
        ]);
        $questions = Questions::from($form->content);

        if ($kind === 'grouped') {
            $label = collect($questions)->first(fn ($question) => $question->name() === $data['label']);
            if (! $label || ! ChartRules::labels($label)) {
                throw ValidationException::withMessages(['label' => __('Pick a question whose answers can group the responses.')]);
            }
            $rules = ChartRules::cleanGrouped($data, $questions);
            if (! $rules) {
                throw ValidationException::withMessages([($data['mode'] ?? 'numbers') === 'split' ? 'split' : 'series' => ($data['mode'] ?? 'numbers') === 'split'
                    ? __('Pick a choice question to split each group by, other than the one that groups them.')
                    : __('Pick at least one number question to work out per group.')]);
            }
        } else {
            $rules = ChartRules::clean($data, $questions);
            if (! $rules) {
                throw ValidationException::withMessages(['question' => __('Pick a question this form can chart.')]);
            }
        }

        return ['kind' => $kind, 'title' => filled($data['title'] ?? null) ? trim($data['title']) : null, 'rules' => $rules];
    }

    /** Registers the chart routes (inside the forms prefix); a tab or chart is only found through its own form. */
    public static function routes(): void
    {
        Route::scopeBindings()->group(function () {
            Route::post('/{form}/visualize/tabs/{visualizationTab}/charts', [self::class, 'store'])->name('forms.visualize.charts.store');
            Route::post('/{form}/visualize/charts/{visualizationChart}', [self::class, 'update'])->name('forms.visualize.charts.update');
            Route::post('/{form}/visualize/charts/{visualizationChart}/duplicate', [self::class, 'duplicate'])->name('forms.visualize.charts.duplicate');
            Route::post('/{form}/visualize/charts/{visualizationChart}/delete', [self::class, 'destroy'])->name('forms.visualize.charts.destroy');
        });
    }
}
