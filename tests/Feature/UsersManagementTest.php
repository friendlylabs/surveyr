<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Notifications\AccountCreated;
use App\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class UsersManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create(['name' => 'Ada Admin']);
        Role::firstOrCreate(['name' => 'member']);
        Notification::fake();
    }

    /** The role's public hash, as the user forms post it. */
    private function roleHash(string $name): string
    {
        return Role::where('name', $name)->value('hash');
    }

    public function test_only_admins_reach_the_page(): void
    {
        $this->actingAs(User::factory()->role('member')->create())->get(route('admin.users'))->assertForbidden();
        $this->actingAs($this->admin)->get(route('admin.users'))->assertOk()->assertSee('Ada Admin');
    }

    public function test_roles_reach_the_page_by_hash_not_id(): void
    {
        $member = User::factory()->role('member')->create();
        $role = Role::where('name', 'member')->firstOrFail();

        $this->assertMatchesRegularExpression('/^[0-9a-f]{20}$/', $role->hash);
        $this->actingAs($this->admin)->get(route('admin.users'))->assertOk()
            ->assertSee('value="'.$role->hash.'"', false)
            ->assertDontSee('role_id', false);
        $this->assertNotNull($member->role);
    }

    public function test_users_are_addressed_by_hash_not_id(): void
    {
        $member = User::factory()->role('member')->create();

        $this->assertMatchesRegularExpression('/^[0-9a-f]{20}$/', $member->hash);
        $this->assertStringContainsString("/admin/users/{$member->hash}/update", route('admin.users.update', $member));
    }

    public function test_search_role_and_status_filters(): void
    {
        User::factory()->role('member')->create(['name' => 'Mia Member']);
        User::factory()->role('member')->create(['name' => 'Dan Disabled', 'disabled_at' => now()]);
        User::factory()->create(['name' => 'Nora Norole']);

        $this->actingAs($this->admin)->get(route('admin.users', ['q' => 'mia']))
            ->assertSee('Mia Member')->assertDontSee('Dan Disabled');

        $this->get(route('admin.users', ['role' => 'admin']))
            ->assertSee('Ada Admin')->assertDontSee('Mia Member');

        $this->get(route('admin.users', ['role' => 'none']))
            ->assertSee('Nora Norole')->assertDontSee('Mia Member');

        $this->get(route('admin.users', ['status' => 'disabled']))
            ->assertSee('Dan Disabled')->assertDontSee('Mia Member');

        $this->get(route('admin.users', ['q' => 'nobody-like-this']))->assertSee('No users match');
    }

    public function test_add_a_user_and_invite_them(): void
    {
        $this->actingAs($this->admin)->postJson(route('admin.users.store'), [
            'name' => 'Mia Member',
            'email' => 'Mia@Example.com',
            'role' => $this->roleHash('member'),
        ])->assertOk()->assertJson(['success' => true]);

        $user = User::where('email', 'mia@example.com')->firstOrFail();
        $this->assertTrue($user->hasRole('member'));
        Notification::assertSentTo($user, AccountCreated::class);
    }

    public function test_add_validates_inline(): void
    {
        $this->actingAs($this->admin)->postJson(route('admin.users.store'), ['name' => '', 'email' => 'not-an-email'])
            ->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonValidationErrors(['name', 'email', 'role']);
    }

    public function test_edit_a_user(): void
    {
        $member = User::factory()->role('member')->create();

        $this->actingAs($this->admin)->postJson(route('admin.users.update', $member), [
            'name' => 'Renamed',
            'email' => $member->email,
            'role' => $this->roleHash('admin'),
        ])->assertOk()->assertJson(['success' => true]);

        $this->assertTrue($member->fresh()->hasRole('admin'));
        $this->assertSame('Renamed', $member->fresh()->name);
    }

    public function test_admins_cant_change_their_own_role_or_disable_themselves(): void
    {
        $this->actingAs($this->admin)->postJson(route('admin.users.update', $this->admin), [
            'name' => 'Ada Admin',
            'email' => $this->admin->email,
            'role' => $this->roleHash('member'),
        ])->assertStatus(422);

        $this->postJson(route('admin.users.disable', $this->admin))->assertStatus(422);
        $this->assertFalse($this->admin->fresh()->isDisabled());
        $this->assertTrue($this->admin->fresh()->hasRole('admin'));
    }

    public function test_send_a_password_link(): void
    {
        $member = User::factory()->role('member')->create();

        $this->actingAs($this->admin)->postJson(route('admin.users.password-link', $member))->assertOk()->assertJson(['success' => true]);

        Notification::assertSentTo($member, ResetPassword::class);
    }

    public function test_users_are_disabled_and_enabled_never_deleted(): void
    {
        $member = User::factory()->role('member')->create();

        $this->actingAs($this->admin)->postJson(route('admin.users.disable', $member))->assertOk()->assertJson(['success' => true]);
        $this->assertTrue($member->fresh()->isDisabled());

        $this->postJson(route('admin.users.disable', $member), ['enable' => 1])->assertOk();
        $this->assertFalse($member->fresh()->isDisabled());
        $this->assertFalse(Route::has('admin.users.destroy'));
    }

    public function test_disable_several_users_at_once(): void
    {
        [$first, $second] = User::factory()->role('member')->count(2)->create();
        $kept = User::factory()->role('member')->create();

        $this->actingAs($this->admin)->postJson(route('admin.users.disable-many'), ['users' => [$first->hash, $second->hash]])
            ->assertOk()->assertJson(['success' => true]);

        $this->assertTrue($first->fresh()->isDisabled());
        $this->assertTrue($second->fresh()->isDisabled());
        $this->assertFalse($kept->fresh()->isDisabled());
    }

    public function test_a_bulk_disable_that_includes_yourself_disables_nobody(): void
    {
        $member = User::factory()->role('member')->create();

        $this->actingAs($this->admin)->postJson(route('admin.users.disable-many'), ['users' => [$member->hash, $this->admin->hash]])
            ->assertStatus(422);

        $this->assertFalse($member->fresh()->isDisabled());
    }

    public function test_a_disabled_user_gets_no_password_link(): void
    {
        $member = User::factory()->role('member')->create(['disabled_at' => now()]);

        $this->actingAs($this->admin)->postJson(route('admin.users.password-link', $member))->assertStatus(422);

        Notification::assertNothingSent();
    }

    public function test_each_row_tells_the_bulk_pill_whether_it_is_active_or_disabled(): void
    {
        $active = User::factory()->role('member')->create();
        $disabled = User::factory()->role('member')->create(['disabled_at' => now()]);

        $this->actingAs($this->admin)->get(route('admin.users'))->assertOk()
            ->assertSee('value="'.$active->hash.'" data-pc-select  data-pc-state="active"', false)
            ->assertSee('value="'.$disabled->hash.'" data-pc-select  data-pc-state="disabled"', false)
            ->assertSee('data-pc-bulk-for="active"', false)
            ->assertSee('data-pc-bulk-for="disabled"', false);
    }

    public function test_the_users_list_offers_bulk_selection_but_not_on_yourself(): void
    {
        $member = User::factory()->role('member')->create();

        $this->actingAs($this->admin)->get(route('admin.users'))->assertOk()
            ->assertSee('data-pc-select-all', false)
            ->assertSee('value="'.$member->hash.'" data-pc-select', false)
            ->assertDontSee('value="'.$this->admin->hash.'" data-pc-select', false);
    }

    public function test_an_unknown_hash_is_a_json_404(): void
    {
        $this->actingAs($this->admin)->postJson('/admin/users/doesnotexist/disable')
            ->assertNotFound()
            ->assertJson(['success' => false, 'message' => 'The requested record was not found']);
    }
}
