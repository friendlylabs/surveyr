<?php

namespace Tests\Feature;

use App\Enums\SpaceRole;
use App\Enums\WorkspaceRole;
use App\Models\Form;
use App\Models\Space;
use App\Models\User;
use App\Models\Workspace;
use App\Support\Workspaces;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SpacesTest
 *
 * Spaces (SpacesController, SpacePolicy): who sees and runs a space, and its members' roles.
 */
class SpacesTest extends TestCase
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

    private function space(?User $member = null, SpaceRole $role = SpaceRole::Viewer): Space
    {
        $factory = Space::factory()->in($this->workspace);

        return ($member ? $factory->withMember($member, $role) : $factory)->create();
    }

    public function test_whoever_starts_a_space_manages_it(): void
    {
        $user = $this->member();

        $this->actingAs($user)->postJson(route('spaces.store'), ['name' => 'Field research', 'color' => 'brand'])->assertOk();

        $space = Space::withoutGlobalScope('workspace')->where('name', 'Field research')->firstOrFail();
        $this->assertSame($this->workspace->id, $space->workspace_id);
        $this->assertSame(SpaceRole::Manager, $space->roleOf($user));
    }

    public function test_the_list_shows_your_spaces_and_admins_see_all(): void
    {
        $user = $this->member();
        $this->space($user)->update(['name' => 'Mine']);
        $this->space()->update(['name' => 'Elsewhere']);

        $this->actingAs($user)->get(route('spaces'))->assertOk()->assertSee('Mine')->assertDontSee('Elsewhere');
        $this->actingAs($this->member(WorkspaceRole::Admin))->get(route('spaces'))->assertSee('Mine')->assertSee('Elsewhere');
    }

    public function test_the_row_menu_offers_edit_and_delete_only_to_managers(): void
    {
        $user = $this->member();
        $managed = $this->space($user, SpaceRole::Manager);
        $viewed = $this->space($user, SpaceRole::Viewer);

        $this->actingAs($user)->get(route('spaces'))->assertOk()
            ->assertSee('spaceMenu-'.$managed->hash, false)
            ->assertSee('spaceMenu-'.$viewed->hash, false)
            ->assertSee(e(json_encode(route('spaces.update', $managed))), false)
            ->assertSee(route('spaces.destroy', $managed), false)
            ->assertDontSee(e(json_encode(route('spaces.update', $viewed))), false)
            ->assertDontSee(route('spaces.destroy', $viewed), false);
    }

    public function test_delete_several_spaces_at_once_and_their_forms_stay(): void
    {
        $user = $this->member();
        $first = $this->space($user, SpaceRole::Manager);
        $second = $this->space($user, SpaceRole::Manager);
        $form = Form::factory()->in($this->workspace)->create();
        $form->spaces()->attach($first);

        $this->actingAs($user)->postJson(route('spaces.destroy-many'), ['spaces' => [$first->hash, $second->hash]])->assertOk();

        $this->assertModelMissing($first);
        $this->assertModelMissing($second);
        $this->assertModelExists($form);
    }

    public function test_a_bulk_delete_with_a_space_you_dont_manage_deletes_nothing(): void
    {
        $user = $this->member();
        $managed = $this->space($user, SpaceRole::Manager);
        $viewed = $this->space($user, SpaceRole::Viewer);

        $this->actingAs($user)->postJson(route('spaces.destroy-many'), ['spaces' => [$managed->hash, $viewed->hash]])->assertForbidden();

        $this->assertModelExists($managed);
        $this->assertModelExists($viewed);
        $this->actingAs($user)->get(route('spaces'))
            ->assertSee('value="'.$managed->hash.'" data-pc-select', false)
            ->assertDontSee('value="'.$viewed->hash.'" data-pc-select', false);
    }

    public function test_a_space_shows_its_description_and_opens_the_requested_tab(): void
    {
        $user = $this->member();
        $space = $this->space($user, SpaceRole::Manager);
        $space->update(['description' => 'Household surveys in Dodoma']);

        $this->actingAs($user)->get(route('spaces.show', $space))->assertOk()
            ->assertSee('<p class="pc-page-subtitle">Household surveys in Dodoma</p>', false)
            ->assertSee('data-pc-tab-panel="forms" data-pc-open="true"', false);

        $this->get(route('spaces.show', [$space, 'tab' => 'members']))
            ->assertSee('data-pc-tab-panel="members" data-pc-open="true"', false)
            ->assertSee('data-pc-tab-panel="forms" data-pc-open="false"', false);
    }

    public function test_the_forms_tab_lists_and_filters_the_spaces_forms_like_the_forms_page(): void
    {
        $user = $this->member();
        $space = $this->space($user, SpaceRole::Editor);
        Form::factory()->in($this->workspace)->create(['title' => 'Alpha open'])->spaces()->attach($space);
        Form::factory()->in($this->workspace)->create(['title' => 'Beta closed', 'closes_at' => now()->subDay()])->spaces()->attach($space);
        Form::factory()->in($this->workspace)->create(['title' => 'Elsewhere']);

        $this->actingAs($user)->get(route('spaces.show', $space))->assertOk()
            ->assertSee('Alpha open')->assertSee('Beta closed')->assertDontSee('Elsewhere')
            ->assertSee('data-pc-toolbar', false);

        // inside a space the Spaces column would only repeat its name; the forms page keeps it
        $this->get(route('spaces.show', $space))->assertDontSee('<th class="pc-col-more">'.__('Spaces').'</th>', false);
        $this->get(route('forms'))->assertSee('<th class="pc-col-more">'.__('Spaces').'</th>', false);

        $this->get(route('spaces.show', [$space, 'q' => 'alpha']))->assertSee('Alpha open')->assertDontSee('Beta closed');
        $this->get(route('spaces.show', [$space, 'status' => 'closed']))->assertSee('Beta closed')->assertDontSee('Alpha open');
    }

    public function test_managers_edit_and_delete_the_space_from_its_settings_tab(): void
    {
        $manager = $this->member();
        $viewer = $this->member();
        $space = $this->space($manager, SpaceRole::Manager);
        $space->addMember($viewer, SpaceRole::Viewer);

        $this->actingAs($manager)->get(route('spaces.show', [$space, 'tab' => 'settings']))->assertOk()
            ->assertSee('data-pc-tab-panel="settings" data-pc-open="true"', false)
            ->assertSee(route('spaces.update', $space), false)
            ->assertSee(route('spaces.destroy', $space), false)
            ->assertDontSee('data-pc-target="#editSpace"', false)
            // the colour choices carry their swatch
            ->assertSee('data-pc-swatch="var(--color-pc-brand)"', false)
            ->assertSee('data-pc-swatch="none"', false)
            ->assertDontSee('->all()', false);

        // viewers get no Settings tab; asking for it opens Forms
        $this->actingAs($viewer)->get(route('spaces.show', [$space, 'tab' => 'settings']))->assertOk()
            ->assertDontSee('data-pc-tab-panel="settings"', false)
            ->assertSee('data-pc-tab-panel="forms" data-pc-open="true"', false)
            ->assertDontSee(route('spaces.destroy', $space), false);
    }

    public function test_only_members_open_a_space(): void
    {
        $viewer = $this->member();
        $space = $this->space($viewer);

        $this->actingAs($this->member())->get(route('spaces.show', $space))->assertForbidden();
        $this->actingAs($viewer)->get(route('spaces.show', $space))->assertOk()->assertSee($space->name);
    }

    public function test_a_space_in_another_workspace_is_not_found_even_for_admins(): void
    {
        config(['surveyr.workspaces.multiple' => true]);
        $admin = User::factory()->create();
        Workspaces::provision($admin);
        $theirs = $this->space();

        $this->actingAs($admin)->get(route('spaces.show', $theirs))->assertNotFound();
    }

    public function test_managers_add_members_change_roles_and_remove_them(): void
    {
        $manager = $this->member();
        $space = $this->space($manager, SpaceRole::Manager);
        $person = $this->member();

        $this->actingAs($manager)->postJson(route('spaces.members.store', $space), ['user' => $person->hash, 'role' => 'editor'])->assertOk();
        $this->assertSame(SpaceRole::Editor, $space->roleOf($person));

        $this->actingAs($manager)->postJson(route('spaces.members.update', [$space, $person]), ['role' => 'viewer'])->assertOk();
        $this->assertSame(SpaceRole::Viewer, $space->roleOf($person));

        $this->actingAs($manager)->postJson(route('spaces.members.destroy', [$space, $person]))->assertOk();
        $this->assertNull($space->roleOf($person));
    }

    public function test_editors_and_viewers_cannot_manage_the_space(): void
    {
        $editor = $this->member();
        $space = $this->space($editor, SpaceRole::Editor);

        $this->actingAs($editor)->postJson(route('spaces.update', $space), ['name' => 'Renamed'])->assertForbidden();
        $this->actingAs($editor)->postJson(route('spaces.members.store', $space), ['user' => $this->member()->hash, 'role' => 'viewer'])->assertForbidden();
    }

    public function test_a_space_keeps_at_least_one_manager(): void
    {
        $manager = $this->member();
        $space = $this->space($manager, SpaceRole::Manager);

        $this->actingAs($manager)->postJson(route('spaces.members.update', [$space, $manager]), ['role' => 'editor'])->assertUnprocessable();
        $this->actingAs($manager)->postJson(route('spaces.members.destroy', [$space, $manager]))->assertUnprocessable();
        $this->assertSame(SpaceRole::Manager, $space->roleOf($manager));
    }

    public function test_nobody_changes_or_removes_the_spaces_creator(): void
    {
        $creator = $this->member();
        $this->actingAs($creator)->postJson(route('spaces.store'), ['name' => 'Field research'])->assertOk();
        $space = Space::withoutGlobalScope('workspace')->where('name', 'Field research')->firstOrFail();
        $otherManager = $this->member();
        $space->addMember($otherManager, SpaceRole::Manager);
        $admin = $this->member(WorkspaceRole::Admin);

        foreach ([$otherManager, $admin] as $actor) {
            $this->actingAs($actor)->postJson(route('spaces.members.update', [$space, $creator]), ['role' => 'viewer'])
                ->assertUnprocessable()->assertJson(['message' => __('The space\'s creator keeps their role.')]);
            $this->actingAs($actor)->postJson(route('spaces.members.destroy', [$space, $creator]))->assertUnprocessable();
        }
        $this->assertSame(SpaceRole::Manager, $space->roleOf($creator));

        $this->actingAs($otherManager)->get(route('spaces.show', [$space, 'tab' => 'members']))
            ->assertDontSee('memberMenu-'.$creator->hash, false)
            ->assertSee(__('Creator'));
    }

    public function test_managers_cannot_change_or_remove_themselves(): void
    {
        $owner = $this->member();
        $space = $this->space($owner, SpaceRole::Manager);
        $manager = $this->member();
        $space->addMember($manager, SpaceRole::Manager);

        $this->actingAs($manager)->postJson(route('spaces.members.update', [$space, $manager]), ['role' => 'viewer'])
            ->assertUnprocessable()->assertJson(['message' => __('You can\'t change your own role.')]);
        $this->actingAs($manager)->postJson(route('spaces.members.destroy', [$space, $manager]))->assertUnprocessable();
        $this->assertSame(SpaceRole::Manager, $space->roleOf($manager));
        $this->actingAs($manager)->get(route('spaces.show', [$space, 'tab' => 'members']))->assertDontSee('memberMenu-'.$manager->hash, false);

        // another Manager still can
        $this->actingAs($owner)->postJson(route('spaces.members.update', [$space, $manager]), ['role' => 'editor'])->assertOk();
        $this->assertSame(SpaceRole::Editor, $space->roleOf($manager));
    }

    public function test_only_workspace_members_can_be_added(): void
    {
        $manager = $this->member();
        $space = $this->space($manager, SpaceRole::Manager);
        $outsider = User::factory()->create();

        $this->actingAs($manager)->postJson(route('spaces.members.store', $space), ['user' => $outsider->hash, 'role' => 'viewer'])->assertUnprocessable();
    }
}
