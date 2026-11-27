<?php

namespace Tests\Feature;

use App\Enums\SpaceRole;
use App\Enums\WorkspaceRole;
use App\Models\Form;
use App\Models\Space;
use App\Models\Submission;
use App\Models\User;
use App\Models\Workspace;
use App\Support\SurveyAnswers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * FormResponsesTest
 *
 * A form's responses in the app (FormResponsesController): the table (every question a column, sorting, search and
 * the time filter), a response in the side panel, archiving and restoring in place of deleting, purging, response
 * numbers, answers shown readably, and who collected a response and from where.
 */
class FormResponsesTest extends TestCase
{
    use RefreshDatabase;

    private Workspace $workspace;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->workspace = Workspace::factory()->create();
    }

    /** A workspace member with the given workspace role. */
    private function member(WorkspaceRole $role = WorkspaceRole::Member): User
    {
        $user = User::factory()->create();
        $this->workspace->addMember($user, $role);
        $user->switchWorkspace($this->workspace);

        return $user;
    }

    /** A form with a text question and a choice question whose values differ from their labels. */
    private function form(array $attributes = []): Form
    {
        return Form::factory()->in($this->workspace)->create($attributes + ['content' => ['pages' => [['name' => 'page1', 'elements' => [
            ['type' => 'text', 'name' => 'village', 'title' => 'Village name'],
            ['type' => 'radiogroup', 'name' => 'source', 'title' => 'Main water source', 'choices' => [['value' => 'bh', 'text' => 'Borehole'], ['value' => 'rv', 'text' => 'River']]],
        ]]]]]);
    }

    public function test_owners_see_the_responses_with_readable_answers(): void
    {
        $owner = $this->member();
        $form = $this->form(['user_id' => $owner->id]);
        Submission::factory()->to($form)->create(['data' => ['village' => 'Kongwa', 'source' => 'bh']]);

        $this->actingAs($owner)->get(route('forms.responses', $form))->assertOk()
            ->assertSee('Village name')->assertSee('Main water source')
            ->assertSee('Kongwa')->assertSee('Borehole')->assertDontSee('>bh<', false);
    }

    public function test_space_viewers_see_responses_but_cannot_archive_or_purge_them(): void
    {
        $viewer = $this->member();
        $space = Space::factory()->in($this->workspace)->withMember($viewer, SpaceRole::Viewer)->create();
        $form = $this->form();
        $form->spaces()->attach($space);
        $response = Submission::factory()->to($form)->create();

        $this->actingAs($viewer)->get(route('forms.responses', $form))->assertOk()
            ->assertDontSee(route('forms.responses.archive', $form), false)->assertDontSee(route('forms.responses.purge', $form), false);
        $this->getJson(route('forms.responses.show', [$form, $response]))->assertOk()->assertJsonPath('response.hash', $response->hash);
        $this->postJson(route('forms.responses.archive', $form), ['responses' => [$response->hash], 'archive' => 1])->assertForbidden();
        $this->postJson(route('forms.responses.purge', $form))->assertForbidden();
        $this->assertNull($response->fresh()->archived_at);

        $this->actingAs($this->member())->get(route('forms.responses', $form))->assertForbidden();
    }

    public function test_search_narrows_the_list(): void
    {
        $owner = $this->member();
        $form = $this->form(['user_id' => $owner->id]);
        Submission::factory()->to($form)->create(['data' => ['village' => 'Kongwa']]);
        Submission::factory()->to($form)->create(['data' => ['village' => 'Mpwapwa'], 'created_at' => now()->subDays(20)]);

        $this->actingAs($owner)->get(route('forms.responses', [$form, 'q' => 'kongwa']))->assertSee('Kongwa')->assertDontSee('Mpwapwa');
    }

    public function test_a_response_is_only_found_through_its_own_form(): void
    {
        $owner = $this->member();
        $form = $this->form(['user_id' => $owner->id]);
        $other = $this->form(['user_id' => $owner->id]);
        $response = Submission::factory()->to($other)->create();

        $this->actingAs($owner)->get(route('forms.responses.show', [$form, $response]))->assertNotFound();
    }

    public function test_editors_archive_responses_out_of_the_list_and_restore_them(): void
    {
        $editor = $this->member();
        $space = Space::factory()->in($this->workspace)->withMember($editor, SpaceRole::Editor)->create();
        $form = $this->form();
        $form->spaces()->attach($space);
        $kept = Submission::factory()->to($form)->create(['data' => ['village' => 'Kongwa']]);
        $archived = Submission::factory()->to($form)->create(['data' => ['village' => 'Mpwapwa']]);

        $this->actingAs($editor)->postJson(route('forms.responses.archive', $form), ['responses' => [$archived->hash], 'archive' => 1])
            ->assertOk()->assertJson(['message' => trans_choice(':count response archived|:count responses archived', 1)]);
        $this->assertNotNull($archived->fresh()->archived_at);
        $this->assertModelExists($archived);

        $this->get(route('forms.responses', $form))->assertSee('Kongwa')->assertDontSee('Mpwapwa');
        $this->get(route('forms.responses', [$form, 'archived' => 1]))->assertSee('Mpwapwa')->assertDontSee('Kongwa')->assertSee(__('Restore'));

        $this->postJson(route('forms.responses.archive', $form), ['responses' => [$archived->hash], 'archive' => 0])->assertOk();
        $this->assertNull($archived->fresh()->archived_at);
        $this->assertNull($kept->fresh()->archived_at);
    }

    public function test_archiving_only_touches_the_forms_own_responses(): void
    {
        $owner = $this->member();
        $form = $this->form(['user_id' => $owner->id]);
        $elsewhere = Submission::factory()->to($this->form())->create();

        $this->actingAs($owner)->postJson(route('forms.responses.archive', $form), ['responses' => [$elsewhere->hash], 'archive' => 1])->assertOk();
        $this->assertNull($elsewhere->fresh()->archived_at);
    }

    public function test_archived_responses_leave_the_counts_but_still_count_as_data(): void
    {
        $owner = $this->member();
        $form = $this->form(['user_id' => $owner->id]);
        Submission::factory()->to($form)->create(['archived_at' => now()]);

        $this->actingAs($owner)->get(route('forms'))->assertSee('>0<', false);
        $this->get(route('forms.show', $form))->assertSee(__('No responses yet'));
        $this->assertTrue($form->hasResponses(), 'a form with archived responses is archived, never deleted');
    }

    public function test_the_owner_purges_every_response_and_editors_cannot(): void
    {
        $owner = $this->member();
        $editor = $this->member();
        $space = Space::factory()->in($this->workspace)->withMember($editor, SpaceRole::Editor)->create();
        $form = $this->form(['user_id' => $owner->id]);
        $form->spaces()->attach($space);
        Submission::factory()->to($form)->count(2)->create();
        Submission::factory()->to($form)->create(['archived_at' => now()]);
        $elsewhere = Submission::factory()->to($this->form())->create();

        $this->actingAs($editor)->postJson(route('forms.responses.purge', $form))->assertForbidden();

        $this->actingAs($owner)->get(route('forms.responses', $form))->assertSee(route('forms.responses.purge', $form), false);
        $this->postJson(route('forms.responses.purge', $form))->assertOk()
            ->assertJson(['message' => trans_choice(':count response deleted|:count responses deleted', 3)]);
        $this->assertSame(0, $form->submissions()->count());
        $this->assertModelExists($elsewhere);
    }

    public function test_responses_are_numbered_within_their_form(): void
    {
        $form = $this->form();
        $other = $this->form();

        $this->postJson(route('public.forms.submit', $form), ['data' => ['a' => 1]])->assertOk();
        $this->postJson(route('public.forms.submit', $other), ['data' => ['a' => 1]])->assertOk();
        $this->postJson(route('public.forms.submit', $form), ['data' => ['a' => 2]])->assertOk();

        $this->assertSame([1, 2], $form->submissions()->orderBy('number')->pluck('number')->all());
        $this->assertSame('RSP-1', $other->submissions()->firstOrFail()->reference);
    }

    public function test_every_question_is_a_column_and_headings_sort_the_table(): void
    {
        $owner = $this->member();
        $form = Form::factory()->in($this->workspace)->ownedBy($owner)->create(['content' => ['pages' => [['elements' => [
            ['type' => 'text', 'name' => 'village', 'title' => 'Village name'],
            ['type' => 'rating', 'name' => 'score', 'title' => 'Score', 'rateMax' => 5],
            ['type' => 'text', 'name' => 'q5', 'title' => 'Fifth'],
            ['type' => 'text', 'name' => 'q6', 'title' => 'Sixth'],
            ['type' => 'file', 'name' => 'photo', 'title' => 'Photo'],
        ]]]]]);
        Submission::factory()->to($form)->create(['data' => ['village' => 'Bahi', 'score' => 4, 'photo' => [['name' => 'a.jpg', 'type' => 'image/jpeg'], ['name' => 'b.jpg', 'type' => 'image/jpeg']]]]);
        Submission::factory()->to($form)->create(['data' => ['village' => 'Afya', 'score' => 2]]);

        $this->actingAs($owner)->get(route('forms.responses', $form))->assertOk()
            ->assertSee('Sixth')->assertSee('Photo')
            ->assertSee('4/5')->assertSee(trans_choice(':count file|:count files', 2))
            ->assertSeeInOrder(['RSP-2', 'RSP-1']);

        $this->get(route('forms.responses', [$form, 'sort' => 'village', 'dir' => 'asc']))->assertSeeInOrder(['Afya', 'Bahi']);
        $this->get(route('forms.responses', [$form, 'sort' => 'village', 'dir' => 'desc']))->assertSeeInOrder(['Bahi', 'Afya']);
        $this->get(route('forms.responses', [$form, 'sort' => 'number', 'dir' => 'asc']))->assertSeeInOrder(['RSP-1', 'RSP-2'])->assertSee('aria-sort="ascending"', false);
        // a file question and anything that isn't a column don't sort
        $this->get(route('forms.responses', [$form, 'sort' => 'photo']))->assertOk()->assertSeeInOrder(['RSP-2', 'RSP-1']);
        $this->get(route('forms.responses', [$form, 'sort' => 'x\') or 1=1 --']))->assertOk()->assertSeeInOrder(['RSP-2', 'RSP-1']);
    }

    public function test_a_response_opens_in_the_side_panel(): void
    {
        $owner = $this->member();
        $collector = $this->member();
        $form = $this->form(['user_id' => $owner->id]);
        [$older, $response, $newer] = Submission::factory()->to($form)->count(3)->create();
        $response->forceFill(['collected_by' => $collector->id, 'ip_address' => '203.0.113.7', 'meta' => ['seconds' => 95]])->save();

        $this->actingAs($owner)->getJson(route('forms.responses.show', [$form, $response]))->assertOk()
            ->assertJsonPath('response.reference', 'RSP-2')
            ->assertJsonPath('response.collector', $collector->name)
            ->assertJsonPath('response.ip', '203.0.113.7')
            ->assertJsonPath('response.newer', $newer->hash)
            ->assertJsonPath('response.older', $older->hash)
            ->assertJsonMissingPath('response.id');

        // opened as a page: the table with the panel on it
        $this->get(route('forms.responses.show', [$form, $response]))->assertRedirect(route('forms.responses', [$form, 'response' => $response->hash]));
        $this->get(route('forms.responses', $form))->assertSee('id="responsePanel"', false)->assertSee('responsesConfig', false);
    }

    public function test_staff_who_collect_a_response_are_recorded(): void
    {
        $staff = $this->member();
        $form = $this->form();

        $this->actingAs($staff)->postJson(route('public.forms.submit', $form), ['data' => ['village' => 'Kongwa']])->assertOk();

        $response = Submission::withoutGlobalScope('workspace')->latest('id')->firstOrFail();
        $this->assertSame($staff->id, $response->collected_by);
    }

    public function test_respondents_and_outsiders_are_not_recorded(): void
    {
        $form = $this->form();

        $this->postJson(route('public.forms.submit', $form), ['data' => ['a' => 1]])->assertOk();
        $this->actingAs(User::factory()->create())->postJson(route('public.forms.submit', $form), ['data' => ['a' => 2]])->assertOk();

        $this->assertSame(0, Submission::withoutGlobalScope('workspace')->whereNotNull('collected_by')->count());
    }

    public function test_the_address_a_response_came_from_is_recorded(): void
    {
        $owner = $this->member();
        $form = $this->form(['user_id' => $owner->id]);

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.7'])->postJson(route('public.forms.submit', $form), ['data' => ['a' => 1]])->assertOk();

        $response = Submission::withoutGlobalScope('workspace')->latest('id')->firstOrFail();
        $this->assertSame('203.0.113.7', $response->ip_address);
        $this->assertArrayNotHasKey('ip_address', $response->toArray(), 'never serialised');
        $this->actingAs($owner)->getJson(route('forms.responses.show', [$form, $response]))->assertJsonPath('response.ip', '203.0.113.7');
    }

    public function test_behind_a_trusted_proxy_the_visitors_address_is_recorded(): void
    {
        $form = $this->form();
        $forwarded = ['REMOTE_ADDR' => '10.0.0.5', 'HTTP_X_FORWARDED_FOR' => '198.51.100.20'];

        // not trusted: the header is ignored, so it can't be faked
        $this->withServerVariables($forwarded)->postJson(route('public.forms.submit', $form), ['data' => ['a' => 1]])->assertOk();
        $this->assertSame('10.0.0.5', Submission::withoutGlobalScope('workspace')->latest('id')->value('ip_address'));

        config(['trustedproxy.proxies' => '10.0.0.0/8']);
        $this->withServerVariables($forwarded)->postJson(route('public.forms.submit', $form), ['data' => ['a' => 2]])->assertOk();
        $this->assertSame('198.51.100.20', Submission::withoutGlobalScope('workspace')->latest('id')->value('ip_address'));
    }

    public function test_the_list_shows_who_collected_each_response(): void
    {
        $owner = $this->member();
        $collector = $this->member();
        $form = $this->form(['user_id' => $owner->id]);
        $response = Submission::factory()->to($form)->create();
        $response->forceFill(['collected_by' => $collector->id])->save();

        $this->actingAs($owner)->get(route('forms.responses', $form))->assertSee($collector->name);
        $this->getJson(route('forms.responses.show', [$form, $response]))->assertJsonPath('response.collector', $collector->name);
    }

    public function test_the_forms_list_counts_responses(): void
    {
        $owner = $this->member();
        $form = $this->form(['user_id' => $owner->id]);
        Submission::factory()->to($form)->count(3)->create();

        $this->actingAs($owner)->get(route('forms'))->assertSee(route('forms.responses', $form), false)->assertSee('>3<', false);
    }

    public function test_answers_are_formatted_for_reading(): void
    {
        $choice = ['type' => 'checkbox', 'choices' => ['a' => 'Apples', 'b' => 'Beans']];

        $this->assertSame('Apples, Beans', SurveyAnswers::format(['a', 'b'], $choice));
        $this->assertSame(__('Yes'), SurveyAnswers::format(true));
        $this->assertSame('photo.jpg', SurveyAnswers::format([['name' => 'photo.jpg', 'type' => 'image/jpeg', 'content' => '...']]));
        $this->assertSame('', SurveyAnswers::format(null));
        $this->assertSame([['name' => 'village', 'title' => 'village', 'type' => 'text', 'kind' => 'text', 'max' => null, 'choices' => []]],
            SurveyAnswers::questions(['pages' => [['elements' => [['type' => 'panel', 'elements' => [['type' => 'text', 'name' => 'village']]], ['type' => 'html', 'name' => 'note']]]]]));
    }
}
