<?php

namespace App\Console\Commands;

use App\Models\Form;
use App\Models\VisualizationChart;
use App\Models\VisualizationTab;
use App\Support\Analytics\LegacyRules;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * ImportLegacyCharts
 *
 * Brings each legacy form's visualization rules (forms.viz_rules on the legacy connection) over as a Visualize tab of
 * charts on the form imported from it (matched by legacy_key, md5 of the legacy id), through LegacyRules.
 *
 * Safe to run again: a form that already has charts is left alone, so nothing is doubled and charts made since stay.
 * A form without charts loses its empty tabs (Visualize's own "Default") and gets the imported tab in their place.
 */
#[Signature('legacy:import-charts
    {--form=* : Only these legacy form ids}
    {--dry-run : Report what would come over without writing anything}')]
#[Description('Bring the legacy forms\' visualization rules over as Visualize tabs')]
class ImportLegacyCharts extends Command
{
    /** @var array{imported: int, charts: int, has_charts: int, nothing: int, unreadable: int, missing: int} */
    private array $counts = ['imported' => 0, 'charts' => 0, 'has_charts' => 0, 'nothing' => 0, 'unreadable' => 0, 'missing' => 0];

    /** Walks the legacy forms with rules, in id order, and imports each one's charts. */
    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        DB::connection('legacy')->table('forms')
            ->select(['id', 'viz_rules'])
            ->whereNotNull('viz_rules')
            ->where('viz_rules', '!=', '')
            ->when($this->option('form'), fn ($query, array $ids) => $query->whereIn('id', $ids))
            ->chunkById(200, fn (Collection $legacy) => $this->importChunk($legacy, $dryRun));

        $this->table(['', __('Forms')], [
            [__('Imported (:charts charts)', ['charts' => $this->counts['charts']]), $this->counts['imported']],
            [__('Already had charts'), $this->counts['has_charts']],
            [__('Nothing to chart'), $this->counts['nothing']],
            [__('Unreadable rules'), $this->counts['unreadable']],
            [__('Form not imported'), $this->counts['missing']],
        ]);
        if ($dryRun) {
            $this->comment(__('Dry run: nothing was written.'));
        }

        return self::SUCCESS;
    }

    /**
     * One chunk of legacy forms against the forms imported from them.
     *
     * @param  Collection<int, object{id: int, viz_rules: string}>  $legacy
     */
    private function importChunk(Collection $legacy, bool $dryRun): void
    {
        $forms = Form::withoutGlobalScope('workspace')
            ->whereIn('legacy_key', $legacy->map(fn ($row) => md5((string) $row->id)))
            ->withCount(['visualizationCharts' => fn ($query) => $query->withoutGlobalScope('workspace')])
            ->get()
            ->keyBy('legacy_key');

        foreach ($legacy as $row) {
            $form = $forms->get(md5((string) $row->id));
            $rules = json_decode($row->viz_rules, true);

            if (! $form) {
                $this->counts['missing']++;

                continue;
            }
            if (! is_array($rules)) {
                $this->counts['unreadable']++;
                $this->warn(__('Legacy form :id: its rules are not readable JSON.', ['id' => $row->id]));

                continue;
            }
            if ($form->visualization_charts_count > 0) {
                $this->counts['has_charts']++;

                continue;
            }

            $tab = LegacyRules::toTab($rules, $form->content);
            if ($tab['charts'] === []) {
                $this->counts['nothing']++;

                continue;
            }

            if (! $dryRun) {
                $this->store($form, $tab);
            }
            $this->counts['imported']++;
            $this->counts['charts'] += count($tab['charts']);
            $this->line(__('Legacy form :id → :form: :count charts', ['id' => $row->id, 'form' => $form->hash, 'count' => count($tab['charts'])]), verbosity: 'v');
        }
    }

    /**
     * Replaces a chartless form's empty tabs with the imported one.
     *
     * @param  array{name: string, charts: list<array{kind: string, title: null, rules: array<string, mixed>}>}  $built
     */
    private function store(Form $form, array $built): void
    {
        DB::transaction(function () use ($form, $built) {
            VisualizationTab::withoutGlobalScope('workspace')->where('form_id', $form->id)->delete();

            $tab = new VisualizationTab(['name' => $built['name']]);
            $tab->workspace_id = $form->workspace_id;
            $tab->form()->associate($form);
            $tab->position = 0;
            $tab->save();

            foreach ($built['charts'] as $position => $chart) {
                $model = new VisualizationChart($chart);
                $model->workspace_id = $form->workspace_id;
                $model->form()->associate($form);
                $model->tab()->associate($tab);
                $model->position = $position;
                $model->save();
            }
        });
    }
}
