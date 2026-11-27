<?php

namespace Tests\Feature;

use App\Enums\SpaceRole;
use App\Enums\WorkspaceRole;
use App\Models\Form;
use App\Models\Space;
use App\Models\Submission;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * FormOverviewTest
 *
 * A form's overview (FormOverviewController), the first screen when a form is opened: its stats and daily chart, the
 * tabs each role gets, and the ways in.
 */
class FormOverviewTest extends TestCase
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

    public function test_owners_see_the_stats_and_every_tab(): void
    {
        $owner = $this->member();
        $form = Form::factory()->in($this->workspace)->ownedBy($owner)->create(['title' => 'Water access']);
        Submission::factory()->to($form)->count(3)->create();
        Submission::factory()->to($form)->create(['created_at' => now()->subDays(10)]);

        $this->actingAs($owner)->get(route('forms.show', $form))->assertOk()
            ->assertSee('Water access')
            ->assertSee('data-pc-tab-panel="overview" data-pc-open="true"', false)
            ->assertSee('data-pc-tab-panel="settings"', false)
            ->assertDontSee('data-pc-tab-panel="theme"', false)
            ->assertSee(__('+:count in the last 7 days', ['count' => 3]))
            ->assertSee(__('Daily responses'))
            ->assertSee('data-pc-chart', false)
            ->assertSee('"data":[0,', false)
            ->assertDontSee(__('7-day average'));
    }

    public function test_viewers_get_the_overview_and_submissions_only(): void
    {
        $viewer = $this->member();
        $space = Space::factory()->in($this->workspace)->withMember($viewer, SpaceRole::Viewer)->create();
        $form = Form::factory()->in($this->workspace)->create();
        $form->spaces()->attach($space);

        $this->actingAs($viewer)->get(route('forms.show', [$form, 'tab' => 'settings']))->assertOk()
            ->assertSee('data-pc-tab-panel="overview" data-pc-open="true"', false)
            ->assertSee('data-pc-tab-panel="submissions"', false)
            ->assertDontSee('data-pc-tab-panel="settings"', false)
            ->assertDontSee(route('forms.build', $form), false);

        $this->actingAs($this->member())->get(route('forms.show', $form))->assertForbidden();
    }

    public function test_a_form_without_responses_shows_an_empty_chart(): void
    {
        $owner = $this->member();
        $form = Form::factory()->in($this->workspace)->ownedBy($owner)->create();

        $this->actingAs($owner)->get(route('forms.show', $form))->assertOk()
            ->assertSee(__('No responses yet'))
            ->assertDontSee('data-pc-chart', false)
            ->assertSee(route('public.forms.show', $form), false);
    }

    public function test_the_submissions_tab_leads_to_all_submissions(): void
    {
        $owner = $this->member();
        $form = Form::factory()->in($this->workspace)->ownedBy($owner)->create();
        Submission::factory()->to($form)->count(2)->create();

        $this->actingAs($owner)->get(route('forms.show', [$form, 'tab' => 'submissions']))->assertOk()
            ->assertSee('data-pc-tab-panel="submissions" data-pc-open="true"', false)
            ->assertSee(__('All submissions'))
            ->assertSee(route('forms.responses', $form), false);
    }

    public function test_the_forms_list_opens_a_form_on_its_overview(): void
    {
        $owner = $this->member();
        $form = Form::factory()->in($this->workspace)->ownedBy($owner)->create();

        $this->actingAs($owner)->get(route('forms'))->assertSee('href="'.route('forms.show', $form).'"', false);
    }

    public function test_the_share_dialog_has_the_link_and_an_embed_snippet(): void
    {
        $owner = $this->member();
        $form = Form::factory()->in($this->workspace)->ownedBy($owner)->create();

        $this->actingAs($owner)->get(route('forms.show', $form))
            ->assertSee('id="shareForm"', false)
            ->assertSee(e('<iframe src="'.route('public.forms.show', $form).'"'), false);
    }
}
