<?php

namespace Tests\Feature;

use App\Enums\SpaceRole;
use App\Enums\WorkspaceRole;
use App\Models\Form;
use App\Models\Space;
use App\Models\Submission;
use App\Models\SubmissionView;
use App\Models\User;
use App\Models\VisualizationChart;
use App\Models\VisualizationTab;
use App\Models\Workspace;
use App\Support\Analytics\ChartRules;
use App\Support\Analytics\Questions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * ResponsesVisualizeTest
 *
 * Visualize (ResponsesVisualizeController, App\Support\Analytics): who sees it, that it starts empty and shows each
 * tab's own charts with their rules, that it counts the same responses as the responses table (views, filters, search,
 * archived), each question kind's summary, and the paged Other texts.
 */
class ResponsesVisualizeTest extends TestCase
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
            ['type' => 'text', 'name' => 'village', 'title' => 'Village'],
            ['type' => 'radiogroup', 'name' => 'source', 'title' => 'Water source', 'showOtherItem' => true, 'showNoneItem' => true,
                'choices' => [['value' => 'bh', 'text' => 'Borehole'], ['value' => 'rv', 'text' => 'River']]],
            ['type' => 'checkbox', 'name' => 'uses', 'title' => 'Uses', 'choices' => ['drinking', 'washing', 'farming']],
            ['type' => 'boolean', 'name' => 'treated', 'title' => 'Treated', 'labelTrue' => 'Treated', 'labelFalse' => 'Untreated'],
            ['type' => 'rating', 'name' => 'service', 'title' => 'Service', 'rateMax' => 5],
            ['type' => 'rating', 'name' => 'recommend', 'title' => 'Recommend', 'rateMin' => 0, 'rateMax' => 10],
            ['type' => 'panel', 'name' => 'household', 'elements' => [
                ['type' => 'text', 'name' => 'size', 'title' => 'Household size', 'inputType' => 'number'],
            ]],
            ['type' => 'ranking', 'name' => 'priorities', 'title' => 'Priorities', 'choices' => ['water', 'roads', 'schools']],
            ['type' => 'matrix', 'name' => 'quality', 'title' => 'Quality', 'rows' => ['taste', 'smell'], 'columns' => ['good', 'bad']],
            ['type' => 'comment', 'name' => 'notes', 'title' => 'Notes'],
            ['type' => 'file', 'name' => 'photo', 'title' => 'Photo'],
            ['type' => 'signaturepad', 'name' => 'signature', 'title' => 'Signature'],
            ['type' => 'paneldynamic', 'name' => 'members', 'title' => 'Members', 'templateElements' => [['type' => 'text', 'name' => 'age']]],
            ['type' => 'html', 'name' => 'intro', 'html' => '<p>Hi</p>'],
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

    /** A response with these answers. */
    private function respond(array $data, array $attributes = []): Submission
    {
        return Submission::factory()->to($this->form)->create(['data' => $data] + $attributes);
    }

    /** The Visualize page, with this query string. */
    private function visualize(array $query = [], ?User $user = null): TestResponse
    {
        return $this->actingAs($user ?? $this->owner)->get(route('forms.responses.visualize', [$this->form, ...$query]));
    }

    /** A tab holding a chart of every question the form can chart, in the form's order (made once). */
    private function chartEverything(): VisualizationTab
    {
        $tab = $this->form->visualizationTabs()->first();
        if ($tab) {
            return $tab;
        }

        $tab = VisualizationTab::factory()->of($this->form)->create(['name' => 'Everything']);
        foreach (array_keys(ChartRules::chartable(Questions::from($this->form->content))) as $position => $question) {
            VisualizationChart::factory()->on($tab)->question($question)->create(['position' => $position]);
        }

        return $tab;
    }

    /**
     * The open tab's summaries, by question name (charting every question first).
     *
     * @return array<string, array<string, mixed>>
     */
    private function summaries(array $query = []): array
    {
        $this->chartEverything();

        return collect($this->visualize($query)->assertOk()->viewData('summaries'))->keyBy('question')->all();
    }

    /**
     * A summary's choices as label => count.
     *
     * @return array<string, int>
     */
    private function counts(array $summary): array
    {
        return array_column($summary['categories'], 'count', 'label');
    }

    private function encode(array $filters): string
    {
        return strtr(base64_encode(json_encode($filters)), '+/', '-_');
    }

    public function test_people_who_can_see_the_form_see_its_summary_and_others_cannot(): void
    {
        $this->respond(['source' => 'bh']);
        $this->chartEverything();

        $this->visualize()->assertOk()->assertSee('Water source');

        $viewer = $this->member();
        $space = Space::factory()->in($this->workspace)->withMember($viewer, SpaceRole::Viewer)->create();
        $this->form->spaces()->attach($space);
        $this->visualize(user: $viewer)->assertOk();

        $this->visualize(user: $this->member())->assertForbidden();

        $outsider = User::factory()->create();
        $otherWorkspace = Workspace::factory()->create();
        $otherWorkspace->addMember($outsider, WorkspaceRole::Member);
        $outsider->switchWorkspace($otherWorkspace);
        $this->visualize(user: $outsider)->assertNotFound();
    }

    public function test_visualize_starts_with_an_empty_default_tab(): void
    {
        $this->respond(['source' => 'bh']);

        $this->visualize()->assertOk()->assertSee('Default')->assertSee('No charts on this tab')->assertSee('Add chart')->assertDontSee('data-viz-card', false);
        $this->assertSame(['Default'], $this->form->visualizationTabs()->pluck('name')->all());

        // made once, whoever opens the page first
        $viewer = $this->member();
        $this->form->spaces()->attach(Space::factory()->in($this->workspace)->withMember($viewer, SpaceRole::Viewer)->create());
        $this->visualize(user: $viewer)->assertOk()->assertSee('haven&#039;t added charts to this tab yet', false)->assertDontSee('Add tab')->assertDontSee('Add chart');
        $this->assertSame(1, $this->form->visualizationTabs()->count());
    }

    public function test_empty_choices_are_left_out_of_a_chart_but_a_scale_keeps_its_steps(): void
    {
        $this->respond(['source' => 'bh', 'service' => 4]);

        $summaries = $this->summaries();

        $this->assertSame(['Borehole' => 1], $this->counts($summaries['source']));
        $this->assertSame(['1' => 0, '2' => 0, '3' => 0, '4' => 1, '5' => 0], $this->counts($summaries['service']));
    }

    public function test_a_chart_shows_its_own_rules(): void
    {
        foreach (['bh', 'bh', 'bh', 'rv', 'other', 'none'] as $source) {
            $this->respond(['source' => $source]);
        }
        $tab = VisualizationTab::factory()->of($this->form)->create();
        $chart = VisualizationChart::factory()->on($tab)->question('source', ['visual' => 'donut', 'sort' => 'asc', 'limit' => 3])->create(['title' => 'Where water comes from']);

        $summary = $this->visualize()->assertOk()->assertSee('Where water comes from')->viewData('summaries')[0];

        $this->assertSame($chart->hash, $summary['name']);
        $this->assertSame('donut', $summary['visuals'][0]);
        // least picked first, the rest added up as Other values
        $this->assertSame(['River' => 1, 'Other' => 1, 'Other values' => 4], $this->counts($summary));
    }

    /**
     * A grouped chart's summary on its own tab.
     *
     * @param  array<string, mixed>  $rules
     * @return array<string, mixed>
     */
    private function grouped(array $rules, array $query = []): array
    {
        $tab = VisualizationTab::factory()->of($this->form)->create();
        VisualizationChart::factory()->on($tab)->create(['kind' => 'grouped', 'rules' => [
            'mode' => 'numbers', 'series' => [], 'split' => null, 'visual' => null, 'sort' => 'desc', 'limit' => 10, ...$rules,
        ]]);

        return $this->visualize([...$query, 'tab' => $tab->hash])->assertOk()->viewData('summaries')[0];
    }

    /**
     * A grouped summary's groups as label => values.
     *
     * @return array<string, list<int|float|null>>
     */
    private function groupValues(array $summary): array
    {
        return array_column($summary['groups'], 'values', 'label');
    }

    public function test_numbers_are_worked_out_per_group(): void
    {
        $this->respond(['source' => 'bh', 'size' => 4, 'service' => 5]);
        $this->respond(['source' => 'bh', 'size' => 6, 'service' => 3]);
        $this->respond(['source' => 'rv', 'size' => 9]);
        $this->respond(['source' => 'rv', 'size' => 'many']);
        $this->respond(['size' => 3]);

        $summary = $this->grouped(['label' => 'source', 'series' => [
            ['question' => 'size', 'aggregate' => 'sum'],
            ['question' => 'service', 'aggregate' => 'avg'],
            ['question' => 'size', 'aggregate' => 'max'],
        ]]);

        $this->assertSame('grouped', $summary['kind']);
        $this->assertSame('Household size, Service by Water source', $summary['title']);
        $this->assertSame(['Borehole' => [10, 4.0, 6], 'River' => [9, null, 9]], $this->groupValues($summary));
        $this->assertSame(['Household size (total)', 'Service (average)', 'Household size (largest)'], array_column($summary['series'], 'label'));
        $this->assertSame(1, $summary['unreadableValues']);
        $this->assertSame([4, 1], [$summary['answered'], $summary['skipped']]);
    }

    public function test_free_text_groups_match_whatever_their_case_and_every_group_shows(): void
    {
        foreach (['Kongwa', 'kongwa ', 'KONGWA', 'Bahi', 'Bahi', 'Mpwapwa'] as $village) {
            $this->respond(['village' => $village, 'size' => 2]);
        }

        // a limit saved with an older chart doesn't hold any more
        $summary = $this->grouped(['label' => 'village', 'series' => [['question' => 'size', 'aggregate' => 'count']], 'limit' => 2]);

        // largest first, each written as it was first
        $this->assertSame(['Kongwa' => [3], 'Bahi' => [2], 'Mpwapwa' => [1]], $this->groupValues($summary));
    }

    public function test_a_choice_question_splits_each_group(): void
    {
        $this->respond(['source' => 'bh', 'treated' => true]);
        $this->respond(['source' => 'bh', 'treated' => false]);
        $this->respond(['source' => 'bh', 'treated' => true]);
        $this->respond(['source' => 'rv', 'treated' => false]);

        $summary = $this->grouped(['label' => 'source', 'mode' => 'split', 'split' => 'treated']);

        $this->assertSame(['Treated', 'Untreated'], array_column($summary['series'], 'label'));
        $this->assertSame(['Borehole' => [2, 1], 'River' => [0, 1]], $this->groupValues($summary));
        $this->assertSame('stacked', $summary['visuals'][0]);
    }

    public function test_a_grouped_chart_follows_the_filters(): void
    {
        $this->respond(['source' => 'bh', 'size' => 4, 'treated' => true]);
        $this->respond(['source' => 'bh', 'size' => 6, 'treated' => false]);

        $filters = strtr(base64_encode(json_encode(['rules' => [['question' => 'treated', 'operator' => 'in', 'value' => ['true']]]])), '+/', '-_');
        $summary = $this->grouped(['label' => 'source', 'series' => [['question' => 'size', 'aggregate' => 'sum']]], ['filters' => $filters]);

        $this->assertSame(['Borehole' => [4]], $this->groupValues($summary));
    }

    public function test_tabs_hold_their_own_charts(): void
    {
        $this->respond(['source' => 'bh', 'treated' => true]);
        $first = VisualizationTab::factory()->of($this->form)->create(['position' => 0]);
        $second = VisualizationTab::factory()->of($this->form)->create(['position' => 1]);
        VisualizationChart::factory()->on($first)->question('source')->create();
        VisualizationChart::factory()->on($second)->question('treated')->create();
        VisualizationChart::factory()->on($second)->question('source', ['visual' => 'donut'])->create(['position' => 1]);

        $this->assertSame(['source'], array_column($this->visualize()->viewData('summaries'), 'question'));
        $this->assertSame(['treated', 'source'], array_column($this->visualize(['tab' => $second->hash])->viewData('summaries'), 'question'));
    }

    public function test_a_chart_whose_question_is_gone_says_so(): void
    {
        $tab = VisualizationTab::factory()->of($this->form)->create();
        VisualizationChart::factory()->on($tab)->question('removed')->create();

        $this->visualize()->assertOk()->assertSee('no longer in the form');
    }

    public function test_the_responses_page_links_to_visualize_with_its_view_and_filters(): void
    {
        $view = SubmissionView::factory()->for($this->form)->create(['filters' => ['from' => null, 'to' => null, 'rules' => []]]);
        $this->respond(['source' => 'bh']);

        $this->actingAs($this->owner)->get(route('forms.responses', [$this->form, 'view' => $view->hash, 'q' => 'bh']))
            ->assertOk()
            ->assertSee(route('forms.responses.visualize', [$this->form, 'view' => $view->hash, 'q' => 'bh']));
    }

    public function test_it_counts_the_responses_the_view_filters_search_and_archive_pick(): void
    {
        $this->respond(['source' => 'bh', 'notes' => 'needs a pump']);
        $this->respond(['source' => 'bh']);
        $this->respond(['source' => 'rv']);
        $this->respond(['source' => 'rv'], ['archived_at' => now()]);

        $this->assertSame(3, $this->summaries()['source']['total']);
        $this->assertSame(1, $this->summaries(['archived' => 1])['source']['total']);
        $this->assertSame(1, $this->summaries(['q' => 'pump'])['source']['total']);

        $filters = ['rules' => [['question' => 'source', 'operator' => 'in', 'value' => ['bh']]]];
        $this->assertSame(['Borehole' => 2], $this->counts($this->summaries(['filters' => $this->encode($filters)])['source']));

        $view = SubmissionView::factory()->for($this->form)->create(['filters' => ['from' => null, 'to' => null, 'rules' => [['question' => 'source', 'operator' => 'in', 'value' => ['rv']]]]]);
        $this->assertSame(1, $this->summaries(['view' => $view->hash])['source']['total']);
    }

    public function test_a_choice_counts_each_pick_with_other_none_and_unlisted_values(): void
    {
        $this->respond(['source' => 'bh']);
        $this->respond(['source' => 'bh']);
        $this->respond(['source' => 'other', 'source-Comment' => 'Rain tank']);
        $this->respond(['source' => 'none']);
        $this->respond(['source' => 'well']);
        $this->respond(['source' => ['bh']]);
        $this->respond([]);

        $source = $this->summaries()['source'];

        $this->assertSame(['Borehole' => 2, 'Other' => 2, 'None' => 1], $this->counts($source));
        $this->assertSame(40.0, $source['categories'][0]['percent']);
        $this->assertSame([7, 5, 1, 1], [$source['total'], $source['answered'], $source['skipped'], $source['unreadable']]);
        // the typed Other text, and the unlisted value kept in the answer
        $this->assertSame(2, $source['otherAnswers']);
    }

    public function test_choices_from_a_web_service_are_counted_by_their_own_values(): void
    {
        $this->form->update(['content' => ['pages' => [['elements' => [
            ['type' => 'dropdown', 'name' => 'country', 'choicesByUrl' => ['url' => 'https://example.test/countries']],
        ]]]]]);
        $this->respond(['country' => 'TZ']);
        $this->respond(['country' => 'TZ']);
        $this->respond(['country' => 'KE']);

        $this->assertSame(['TZ' => 2, 'KE' => 1], $this->counts($this->summaries()['country']));
    }

    public function test_several_picks_are_shares_of_those_who_answered(): void
    {
        $this->respond(['uses' => ['drinking', 'washing']]);
        $this->respond(['uses' => ['drinking']]);

        $uses = $this->summaries()['uses'];

        $this->assertTrue($uses['multiple']);
        $this->assertSame(['drinking' => 2, 'washing' => 1], $this->counts($uses));
        $this->assertSame([100.0, 50.0], array_column($uses['categories'], 'percent'));
    }

    public function test_a_yes_no_question_uses_its_own_labels(): void
    {
        $this->respond(['treated' => true]);
        $this->respond(['treated' => false]);
        $this->respond(['treated' => false]);

        $this->assertSame(['Treated' => 1, 'Untreated' => 2], $this->counts($this->summaries()['treated']));
    }

    public function test_a_rating_lists_every_step_and_averages_them(): void
    {
        $this->respond(['service' => 5]);
        $this->respond(['service' => 4]);
        $this->respond(['service' => 2]);

        $summaries = $this->summaries();
        $service = $summaries['service'];

        $this->assertSame('rating', $service['kind']);
        // ordered steps default to a line; unordered choices never offer one
        $this->assertSame(['line', 'bar', 'table'], $service['visuals']);
        $this->assertNotContains('line', $summaries['source']['visuals'] ?? []);
        $this->assertSame(['1' => 0, '2' => 1, '3' => 0, '4' => 1, '5' => 1], $this->counts($service));
        $this->assertSame([3.67, 4.0, 2, 5], [$service['stats']['mean'], $service['stats']['median'], $service['stats']['min'], $service['stats']['max']]);
    }

    public function test_a_zero_to_ten_rating_gets_a_net_promoter_score(): void
    {
        foreach ([10, 9, 9, 8, 3] as $score) {
            $this->respond(['recommend' => $score]);
        }

        $recommend = $this->summaries()['recommend'];

        $this->assertSame('nps', $recommend['kind']);
        // 3 promoters, 1 passive, 1 detractor of 5: 60% - 20%
        $this->assertSame(40, $recommend['nps']['score']);
        $this->assertSame(60.0, $recommend['nps']['promoters']['percent']);
    }

    public function test_a_number_is_summarised_and_its_spread_binned(): void
    {
        foreach ([2, 4, 4, 7, 'many'] as $size) {
            $this->respond(['size' => $size]);
        }

        $size = $this->summaries()['size'];

        $this->assertSame('number', $size['kind']);
        $this->assertSame(['count' => 4, 'mean' => 4.25, 'median' => 4.0, 'min' => 2, 'max' => 7], $size['stats']);
        $this->assertSame(1, $size['unreadable']);
        $this->assertSame(['2' => 1, '3' => 0, '4' => 2, '5' => 0, '6' => 0, '7' => 1], $this->counts($size));
    }

    public function test_a_ranking_is_ordered_by_average_position(): void
    {
        $this->respond(['priorities' => ['water', 'roads', 'schools']]);
        $this->respond(['priorities' => ['water', 'schools', 'roads']]);
        $this->respond(['priorities' => ['roads', 'water']]);

        $ranking = $this->summaries()['priorities']['ranking'];

        $this->assertSame(['water', 'roads', 'schools'], array_column($ranking, 'value'));
        $this->assertSame([1.33, 2.0, 2.5], array_column($ranking, 'average'));
        $this->assertSame([2, 1, 0], array_column($ranking, 'first'));
        $this->assertSame([0, 0, 1], array_column($ranking, 'unranked'));
    }

    public function test_a_matrix_counts_each_row_by_column(): void
    {
        $this->respond(['quality' => ['taste' => 'good', 'smell' => 'bad']]);
        $this->respond(['quality' => ['taste' => 'good']]);

        $rows = collect($this->summaries()['quality']['rows'])->keyBy('value');

        $this->assertSame([2, 0], array_column($rows['taste']['cells'], 'count'));
        $this->assertSame([0.0, 100.0], array_column($rows['smell']['cells'], 'percent'));
        $this->visualize()->assertSee('pc-viz-heat', false);
    }

    public function test_files_and_signatures_are_counted_and_linked_by_hash(): void
    {
        $response = $this->respond([
            'photo' => [['name' => 'well.png', 'type' => 'image/png', 'content' => 'upload:abc'], ['name' => 'tank.jpg', 'type' => 'image/jpeg', 'content' => 'upload:def']],
            'signature' => 'data:image/png;base64,AAAA',
        ]);
        $this->respond([]);

        $summaries = $this->summaries();

        $this->assertSame([1, 2], [$summaries['photo']['answered'], $summaries['photo']['files']]);
        $this->assertSame(['PNG' => 1, 'JPG' => 1], $this->counts($summaries['photo']));
        $this->assertSame(1, $summaries['signature']['answered']);
        $this->visualize()
            ->assertSee(route('forms.responses.file', [$this->form, $response->hash, 'question' => 'photo', 'file' => 1]))
            ->assertDontSee('AAAA');
    }

    public function test_questions_it_cannot_break_down_are_only_counted(): void
    {
        $this->respond(['members' => [['age' => 3]]]);

        $summaries = $this->summaries();

        $this->assertSame('unsupported', $summaries['members']['kind']);
        $this->assertSame(1, $summaries['members']['answered']);
        $this->assertArrayNotHasKey('intro', $summaries);
    }

    public function test_free_text_is_not_summarised(): void
    {
        $this->respond(['notes' => 'needs a pump', 'source' => 'bh']);

        $this->assertArrayNotHasKey('notes', $this->summaries());
        $this->visualize()->assertDontSee('needs a pump');
        $this->actingAs($this->owner)->getJson(route('forms.responses.visualize.answers', [$this->form, 'question' => 'notes']))->assertUnprocessable();
    }

    public function test_other_texts_come_a_page_at_a_time_over_the_same_responses(): void
    {
        foreach (range(1, 12) as $number) {
            $this->respond(['source' => 'other', 'source-Comment' => "tank $number", 'treated' => $number <= 3], ['created_at' => now()->subMinutes(20 - $number)]);
        }

        // the first page comes with the page, newest first
        $this->chartEverything();
        $this->visualize()->assertSee('tank 12')->assertDontSee('tank 2<', false);

        $page = $this->actingAs($this->owner)->getJson(route('forms.responses.visualize.answers', [$this->form, 'question' => 'source', 'page' => 2]))
            ->assertOk()->json('answers');
        $this->assertSame(['tank 2', 'tank 1'], array_column($page['items'], 'text'));
        $this->assertSame(12, $page['total']);

        $filtered = $this->actingAs($this->owner)->getJson(route('forms.responses.visualize.answers', [
            $this->form, 'question' => 'source', 'filters' => $this->encode(['rules' => [['question' => 'treated', 'operator' => 'in', 'value' => ['true']]]]),
        ]))->assertOk()->json('answers');
        $this->assertSame(['tank 3', 'tank 2', 'tank 1'], array_column($filtered['items'], 'text'));

        $this->actingAs($this->owner)->getJson(route('forms.responses.visualize.answers', [$this->form, 'question' => 'missing']))->assertUnprocessable();
    }

    public function test_other_texts_of_a_choice_are_listed(): void
    {
        $this->respond(['source' => 'other', 'source-Comment' => 'Rain tank']);
        $this->chartEverything();

        $this->visualize()->assertSee('Other answers')->assertSee('Rain tank');
        $this->actingAs($this->owner)->getJson(route('forms.responses.visualize.answers', [$this->form, 'question' => 'source', 'other' => 1]))
            ->assertOk()->assertJsonPath('answers.items.0.text', 'Rain tank')->assertJsonPath('answers.perPage', 10);
    }

    public function test_other_text_kept_as_the_answer_itself_is_listed_too(): void
    {
        // a form that stores the Other text as the answer (storeOthersAsComment: false), and a checkbox doing the same
        $this->respond(['source' => 'Rain tank']);
        $this->respond(['source' => 'bh']);
        $this->form->update(['content' => ['pages' => [['elements' => [
            ['type' => 'checkbox', 'name' => 'uses', 'showOtherItem' => true, 'choices' => ['drinking', 'washing']],
        ]]]]]);
        $this->respond(['uses' => ['drinking', 'Brewing']]);

        $this->actingAs($this->owner)->getJson(route('forms.responses.visualize.answers', [$this->form, 'question' => 'uses']))
            ->assertOk()->assertJsonPath('answers.total', 1)->assertJsonPath('answers.items.0.text', 'Brewing');
    }

    public function test_a_view_saved_from_visualize_opens_there(): void
    {
        $this->actingAs($this->owner)->postJson(route('forms.views.store', $this->form), [
            'name' => 'Boreholes',
            'filters' => json_encode(['rules' => []]),
            'return' => 'visualize',
        ])->assertOk()->assertJsonPath('redirect', route('forms.responses.visualize', [$this->form, 'view' => SubmissionView::where('name', 'Boreholes')->firstOrFail()]));

        // from the responses table it still opens there
        $this->actingAs($this->owner)->postJson(route('forms.views.store', $this->form), ['name' => 'All', 'filters' => json_encode(['rules' => []])])
            ->assertJsonPath('redirect', route('forms.responses', [$this->form, 'view' => SubmissionView::where('name', 'All')->firstOrFail()]));
    }
}
