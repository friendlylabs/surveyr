<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\SubmissionView;
use App\Support\SubmissionFilters;
use App\Support\SurveyAnswers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

/**
 * SubmissionViewsController
 *
 * A form's saved views of its responses: saving the responses table's filters under a name, changing and deleting
 * them. Views are shared with everyone who can see the form (who may save new ones); the one who saved a view, or
 * whoever manages the form, changes or deletes it.
 */
class SubmissionViewsController extends Controller
{
    /** Saves the posted filters as a new view, then opens it: on the responses table, or with return=visualize on Visualize. */
    public function store(Request $request, Form $form): JsonResponse
    {
        Gate::authorize('view', $form);

        $view = new SubmissionView($this->validated($request, $form));
        $view->workspace_id = $form->workspace_id;
        $view->user()->associate($request->user());
        $view->form()->associate($form);
        $view->save();

        $this->redirect = $this->opened($request, $form, $view);

        return $this->jsonSuccess(__('View saved'));
    }

    /** Changes a view's name, description and filters. */
    public function update(Request $request, Form $form, SubmissionView $view): JsonResponse
    {
        Gate::authorize('update', $view);

        $view->update($this->validated($request, $form, $view));
        $this->redirect = $this->opened($request, $form, $view);

        return $this->jsonSuccess(__('View updated'));
    }

    /** Where a saved view opens: the page it was saved from (return=visualize), else the responses table. */
    private function opened(Request $request, Form $form, SubmissionView $view): string
    {
        return $request->input('return') === 'visualize'
            ? route('forms.responses.visualize', [$form, 'view' => $view])
            : route('forms.responses', [$form, 'view' => $view]);
    }

    /** Deletes a view; its responses are untouched. */
    public function destroy(Form $form, SubmissionView $view): JsonResponse
    {
        Gate::authorize('delete', $view);

        $view->delete();
        $this->redirect = url()->previous() === route('forms.responses', [$form, 'view' => $view])
            ? route('forms.responses', $form)
            : url()->previous();

        return $this->jsonSuccess(__('View deleted'));
    }

    /**
     * The view's fields from the request; the filters arrive as JSON and are cleaned against the form's questions.
     * Leaving out name and description (a "save changes" from the filter panel) keeps the view's own.
     *
     * @return array{name: string, description: ?string, filters: array}
     */
    private function validated(Request $request, Form $form, ?SubmissionView $view = null): array
    {
        $data = $request->validate([
            'name' => [$view ? 'sometimes' : 'required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:300'],
            'filters' => ['required', 'json'],
        ]);

        $fields = SubmissionFilters::fields(SurveyAnswers::questions($form->content));

        return [
            'name' => $data['name'] ?? $view->name,
            'description' => array_key_exists('description', $data) ? $data['description'] : $view?->description,
            'filters' => SubmissionFilters::normalise(json_decode($data['filters'], true), $fields),
        ];
    }

    /** Registers the views routes (inside the forms prefix); a view is only found through its own form. */
    public static function routes(): void
    {
        Route::scopeBindings()->group(function () {
            Route::post('/{form}/views', [self::class, 'store'])->name('forms.views.store');
            Route::post('/{form}/views/{view}', [self::class, 'update'])->name('forms.views.update');
            Route::post('/{form}/views/{view}/delete', [self::class, 'destroy'])->name('forms.views.destroy');
        });
    }
}
