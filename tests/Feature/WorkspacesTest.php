<?php

namespace Tests\Feature;

use App\Enums\WorkspaceRole;
use App\Models\Role;
use App\Models\User;
use App\Models\Workspace;
use App\Support\CurrentWorkspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\ConfiguresRegistration;
use Tests\TestCase;

// config/surveyr.php `workspaces`: self-hosted (one shared workspace) and SaaS (a workspace per account)
class WorkspacesTest extends TestCase
{
    use ConfiguresRegistration, RefreshDatabase;

    private function signUp(string $email)
    {
        return $this->postJson('/auth/register', [
            'name' => 'Jane Doe',
            'email' => $email,
            'password' => 'a-good-password',
            'password_confirmation' => 'a-good-password',
        ]);
    }

    private function multipleWorkspaces(bool $on = true): void
    {
        config(['surveyr.workspaces.multiple' => $on]);
    }

    public function test_self_hosted_sign_ups_share_one_workspace_and_the_first_owns_it(): void
    {
        $this->withRegistration();

        $this->signUp('first@example.com')->assertOk();
        auth()->logout();
        $this->signUp('second@example.com')->assertOk();

        $workspace = Workspace::sole();
        $first = User::where('email', 'first@example.com')->firstOrFail();
        $second = User::where('email', 'second@example.com')->firstOrFail();

        $this->assertTrue($workspace->owner->is($first));
        $this->assertSame(WorkspaceRole::Owner, $first->workspaceRole($workspace));
        $this->assertSame(WorkspaceRole::Member, $second->workspaceRole($workspace));
        $this->assertSame($workspace->id, $second->current_workspace_id);
    }

    public function test_with_multiple_workspaces_each_sign_up_owns_its_own(): void
    {
        $this->withRegistration();
        $this->multipleWorkspaces();

        $this->signUp('first@example.com')->assertOk();
        auth()->logout();
        $this->signUp('second@example.com')->assertOk();

        $this->assertSame(2, Workspace::count());
        $second = User::where('email', 'second@example.com')->firstOrFail();
        $this->assertSame("Jane's workspace", $second->currentWorkspace->name);
        $this->assertSame(WorkspaceRole::Owner, $second->workspaceRole($second->currentWorkspace));
    }

    public function test_admin_invited_accounts_join_the_shared_workspace(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $workspace = Workspace::factory()->ownedBy($admin)->create();

        $this->actingAs($admin)->postJson(route('admin.users.store'), [
            'name' => 'Ivy Invited',
            'email' => 'ivy@example.com',
            'role_id' => Role::firstOrCreate(['name' => 'member'])->id,
        ])->assertOk();

        $invited = User::where('email', 'ivy@example.com')->firstOrFail();
        $this->assertSame(WorkspaceRole::Member, $invited->workspaceRole($workspace));
    }

    public function test_accounts_without_a_workspace_get_a_seat_on_their_first_visit(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertViewHas('currentWorkspace');

        $this->assertNotNull($user->fresh()->current_workspace_id);
        $this->assertSame(1, Workspace::count());
    }

    public function test_a_member_removed_from_their_current_workspace_falls_back_to_another(): void
    {
        $this->multipleWorkspaces();
        $user = User::factory()->create();
        $kept = Workspace::factory()->ownedBy($user)->create();
        $left = Workspace::factory()->create();
        $left->addMember($user);
        $user->switchWorkspace($left);

        $left->members()->detach($user);

        $this->actingAs($user)->get(route('dashboard'))->assertOk();

        $this->assertSame($kept->id, $user->fresh()->current_workspace_id);
        $this->assertSame($kept->id, app(CurrentWorkspace::class)->id());
    }

    public function test_members_switch_between_their_workspaces(): void
    {
        $this->multipleWorkspaces();
        $user = User::factory()->create();
        Workspace::factory()->ownedBy($user)->create();
        $other = Workspace::factory()->create();
        $other->addMember($user);

        $this->actingAs($user)->postJson(route('workspaces.switch', $other))
            ->assertOk()
            ->assertJson(['success' => true, 'redirect' => route('dashboard')]);

        $this->assertSame($other->id, $user->fresh()->current_workspace_id);
    }

    public function test_nobody_can_switch_into_a_workspace_they_are_not_in(): void
    {
        $this->multipleWorkspaces();
        $user = User::factory()->create();
        $mine = Workspace::factory()->ownedBy($user)->create();
        $theirs = Workspace::factory()->create();

        $this->actingAs($user)->postJson(route('workspaces.switch', $theirs))->assertNotFound();

        $this->assertSame($mine->id, $user->fresh()->current_workspace_id);
    }

    public function test_workspaces_are_addressed_by_hash_not_id(): void
    {
        $workspace = Workspace::factory()->create();

        $this->assertStringContainsString($workspace->hash, route('workspaces.switch', $workspace));
        $this->assertStringNotContainsString('/'.$workspace->id.'/', route('workspaces.switch', $workspace));
    }

    public function test_only_owners_and_admins_manage_the_current_workspace(): void
    {
        $this->multipleWorkspaces();
        $owner = User::factory()->create();
        $workspace = Workspace::factory()->ownedBy($owner)->create();
        $admin = User::factory()->create();
        $member = User::factory()->create();
        $workspace->addMember($admin, WorkspaceRole::Admin);
        $workspace->addMember($member);

        app(CurrentWorkspace::class)->set($workspace);

        $this->assertTrue(Gate::forUser($owner)->allows('manage-workspace'));
        $this->assertTrue(Gate::forUser($admin)->allows('manage-workspace'));
        $this->assertFalse(Gate::forUser($member)->allows('manage-workspace'));
    }

    public function test_the_install_admin_passes_every_check_only_when_self_hosted(): void
    {
        $installAdmin = User::factory()->admin()->create();
        $workspace = Workspace::factory()->create();
        app(CurrentWorkspace::class)->set($workspace);

        $this->assertTrue(Gate::forUser($installAdmin)->allows('manage-workspace'));

        // SaaS: admin is a platform role, with no pass into a workspace it isn't a member of
        $this->multipleWorkspaces();
        $this->assertFalse(Gate::forUser($installAdmin)->allows('manage-workspace'));
        $this->assertTrue(Gate::forUser($installAdmin)->allows('admin'));
    }
}
