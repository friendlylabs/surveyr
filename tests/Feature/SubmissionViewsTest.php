<?php

namespace Tests\Feature;

use App\Enums\SpaceRole;
use App\Enums\WorkspaceRole;
use App\Models\Form;
use App\Models\Space;
use App\Models\Submission;
use App\Models\SubmissionView;
use App\Models\User;
use App\Models\Workspace;
use App\Support\SubmissionFilters;
use App\Support\SurveyAnswers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SubmissionViewsTest
 *
 * The responses table's filters (App\Support\SubmissionFilters: text, number, choice and empty rules, a date range,
 * same-question rules as alternatives) and saved views (SubmissionViewsController): saving, sharing, who changes them,
 * and the overview's list with counts.
 */
class SubmissionViewsTest extends TestCase
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
            ['type' => 'text', 'name' => 'size', 'title' => 'Household size', 'inputType' => 'number'],
            ['type' => 'radiogroup', 'name' => 'source', 'title' => 'Source', 'choices' => [['value' => 'bh', 'text' => 'Borehole'], ['value' => 'rv', 'text' => 'River']]],
            ['type' => 'checkbox', 'name' => 'uses', 'title' => 'Uses', 'choices' => ['drinking', 'washing', 'farming']],
            ['type' => 'boolean', 'name' => 'treated', 'title' => 'Treated'],
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

    /** A response with these answers. */
    private function respond(array $data, array $attributes = []): Submission
    {
        return Submission::factory()->to($this->form)->create(['data' => $data] + $attributes);
    }

    /** The references the filtered table shows. */
    private function shown(array $filters): array
    {
        $encoded = strtr(base64_encode(json_encode($filters)), '+/', '-_');
        $html = $this->actingAs($this->owner)->get(route('forms.responses', [$this->form, 'filters' => $encoded]))->assertOk()->getContent();
        preg_match_all('/RSP-\d+/', $html, $matches);

        return array_values(array_unique($matches[0]));
    }

    private function rule(string $question, string $operator, mixed $value = null): array
    {
        return compact('question', 'operator', 'value');
    }

    public function test_text_rules_match_regardless_of_case(): void
    {
        $this->respond(['village' => 'Kongwa']);    // RSP-1
        $this->respond(['village' => 'Bahi']);      // RSP-2
        $this->respond(['notes' => 'no village']);  // RSP-3

        $this->assertSame(['RSP-1'], $this->shown(['rules' => [$this->rule('village', 'contains', 'KONG')]]));
        $this->assertSame(['RSP-2'], $this->shown(['rules' => [$this->rule('village', 'is', 'bahi')]]));
        $this->assertSame(['RSP-3', 'RSP-2'], $this->shown(['rules' => [$this->rule('village', 'notContains', 'kong')]]));
        $this->assertSame([], $this->shown(['rules' => [$this->rule('village', 'contains', '%')]]), 'a % is matched literally, not as a wildcard');
    }

    public function test_number_rules_compare_numerically(): void
    {
        $this->respond(['size' => 3]);
        $this->respond(['size' => 12]);
        $this->respond(['size' => 7]);

        $this->assertSame(['RSP-2'], $this->shown(['rules' => [$this->rule('size', 'gt', 9)]]));
        $this->assertSame(['RSP-3', 'RSP-1'], $this->shown(['rules' => [$this->rule('size', 'between', ['min' => 2, 'max' => 7])]]));
        $this->assertSame(['RSP-3', 'RSP-2'], $this->shown(['rules' => [$this->rule('size', 'between', ['min' => 5, 'max' => null])]]));
    }

    public function test_choice_rules_match_single_and_multiple_answers_and_yes_no(): void
    {
        $this->respond(['source' => 'bh', 'uses' => ['drinking', 'washing'], 'treated' => true]);
        $this->respond(['source' => 'rv', 'uses' => ['farming'], 'treated' => false]);
        $this->respond(['notes' => 'skipped']);

        $this->assertSame(['RSP-1'], $this->shown(['rules' => [$this->rule('source', 'in', ['bh'])]]));
        $this->assertSame(['RSP-2', 'RSP-1'], $this->shown(['rules' => [$this->rule('uses', 'in', ['washing', 'farming'])]]));
        $this->assertSame(['RSP-3', 'RSP-2'], $this->shown(['rules' => [$this->rule('uses', 'notIn', ['drinking'])]]));
        $this->assertSame(['RSP-1'], $this->shown(['rules' => [$this->rule('treated', 'in', ['true'])]]));
        $this->assertSame(['RSP-2'], $this->shown(['rules' => [$this->rule('treated', 'in', ['false'])]]));
    }

    public function test_empty_and_answered_rules(): void
    {
        $this->respond(['village' => 'Kongwa']);
        $this->respond(['village' => '', 'uses' => []]);
        $this->respond([]);

        $this->assertSame(['RSP-1'], $this->shown(['rules' => [$this->rule('village', 'notEmpty')]]));
        $this->assertSame(['RSP-3', 'RSP-2'], $this->shown(['rules' => [$this->rule('village', 'empty')]]));
        $this->assertSame(['RSP-3', 'RSP-2', 'RSP-1'], $this->shown(['rules' => [$this->rule('uses', 'empty')]]));
    }

    public function test_rules_on_one_question_are_alternatives_and_questions_must_all_match(): void
    {
        $this->respond(['village' => 'Kongwa', 'source' => 'bh']);
        $this->respond(['village' => 'Bahi', 'source' => 'bh']);
        $this->respond(['village' => 'Kongwa', 'source' => 'rv']);

        $this->assertSame(['RSP-2', 'RSP-1'], $this->shown(['rules' => [$this->rule('village', 'is', 'kongwa'), $this->rule('village', 'is', 'bahi'), $this->rule('source', 'in', ['bh'])]]));
    }

    public function test_the_date_range_narrows_by_when_responses_came_in(): void
    {
        $this->respond(['village' => 'Old'], ['created_at' => '2026-10-02 10:00:00']);
        $this->respond(['village' => 'New'], ['created_at' => '2026-11-20 10:00:00']);

        $this->assertSame(['RSP-2'], $this->shown(['from' => '2026-11-01']));
        $this->assertSame(['RSP-1'], $this->shown(['from' => '2026-10-01', 'to' => '2026-10-31']));
    }

    public function test_rules_that_dont_fit_are_dropped(): void
    {
        $fields = SubmissionFilters::fields(SurveyAnswers::questions($this->form->content));

        $filters = SubmissionFilters::normalise(['from' => 'yesterday', 'rules' => [
            $this->rule('nope', 'contains', 'x'),          // no such question
            $this->rule('village', 'gt', 3),               // not a text operator
            $this->rule('source', 'in', ['lake']),         // not one of its choices
            $this->rule('size', 'gt', 'many'),             // not a number
            $this->rule('village', 'contains', '  '),      // nothing to look for
            $this->rule('village', 'contains', 'kon'),
        ]], $fields);

        $this->assertSame(['from' => null, 'to' => null, 'rules' => [$this->rule('village', 'contains', 'kon')]], $filters);
        $this->assertSame(1, SubmissionFilters::count($filters));
    }

    public function test_a_saved_view_brings_its_filters_back_for_everyone_on_the_form(): void
    {
        $this->respond(['village' => 'Kongwa']);
        $this->respond(['village' => 'Bahi']);

        $this->actingAs($this->owner)->postJson(route('forms.views.store', $this->form), [
            'name' => 'Kongwa only',
            'filters' => json_encode(['rules' => [$this->rule('village', 'contains', 'kongwa'), $this->rule('ghost', 'is', 'x')]]),
        ])->assertOk()->assertJson(['success' => true]);

        $view = SubmissionView::withoutGlobalScope('workspace')->firstOrFail();
        $this->assertSame([$this->rule('village', 'contains', 'kongwa')], $view->filters['rules'], 'saved cleaned');
        $this->assertSame($this->owner->id, $view->user_id);

        // a space viewer opens it too
        $viewer = $this->member();
        $space = Space::factory()->in($this->workspace)->withMember($viewer, SpaceRole::Viewer)->create();
        $this->form->spaces()->attach($space);

        $this->actingAs($viewer)->get(route('forms.responses', [$this->form, 'view' => $view->hash]))->assertOk()
            ->assertSee('Kongwa')->assertDontSee('Bahi')->assertSee('Kongwa only');

        // with the view open, an empty set of panel filters shows everything (it doesn't fall back to the view's)
        $this->get(route('forms.responses', [$this->form, 'view' => $view->hash, 'filters' => strtr(base64_encode(json_encode(['rules' => []])), '+/', '-_')]))
            ->assertSee('Bahi');
    }

    public function test_only_its_author_and_form_managers_change_a_view(): void
    {
        $view = SubmissionView::factory()->of($this->form)->create(['user_id' => $this->owner->id, 'name' => 'Mine']);
        $viewer = $this->member();
        $space = Space::factory()->in($this->workspace)->withMember($viewer, SpaceRole::Viewer)->create();
        $this->form->spaces()->attach($space);
        $filters = json_encode(['rules' => []]);

        // viewers save views of their own, but not over someone else's
        $this->actingAs($viewer)->postJson(route('forms.views.store', $this->form), ['name' => 'Theirs', 'filters' => $filters])->assertOk();
        $this->postJson(route('forms.views.update', [$this->form, $view]), ['name' => 'Hijacked', 'filters' => $filters])->assertForbidden();
        $this->postJson(route('forms.views.destroy', [$this->form, $view]))->assertForbidden();

        $theirs = SubmissionView::withoutGlobalScope('workspace')->where('name', 'Theirs')->firstOrFail();
        $this->postJson(route('forms.views.update', [$this->form, $theirs]), ['filters' => $filters])->assertOk();
        $this->assertSame('Theirs', $theirs->fresh()->name, 'saving filters into a view keeps its name');

        // the owner manages the form, so tidies anyone's views
        $this->actingAs($this->owner)->postJson(route('forms.views.destroy', [$this->form, $theirs]))->assertOk();
        $this->assertModelMissing($theirs);
        $this->assertSame('Mine', $view->fresh()->name);

        // outsiders can't save any
        $this->actingAs($this->member())->postJson(route('forms.views.store', $this->form), ['name' => 'X', 'filters' => $filters])->assertForbidden();
    }

    public function test_a_view_is_only_found_through_its_own_form(): void
    {
        $other = Form::factory()->in($this->workspace)->ownedBy($this->owner)->create();
        $view = SubmissionView::factory()->of($other)->create(['user_id' => $this->owner->id]);

        $this->actingAs($this->owner)->postJson(route('forms.views.destroy', [$this->form, $view]))->assertNotFound();
    }

    public function test_the_overview_lists_views_with_their_counts_and_rules(): void
    {
        $this->respond(['village' => 'Kongwa']);
        $this->respond(['village' => 'Kongwa', 'source' => 'bh']);
        $this->respond(['village' => 'Bahi']);
        SubmissionView::factory()->of($this->form)->create(['user_id' => $this->owner->id, 'name' => 'Kongwa homes', 'filters' => ['rules' => [$this->rule('village', 'contains', 'kongwa')]]]);

        $this->actingAs($this->owner)->get(route('forms.show', [$this->form, 'tab' => 'submissions']))->assertOk()
            ->assertSee('Kongwa homes')
            ->assertSee('Village contains “kongwa”')
            ->assertSeeInOrder(['Kongwa homes', '>2<'], false)
            ->assertSee(route('forms.responses', [$this->form, 'filter' => 1]), false);
    }
}
