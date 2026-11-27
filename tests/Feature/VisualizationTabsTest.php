<?php

namespace Tests\Feature;

use App\Enums\SpaceRole;
use App\Enums\WorkspaceRole;
use App\Models\Form;
use App\Models\Space;
use App\Models\User;
use App\Models\VisualizationChart;
use App\Models\VisualizationTab;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * VisualizationTabsTest
 *
 * Building Visualize (VisualizationTabsController, VisualizationChartsController): adding, renaming, moving and deleting
 * tabs, and adding, changing, moving and removing their charts, with rules cleaned against the form's questions. Only
 * those who can edit the form build; a tab or chart is only found through its own form.
 */
class VisualizationTabsTest extends TestCase
{
    use RefreshDatabase;

    private Workspace $workspace;

    private User $owner;

    private Form $form;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->workspace = Workspace::factory()->create();
        $this->owner = $this->member();
        $this->form = Form::factory()->in($this->workspace)->ownedBy($this->owner)->create(['content' => ['pages' => [['elements' => [
            ['type' => 'radiogroup', 'name' => 'source', 'title' => 'Water source', 'choices' => ['borehole', 'river']],
            ['type' => 'checkbox', 'name' => 'uses', 'title' => 'Uses', 'choices' => ['drinking', 'washing']],
            ['type' => 'comment', 'name' => 'notes', 'title' => 'Notes'],
        ]]]]]);
    }

    /** A workspace member with the given workspace role. */
    private function member(WorkspaceRole $role = WorkspaceRole::Member): User
    {
        $user = User::factory()->create();
        $this->workspace->addMember($user, $role);
        $user->switchWorkspace($this->workspace);

        return $user;
    }

    /** A member who can see the form but not change it (a space Viewer). */
    private function viewer(): User
    {
        $viewer = $this->member();
        $this->form->spaces()->attach(Space::factory()->in($this->workspace)->withMember($viewer, SpaceRole::Viewer)->create());

        return $viewer;
    }

    private function tab(array $attributes = []): VisualizationTab
    {
        return VisualizationTab::factory()->of($this->form)->create($attributes);
    }

    public function test_an_editor_adds_renames_and_deletes_a_tab(): void
    {
        $this->actingAs($this->owner)->postJson(route('forms.visualize.tabs.store', $this->form), ['name' => 'Equipment'])->assertOk();
        $tab = $this->form->visualizationTabs()->firstOrFail();
        $this->assertSame('Equipment', $tab->name);

        $this->actingAs($this->owner)->postJson(route('forms.visualize.tabs.update', [$this->form, $tab]), ['name' => 'Devices'])
            ->assertOk()->assertJsonPath('redirect', route('forms.responses.visualize', [$this->form, 'tab' => $tab->hash]));
        $this->assertSame('Devices', $tab->fresh()->name);

        VisualizationChart::factory()->on($tab)->question('source')->create();
        $this->actingAs($this->owner)->postJson(route('forms.visualize.tabs.destroy', [$this->form, $tab]))->assertOk();
        $this->assertDatabaseCount('visualization_tabs', 0);
        $this->assertDatabaseCount('visualization_charts', 0);
    }

    public function test_tabs_move_left_and_right(): void
    {
        $first = $this->tab(['name' => 'A', 'position' => 0]);
        $second = $this->tab(['name' => 'B', 'position' => 1]);
        $third = $this->tab(['name' => 'C', 'position' => 2]);

        $this->actingAs($this->owner)->postJson(route('forms.visualize.tabs.move', [$this->form, $third]), ['direction' => -1])->assertOk();
        $this->assertSame(['A', 'C', 'B'], $this->form->visualizationTabs()->pluck('name')->all());

        // the first can't go further left
        $this->actingAs($this->owner)->postJson(route('forms.visualize.tabs.move', [$this->form, $first]), ['direction' => -1])->assertOk();
        $this->assertSame(['A', 'C', 'B'], $this->form->visualizationTabs()->pluck('name')->all());
    }

    public function test_an_editor_adds_a_chart_with_its_rules_cleaned(): void
    {
        $tab = $this->tab();

        $this->actingAs($this->owner)->postJson(route('forms.visualize.charts.store', [$this->form, $tab]), ['question' => 'uses', 'limit' => 99])
            ->assertJsonValidationErrors('limit');

        $this->actingAs($this->owner)->postJson(route('forms.visualize.charts.store', [$this->form, $tab]), [
            'question' => 'uses', 'visual' => 'donut', 'sort' => 'sideways', 'limit' => 5, 'title' => '  What it is used for ',
        ])->assertOk();

        $chart = $tab->charts()->firstOrFail();
        // a question with several picks can't be a donut; an unknown order is the form's
        $this->assertSame(['question' => 'uses', 'visual' => null, 'sort' => 'form', 'limit' => 5], $chart->rules);
        $this->assertSame('What it is used for', $chart->title);
    }

    public function test_free_text_and_unknown_questions_cant_be_charted(): void
    {
        $tab = $this->tab();

        $this->actingAs($this->owner)->postJson(route('forms.visualize.charts.store', [$this->form, $tab]), ['question' => 'notes'])->assertJsonValidationErrors('question');
        $this->actingAs($this->owner)->postJson(route('forms.visualize.charts.store', [$this->form, $tab]), ['question' => 'missing'])->assertJsonValidationErrors('question');
        $this->assertDatabaseCount('visualization_charts', 0);
    }

    public function test_an_editor_changes_and_removes_a_chart(): void
    {
        $tab = $this->tab();
        $source = VisualizationChart::factory()->on($tab)->question('source')->create(['position' => 0]);
        $uses = VisualizationChart::factory()->on($tab)->question('uses')->create(['position' => 1]);

        $this->actingAs($this->owner)->postJson(route('forms.visualize.charts.update', [$this->form, $source]), ['question' => 'source', 'visual' => 'donut'])->assertOk();
        $this->assertSame(['question' => 'source', 'visual' => 'donut', 'sort' => 'form', 'limit' => 0], $source->fresh()->rules);

        $this->actingAs($this->owner)->postJson(route('forms.visualize.charts.destroy', [$this->form, $uses]))->assertOk();
        $this->assertSame([$source->id], $tab->charts()->pluck('id')->all());
    }

    public function test_an_editor_adds_a_grouped_chart_with_its_rules_cleaned(): void
    {
        $this->form->update(['content' => ['pages' => [['elements' => [
            ['type' => 'text', 'name' => 'school', 'title' => 'School'],
            ['type' => 'text', 'name' => 'computers', 'title' => 'Computers', 'inputType' => 'number'],
            ['type' => 'text', 'name' => 'printers', 'title' => 'Printers', 'inputType' => 'number'],
            ['type' => 'boolean', 'name' => 'internet', 'title' => 'Internet'],
            ['type' => 'comment', 'name' => 'notes', 'title' => 'Notes'],
        ]]]]]);
        $tab = $this->tab();

        $this->actingAs($this->owner)->postJson(route('forms.visualize.charts.store', [$this->form, $tab]), [
            'kind' => 'grouped', 'label' => 'school', 'mode' => 'numbers', 'visual' => 'stacked',
            'series' => [['question' => 'computers', 'aggregate' => 'sum'], ['question' => 'printers', 'aggregate' => 'avg'], ['question' => 'internet', 'aggregate' => 'sum'], ['question' => 'computers', 'aggregate' => 'sum']],
        ])->assertOk();

        // a yes / no isn't a number, and the repeated row goes
        $this->assertSame([
            'label' => 'school', 'mode' => 'numbers',
            'series' => [['question' => 'computers', 'aggregate' => 'sum'], ['question' => 'printers', 'aggregate' => 'avg']],
            'split' => null, 'visual' => 'stacked', 'sort' => 'desc', 'limit' => 0,
        ], $tab->charts()->firstOrFail()->rules);

        // a long comment can't group; a split needs a choice question other than the label; numbers need a number
        $this->actingAs($this->owner)->postJson(route('forms.visualize.charts.store', [$this->form, $tab]), ['kind' => 'grouped', 'label' => 'notes', 'series' => [['question' => 'computers']]])->assertJsonValidationErrors('label');
        $this->actingAs($this->owner)->postJson(route('forms.visualize.charts.store', [$this->form, $tab]), ['kind' => 'grouped', 'label' => 'internet', 'mode' => 'split', 'split' => 'internet'])->assertJsonValidationErrors('split');
        $this->actingAs($this->owner)->postJson(route('forms.visualize.charts.store', [$this->form, $tab]), ['kind' => 'grouped', 'label' => 'school', 'series' => [['question' => 'notes']]])->assertJsonValidationErrors('series');
        $this->assertSame(1, $tab->charts()->count());
    }

    public function test_an_editor_duplicates_a_chart(): void
    {
        $tab = $this->tab();
        $chart = VisualizationChart::factory()->on($tab)->question('source', ['visual' => 'donut'])->create(['title' => 'Water']);

        $this->actingAs($this->owner)->postJson(route('forms.visualize.charts.duplicate', [$this->form, $chart]))->assertOk();

        $charts = $tab->charts()->get();
        $this->assertCount(2, $charts);
        $this->assertNotSame($chart->hash, $charts[1]->hash);
        $this->assertSame(['Water', $chart->rules], [$charts[1]->title, $charts[1]->rules]);
    }

    public function test_an_editor_duplicates_a_tab_with_its_charts_right_after_it(): void
    {
        $first = $this->tab(['name' => 'Equipment', 'position' => 0]);
        $this->tab(['name' => 'People', 'position' => 1]);
        VisualizationChart::factory()->on($first)->question('source')->create();
        VisualizationChart::factory()->on($first)->question('uses')->create(['position' => 1]);

        $this->actingAs($this->owner)->postJson(route('forms.visualize.tabs.duplicate', [$this->form, $first]))->assertOk();

        $this->assertSame(['Equipment', 'Equipment (copy)', 'People'], $this->form->visualizationTabs()->pluck('name')->all());
        $copy = $this->form->visualizationTabs()->where('name', 'Equipment (copy)')->firstOrFail();
        $this->assertSame(['source', 'uses'], $copy->charts()->get()->pluck('rules.question')->all());
        // the original keeps its own
        $this->assertSame(2, $first->charts()->count());
    }

    public function test_viewers_see_tabs_but_cant_change_them(): void
    {
        $viewer = $this->viewer();
        $tab = $this->tab(['name' => 'Equipment']);
        $chart = VisualizationChart::factory()->on($tab)->question('source')->create();

        $this->actingAs($viewer)->get(route('forms.responses.visualize', $this->form))->assertOk()->assertSee('Equipment')->assertDontSee('Add chart')->assertDontSee('Chart options');

        $this->actingAs($viewer)->postJson(route('forms.visualize.tabs.store', $this->form), ['name' => 'Mine'])->assertForbidden();
        $this->actingAs($viewer)->postJson(route('forms.visualize.tabs.update', [$this->form, $tab]), ['name' => 'Mine'])->assertForbidden();
        $this->actingAs($viewer)->postJson(route('forms.visualize.tabs.destroy', [$this->form, $tab]))->assertForbidden();
        $this->actingAs($viewer)->postJson(route('forms.visualize.charts.store', [$this->form, $tab]), ['question' => 'source'])->assertForbidden();
        $this->actingAs($viewer)->postJson(route('forms.visualize.charts.destroy', [$this->form, $chart]))->assertForbidden();
        $this->actingAs($viewer)->postJson(route('forms.visualize.charts.duplicate', [$this->form, $chart]))->assertForbidden();
        $this->actingAs($viewer)->postJson(route('forms.visualize.tabs.duplicate', [$this->form, $tab]))->assertForbidden();
        $this->assertDatabaseCount('visualization_tabs', 1);
        $this->assertDatabaseCount('visualization_charts', 1);
    }

    public function test_a_tab_or_chart_is_only_found_through_its_own_form(): void
    {
        $other = Form::factory()->in($this->workspace)->ownedBy($this->owner)->create(['content' => $this->form->content]);
        $tab = $this->tab();
        $chart = VisualizationChart::factory()->on($tab)->question('source')->create();

        $this->actingAs($this->owner)->postJson(route('forms.visualize.tabs.update', [$other, $tab]), ['name' => 'X'])->assertNotFound();
        $this->actingAs($this->owner)->postJson(route('forms.visualize.charts.destroy', [$other, $chart]))->assertNotFound();
        $this->actingAs($this->owner)->postJson(route('forms.visualize.charts.store', [$other, $tab]), ['question' => 'source'])->assertNotFound();
    }

    public function test_another_workspace_cant_reach_them(): void
    {
        $tab = $this->tab();
        $outsider = User::factory()->create();
        $otherWorkspace = Workspace::factory()->create();
        $otherWorkspace->addMember($outsider, WorkspaceRole::Owner);
        $outsider->switchWorkspace($otherWorkspace);

        $this->actingAs($outsider)->postJson(route('forms.visualize.tabs.update', [$this->form, $tab]), ['name' => 'X'])->assertNotFound();
        $this->actingAs($outsider)->get(route('forms.responses.visualize', $this->form))->assertNotFound();
    }
}
