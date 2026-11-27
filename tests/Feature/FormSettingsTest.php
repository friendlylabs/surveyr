<?php

namespace Tests\Feature;

use App\Enums\SpaceRole;
use App\Enums\WorkspaceRole;
use App\Models\Form;
use App\Models\Space;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * FormSettingsTest
 *
 * A form's settings (FormSettingsController): the General, Access and Submission tabs, who may change what, and the
 * builder's definition kept in step.
 */
class FormSettingsTest extends TestCase
{
    use RefreshDatabase;

    private Workspace $workspace;

    protected function setUp(): void
    {
        parent::setUp();
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

    private function ownForm(User $owner, array $attributes = []): Form
    {
        return Form::factory()->in($this->workspace)->ownedBy($owner)->create($attributes);
    }

    public function test_the_settings_live_in_the_overviews_settings_tab(): void
    {
        $owner = $this->member();
        $form = $this->ownForm($owner);

        // the old address leads to the tab
        $this->actingAs($owner)->get(route('forms.settings', $form))->assertRedirect(route('forms.show', [$form, 'tab' => 'settings']));

        $this->get(route('forms.show', [$form, 'tab' => 'settings']))->assertOk()
            ->assertSee('data-pc-tab-panel="settings" data-pc-open="true"', false)
            ->assertSeeInOrder(['id="generalSettings"', 'id="accessSettings"', 'id="submissionSettings"'], false)
            ->assertSee(route('forms.settings.general', $form), false)
            ->assertSee(route('forms.settings.access', $form), false)
            ->assertSee(route('forms.settings.submission', $form), false);
    }

    public function test_general_saves_title_and_description_and_the_survey_title_follows(): void
    {
        $owner = $this->member();
        $form = $this->ownForm($owner, ['content' => ['title' => 'Old', 'pages' => []]]);

        $this->actingAs($owner)->postJson(route('forms.settings.general', $form), ['title' => 'Water access', 'description' => 'Wave 2'])->assertOk();

        $form->refresh();
        $this->assertSame('Water access', $form->title);
        $this->assertSame('Wave 2', $form->description);
        $this->assertSame('Water access', $form->content['title']);
        $this->assertSame(1, $form->revision, 'an open builder must not save over the new title');
    }

    public function test_managers_share_the_form_and_set_its_spaces(): void
    {
        $owner = $this->member();
        $colleague = $this->member();
        $space = Space::factory()->in($this->workspace)->withMember($owner, SpaceRole::Editor)->create();
        $form = $this->ownForm($owner);

        $this->actingAs($owner)->postJson(route('forms.settings.general', $form), [
            'title' => $form->title,
            'spaces' => [$space->hash],
            'collaborators' => [$colleague->hash, $owner->hash],
        ])->assertOk();

        $this->assertTrue($form->spaces()->whereKey($space->id)->exists());
        $this->assertSame([$colleague->id], $form->collaborators()->pluck('users.id')->all(), 'the owner is never a collaborator');
    }

    public function test_a_space_you_cannot_add_forms_to_keeps_the_form(): void
    {
        $owner = $this->member();
        $theirs = Space::factory()->in($this->workspace)->create();
        $form = $this->ownForm($owner);
        $form->spaces()->attach($theirs);

        $this->actingAs($owner)->postJson(route('forms.settings.general', $form), ['title' => $form->title, 'spaces' => []])->assertOk();

        $this->assertTrue($form->spaces()->whereKey($theirs->id)->exists());
    }

    public function test_editors_change_details_but_not_sharing(): void
    {
        $editor = $this->member();
        $space = Space::factory()->in($this->workspace)->withMember($editor, SpaceRole::Editor)->create();
        $form = Form::factory()->in($this->workspace)->create();
        $form->spaces()->attach($space);

        $this->actingAs($editor)->get(route('forms.show', [$form, 'tab' => 'settings']))->assertOk()->assertDontSee('formCollaborators', false);

        $this->postJson(route('forms.settings.general', $form), ['title' => 'Renamed', 'spaces' => [], 'collaborators' => [$editor->hash]])->assertOk();

        $this->assertSame('Renamed', $form->fresh()->title);
        $this->assertTrue($form->spaces()->whereKey($space->id)->exists());
        $this->assertSame(0, $form->collaborators()->count());
    }

    public function test_viewers_cannot_open_or_change_settings(): void
    {
        $viewer = $this->member();
        $space = Space::factory()->in($this->workspace)->withMember($viewer, SpaceRole::Viewer)->create();
        $form = Form::factory()->in($this->workspace)->create();
        $form->spaces()->attach($space);

        $this->actingAs($viewer)->get(route('forms.settings', $form))->assertForbidden();
        $this->postJson(route('forms.settings.access', $form), [])->assertForbidden();
    }

    public function test_access_sets_the_schedule_and_code(): void
    {
        $owner = $this->member();
        $form = $this->ownForm($owner);

        $this->actingAs($owner)->postJson(route('forms.settings.access', $form), [
            'opens_at' => '2027-01-10 08:00',
            'closes_at' => '2027-01-20 17:30',
            'access_code' => 'river42',
        ])->assertOk();

        $form->refresh();
        $this->assertNotNull($form->opens_at);
        $this->assertTrue($form->closes_at->gt($form->opens_at));
        $this->assertSame('river42', $form->access_code);
        $this->assertSame('scheduled', $form->status());

        $this->postJson(route('forms.settings.access', $form), ['opens_at' => '', 'closes_at' => '', 'access_code' => ''])->assertOk();
        $form->refresh();
        $this->assertNull($form->opens_at);
        $this->assertNull($form->closes_at);
        $this->assertNull($form->access_code);
    }

    public function test_a_closing_date_before_the_opening_is_refused(): void
    {
        $owner = $this->member();
        $form = $this->ownForm($owner);

        $this->actingAs($owner)->postJson(route('forms.settings.access', $form), ['opens_at' => '2027-01-20 08:00', 'closes_at' => '2027-01-10 08:00'])
            ->assertStatus(422)->assertJsonValidationErrors('closes_at');
    }

    public function test_submission_sets_the_redirect_in_the_survey_and_the_webhook(): void
    {
        $owner = $this->member();
        $form = $this->ownForm($owner, ['content' => ['title' => 'T', 'pages' => []]]);

        $this->actingAs($owner)->postJson(route('forms.settings.submission', $form), [
            'redirect_url' => 'https://example.org/thanks',
            'webhook_url' => 'https://hooks.example.org/surveyr',
        ])->assertOk();

        $form->refresh();
        $this->assertSame('https://example.org/thanks', $form->content['navigateToUrl']);
        $this->assertSame('https://hooks.example.org/surveyr', $form->webhook_url);

        $this->postJson(route('forms.settings.submission', $form), ['redirect_url' => '', 'webhook_url' => ''])->assertOk();
        $form->refresh();
        $this->assertArrayNotHasKey('navigateToUrl', $form->content);
        $this->assertNull($form->webhook_url);
    }

    public function test_urls_must_be_web_addresses(): void
    {
        $owner = $this->member();
        $form = $this->ownForm($owner);

        $this->actingAs($owner)->postJson(route('forms.settings.submission', $form), ['redirect_url' => 'javascript:alert(1)', 'webhook_url' => 'ftp://x'])
            ->assertStatus(422)->assertJsonValidationErrors(['redirect_url', 'webhook_url']);
    }
}
