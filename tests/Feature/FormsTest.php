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
 * FormsTest
 *
 * The forms list and create / delete (FormsController, FormPolicy): visibility through ownership, sharing and spaces.
 */
class FormsTest extends TestCase
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

    private function form(array $attributes = []): Form
    {
        return Form::factory()->in($this->workspace)->create($attributes);
    }

    public function test_create_a_personal_form(): void
    {
        $user = $this->member();

        $this->actingAs($user)->postJson(route('forms.store'), ['title' => 'Customer survey'])->assertOk();

        $form = Form::withoutGlobalScope('workspace')->where('title', 'Customer survey')->firstOrFail();
        $this->assertSame($user->id, $form->user_id);
        $this->assertSame('customer-survey', $form->slug);
        $this->assertSame($this->workspace->id, $form->workspace_id);
    }

    public function test_a_form_goes_into_spaces_where_you_may_add_forms(): void
    {
        $user = $this->member();
        $editing = Space::factory()->in($this->workspace)->withMember($user, SpaceRole::Editor)->create();
        $viewing = Space::factory()->in($this->workspace)->withMember($user, SpaceRole::Viewer)->create();

        $this->actingAs($user)->postJson(route('forms.store'), ['title' => 'In a space', 'spaces' => [$editing->hash]])->assertOk();
        $this->assertTrue(Form::withoutGlobalScope('workspace')->where('title', 'In a space')->firstOrFail()->spaces->contains($editing));

        $this->actingAs($user)->postJson(route('forms.store'), ['title' => 'Not allowed', 'spaces' => [$viewing->hash]])->assertForbidden();
    }

    public function test_the_list_shows_own_shared_and_space_forms_only(): void
    {
        $user = $this->member();
        $this->form(['title' => 'My form', 'user_id' => $user->id]);
        $this->form(['title' => 'Shared form'])->collaborators()->attach($user);
        $space = Space::factory()->in($this->workspace)->withMember($user, SpaceRole::Viewer)->create();
        $this->form(['title' => 'Space form'])->spaces()->attach($space);
        $this->form(['title' => 'Someone elses form']);

        $this->actingAs($user)->get(route('forms'))->assertOk()
            ->assertSee('My form')->assertSee('Shared form')->assertSee('Space form')
            ->assertDontSee('Someone elses form');

        $this->actingAs($this->member(WorkspaceRole::Admin))->get(route('forms'))->assertSee('Someone elses form');
    }

    public function test_filters_by_search_and_status(): void
    {
        $user = $this->member(WorkspaceRole::Admin);
        $this->form(['title' => 'Alpha open']);
        $this->form(['title' => 'Beta closed', 'closes_at' => now()->subDay()]);
        $this->form(['title' => 'Gamma scheduled', 'opens_at' => now()->addDay()]);

        $this->actingAs($user)->get(route('forms', ['q' => 'alpha']))->assertSee('Alpha open')->assertDontSee('Beta closed');
        $this->actingAs($user)->get(route('forms', ['status' => 'closed']))->assertSee('Beta closed')->assertDontSee('Alpha open');
        $this->actingAs($user)->get(route('forms', ['status' => 'scheduled']))->assertSee('Gamma scheduled')->assertDontSee('Beta closed');
    }

    public function test_with_no_forms_at_all_the_search_and_filters_are_hidden(): void
    {
        $user = $this->member();

        $this->actingAs($user)->get(route('forms'))->assertOk()
            ->assertSee(__('No forms yet'))
            ->assertDontSee('data-pc-toolbar', false);
    }

    public function test_a_filter_that_matches_nothing_keeps_the_search_and_filters(): void
    {
        $user = $this->member();
        $this->form(['title' => 'Alpha', 'user_id' => $user->id]);

        $this->actingAs($user)->get(route('forms', ['q' => 'zzz']))->assertOk()
            ->assertSee(__('No forms match'))
            ->assertSee('data-pc-toolbar', false);
    }

    public function test_owners_and_space_managers_delete_but_editors_cannot(): void
    {
        $editor = $this->member();
        $manager = $this->member();
        $space = Space::factory()->in($this->workspace)->withMember($editor, SpaceRole::Editor)->withMember($manager, SpaceRole::Manager)->create();
        $form = $this->form();
        $form->spaces()->attach($space);

        $this->actingAs($editor)->postJson(route('forms.destroy', $form))->assertForbidden();
        $this->actingAs($manager)->postJson(route('forms.destroy', $form))->assertOk();
        $this->assertModelMissing($form);
    }

    public function test_delete_several_forms_at_once(): void
    {
        $user = $this->member();
        $first = $this->form(['user_id' => $user->id]);
        $second = $this->form(['user_id' => $user->id]);
        $kept = $this->form(['user_id' => $user->id]);

        $this->actingAs($user)->postJson(route('forms.destroy-many'), ['forms' => [$first->hash, $second->hash]])->assertOk();

        $this->assertModelMissing($first);
        $this->assertModelMissing($second);
        $this->assertModelExists($kept);
    }

    public function test_a_bulk_delete_with_a_form_you_cannot_delete_deletes_nothing(): void
    {
        $user = $this->member();
        $own = $this->form(['user_id' => $user->id]);
        $shared = $this->form();
        $shared->collaborators()->attach($user);

        $this->actingAs($user)->postJson(route('forms.destroy-many'), ['forms' => [$own->hash, $shared->hash]])->assertForbidden();

        $this->assertModelExists($own);
        $this->assertModelExists($shared);
    }

    public function test_only_forms_you_may_delete_get_a_checkbox(): void
    {
        $user = $this->member();
        $own = $this->form(['user_id' => $user->id]);
        $shared = $this->form();
        $shared->collaborators()->attach($user);

        $this->actingAs($user)->get(route('forms'))->assertOk()
            ->assertSee('value="'.$own->hash.'" data-pc-select', false)
            ->assertDontSee('value="'.$shared->hash.'" data-pc-select', false);
    }

    public function test_an_archived_form_leaves_the_list_until_restored(): void
    {
        $user = $this->member();
        $form = $this->form(['title' => 'Old survey', 'user_id' => $user->id]);

        $this->actingAs($user)->postJson(route('forms.archive', $form))->assertOk();
        $this->assertTrue($form->fresh()->isArchived());

        $this->get(route('forms'))->assertDontSee('Old survey');
        $this->get(route('forms', ['status' => 'archived']))->assertSee('Old survey');

        $this->postJson(route('forms.restore', $form))->assertOk();
        $this->get(route('forms'))->assertSee('Old survey');
    }

    public function test_archive_several_forms_at_once_and_restore_them(): void
    {
        $user = $this->member();
        $first = $this->form(['user_id' => $user->id]);
        $second = $this->form(['user_id' => $user->id]);

        $this->actingAs($user)->postJson(route('forms.archive-many'), ['forms' => [$first->hash, $second->hash]])->assertOk();
        $this->assertTrue($first->fresh()->isArchived() && $second->fresh()->isArchived());

        $this->postJson(route('forms.archive-many'), ['forms' => [$first->hash], 'restore' => 1])->assertOk();
        $this->assertFalse($first->fresh()->isArchived());
        $this->assertTrue($second->fresh()->isArchived());
    }

    public function test_editors_cannot_archive(): void
    {
        $editor = $this->member();
        $space = Space::factory()->in($this->workspace)->withMember($editor, SpaceRole::Editor)->create();
        $form = $this->form();
        $form->spaces()->attach($space);

        $this->actingAs($editor)->postJson(route('forms.archive', $form))->assertForbidden();
        $this->assertFalse($form->fresh()->isArchived());
    }

    public function test_space_roles_decide_viewing_and_editing(): void
    {
        $viewer = $this->member();
        $editor = $this->member();
        $space = Space::factory()->in($this->workspace)->withMember($viewer, SpaceRole::Viewer)->withMember($editor, SpaceRole::Editor)->create();
        $form = $this->form();
        $form->spaces()->attach($space);

        $this->assertTrue($viewer->can('view', $form));
        $this->assertFalse($viewer->can('update', $form));
        $this->assertTrue($editor->can('update', $form));
        $this->assertFalse($this->member()->can('view', $form));
    }

    public function test_the_strongest_role_across_spaces_counts(): void
    {
        $user = $this->member();
        $viewing = Space::factory()->in($this->workspace)->withMember($user, SpaceRole::Viewer)->create();
        $editing = Space::factory()->in($this->workspace)->withMember($user, SpaceRole::Editor)->create();
        $form = $this->form();
        $form->spaces()->attach([$viewing->id, $editing->id]);

        $this->assertSame(SpaceRole::Editor, $form->spaceRoleOf($user));
        $this->assertTrue($user->can('update', $form));
    }
}
