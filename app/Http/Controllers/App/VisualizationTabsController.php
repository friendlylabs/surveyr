<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\VisualizationTab;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

/**
 * VisualizationTabsController
 *
 * A form's Visualize tabs: adding, renaming, moving and deleting them (deleting takes its charts). Those who can edit
 * the form do this; everyone who can see it sees the tabs. Each change returns to Visualize with the filters in force.
 */
class VisualizationTabsController extends Controller
{
    /** Adds a tab at the end, then opens it. */
    public function store(Request $request, Form $form): JsonResponse
    {
        Gate::authorize('update', $form);

        $tab = new VisualizationTab($request->validate(['name' => ['required', 'string', 'max:80']]));
        $tab->workspace_id = $form->workspace_id;
        $tab->form()->associate($form);
        $tab->position = (int) $form->visualizationTabs()->max('position') + 1;
        $tab->save();

        $this->redirect = self::visualize($form, $tab);

        return $this->jsonSuccess(__('Tab added'));
    }

    /** Renames a tab. */
    public function update(Request $request, Form $form, VisualizationTab $visualizationTab): JsonResponse
    {
        Gate::authorize('update', $form);

        $visualizationTab->update($request->validate(['name' => ['required', 'string', 'max:80']]));
        $this->redirect = self::visualize($form, $visualizationTab);

        return $this->jsonSuccess(__('Tab renamed'));
    }

    /** Moves a tab one place left (direction -1) or right (1). */
    public function move(Request $request, Form $form, VisualizationTab $visualizationTab): JsonResponse
    {
        Gate::authorize('update', $form);

        $direction = (int) $request->validate(['direction' => ['required', 'in:-1,1']])['direction'];
        self::reorder($form->visualizationTabs()->get()->all(), $visualizationTab, $direction);
        $this->redirect = self::visualize($form, $visualizationTab);

        return $this->jsonSuccess('');
    }

    /** Copies a tab and its charts, right after it, then opens the copy. */
    public function duplicate(Form $form, VisualizationTab $visualizationTab): JsonResponse
    {
        Gate::authorize('update', $form);

        $copy = DB::transaction(function () use ($form, $visualizationTab) {
            // make room right after the original
            $form->visualizationTabs()->where('position', '>', $visualizationTab->position)->increment('position');

            $copy = $visualizationTab->replicate(['hash']);
            $copy->name = mb_substr(__(':name (copy)', ['name' => $visualizationTab->name]), 0, 80);
            $copy->position = $visualizationTab->position + 1;
            $copy->save();
            $visualizationTab->charts()->get()->each(fn ($chart) => $chart->replicate(['hash'])->tab()->associate($copy)->save());

            return $copy;
        });
        $this->redirect = self::visualize($form, $copy);

        return $this->jsonSuccess(__('Tab copied'));
    }

    /** Deletes a tab and its charts, then opens the first tab left. */
    public function destroy(Form $form, VisualizationTab $visualizationTab): JsonResponse
    {
        Gate::authorize('update', $form);

        $visualizationTab->delete();
        $this->redirect = self::visualize($form, null);

        return $this->jsonSuccess(__('Tab deleted'));
    }

    /**
     * Moves an item one place within its list and numbers the list from 0, so positions stay dense.
     *
     * @param  list<Model>  $items
     */
    public static function reorder(array $items, mixed $item, int $direction): void
    {
        $index = collect($items)->search(fn ($candidate) => $candidate->is($item));
        $target = $index + $direction;
        if ($index === false || $target < 0 || $target >= count($items)) {
            return;
        }
        [$items[$index], $items[$target]] = [$items[$target], $items[$index]];

        DB::transaction(function () use ($items) {
            foreach (array_values($items) as $position => $model) {
                if ($model->position !== $position) {
                    $model->position = $position;
                    $model->save();
                }
            }
        });
    }

    /**
     * Visualize on a tab (or the first, with null), keeping the filters, view and search of the page the change was
     * made from.
     */
    public static function visualize(Form $form, ?VisualizationTab $tab): string
    {
        $previous = parse_url(url()->previous());
        parse_str($previous['query'] ?? '', $query);
        $base = route('forms.responses.visualize', $form);
        $carried = ($previous['path'] ?? null) === parse_url($base, PHP_URL_PATH)
            ? array_intersect_key($query, array_flip(['view', 'filters', 'q', 'archived']))
            : [];

        return route('forms.responses.visualize', [$form, ...$carried, ...($tab ? ['tab' => $tab->hash] : [])]);
    }

    /** Registers the tab routes (inside the forms prefix); a tab is only found through its own form. */
    public static function routes(): void
    {
        Route::scopeBindings()->group(function () {
            Route::post('/{form}/visualize/tabs', [self::class, 'store'])->name('forms.visualize.tabs.store');
            Route::post('/{form}/visualize/tabs/{visualizationTab}', [self::class, 'update'])->name('forms.visualize.tabs.update');
            Route::post('/{form}/visualize/tabs/{visualizationTab}/move', [self::class, 'move'])->name('forms.visualize.tabs.move');
            Route::post('/{form}/visualize/tabs/{visualizationTab}/duplicate', [self::class, 'duplicate'])->name('forms.visualize.tabs.duplicate');
            Route::post('/{form}/visualize/tabs/{visualizationTab}/delete', [self::class, 'destroy'])->name('forms.visualize.tabs.destroy');
        });
    }
}
