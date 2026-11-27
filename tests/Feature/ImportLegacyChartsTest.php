<?php

namespace Tests\Feature;

use App\Models\Form;
use App\Models\VisualizationChart;
use App\Models\VisualizationTab;
use App\Models\Workspace;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * ImportLegacyChartsTest
 *
 * legacy:import-charts: legacy forms' visualization rules coming over as a Visualize tab on the forms imported from
 * them, read from a stand-in legacy database (an in-memory SQLite forms table). Running it again doubles nothing, and
 * forms that already have charts are left as they are.
 */
class ImportLegacyChartsTest extends TestCase
{
    use RefreshDatabase;

    private Workspace $workspace;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.connections.legacy' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        DB::purge('legacy');
        Schema::connection('legacy')->create('forms', function (Blueprint $table) {
            $table->id();
            $table->text('viz_rules')->nullable();
        });
        $this->workspace = Workspace::factory()->create();
    }

    /** A legacy form with the given rules, and the form imported from it (two charted questions and a text one). */
    private function imported(int $legacyId, mixed $rules): Form
    {
        DB::connection('legacy')->table('forms')->insert(['id' => $legacyId, 'viz_rules' => is_string($rules) ? $rules : json_encode($rules)]);

        return Form::factory()->in($this->workspace)->create(['legacy_key' => md5((string) $legacyId), 'content' => ['pages' => [['elements' => [
            ['type' => 'radiogroup', 'name' => 'source', 'title' => 'Water source', 'choices' => ['borehole', 'river']],
            ['type' => 'boolean', 'name' => 'treated', 'title' => 'Treated'],
            ['type' => 'text', 'name' => 'village', 'title' => 'Village'],
        ]]]]]);
    }

    /** Rules charting the two choice questions (one as a pie) and listing the text one. */
    private function rules(): array
    {
        return [
            'source' => ['visible' => true, 'visualizer' => 'selectBase', 'chart_type' => 'bar', 'sort' => 'value_desc', 'series' => []],
            'treated' => ['visible' => true, 'visualizer' => 'boolean', 'chart_type' => 'pie', 'series' => []],
            'village' => ['visible' => true, 'visualizer' => 'wordcloud', 'series' => []],
        ];
    }

    public function test_it_imports_a_legacy_forms_rules_as_a_tab_in_place_of_the_empty_default(): void
    {
        $form = $this->imported(7, $this->rules());
        VisualizationTab::factory()->of($form)->create(['name' => 'Default']);

        $this->artisan('legacy:import-charts')->assertSuccessful();

        $tabs = $form->visualizationTabs()->get();
        $this->assertSame(['Charts'], $tabs->pluck('name')->all());
        $charts = $tabs->first()->charts()->orderBy('position')->get();
        $this->assertSame(['source', 'treated'], $charts->pluck('rules.question')->all());
        $this->assertSame([$this->workspace->id], $charts->pluck('workspace_id')->unique()->values()->all());
        $this->assertSame('donut', $charts[1]->rules['visual']);
    }

    public function test_running_it_again_doubles_nothing_and_forms_with_charts_are_left_alone(): void
    {
        $fresh = $this->imported(7, $this->rules());
        $built = $this->imported(8, $this->rules());
        $tab = VisualizationTab::factory()->of($built)->create(['name' => 'Mine']);
        VisualizationChart::factory()->on($tab)->question('source')->create();

        $this->artisan('legacy:import-charts')->assertSuccessful();
        $this->artisan('legacy:import-charts')->assertSuccessful();

        $this->assertSame(2, $fresh->visualizationCharts()->count());
        $this->assertSame(['Mine'], $built->visualizationTabs()->pluck('name')->all());
        $this->assertSame(1, $built->visualizationCharts()->count());
    }

    public function test_a_dry_run_writes_nothing(): void
    {
        $form = $this->imported(7, $this->rules());

        $this->artisan('legacy:import-charts', ['--dry-run' => true])->assertSuccessful();

        $this->assertSame(0, $form->visualizationTabs()->count());
    }

    public function test_it_only_imports_the_forms_asked_for(): void
    {
        $asked = $this->imported(7, $this->rules());
        $other = $this->imported(8, $this->rules());

        $this->artisan('legacy:import-charts', ['--form' => [7]])->assertSuccessful();

        $this->assertSame(2, $asked->visualizationCharts()->count());
        $this->assertSame(0, $other->visualizationCharts()->count());
    }

    public function test_unreadable_or_chartless_rules_and_forms_not_imported_yet_are_skipped(): void
    {
        $broken = $this->imported(7, '{not json');
        $textOnly = $this->imported(8, ['village' => ['visible' => true, 'visualizer' => 'wordcloud']]);
        DB::connection('legacy')->table('forms')->insert(['id' => 9, 'viz_rules' => json_encode($this->rules())]);

        $this->artisan('legacy:import-charts')
            ->expectsOutputToContain('Legacy form 7')
            ->assertSuccessful();

        $this->assertSame(0, $broken->visualizationTabs()->count());
        $this->assertSame(0, $textOnly->visualizationTabs()->count());
        $this->assertSame(0, VisualizationTab::count());
    }
}
