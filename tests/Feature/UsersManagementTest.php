<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Notifications\AccountCreated;
use App\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
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

    private function roleId(string $name): int
    {
        return Role::where('name', $name)->value('id');
    }

    public function test_only_admins_reach_the_page(): void
    {
        $this->actingAs(User::factory()->role('member')->create())->get(route('admin.users'))->assertForbidden();
        $this->actingAs($this->admin)->get(route('admin.users'))->assertOk()->assertSee('Ada Admin');
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
        User::factory()->role('member')->unverified()->create(['name' => 'Una Unconfirmed']);
        User::factory()->create(['name' => 'Nora Norole']);

        $this->actingAs($this->admin)->get(route('admin.users', ['q' => 'mia']))
            ->assertSee('Mia Member')->assertDontSee('Una Unconfirmed');

        $this->get(route('admin.users', ['role' => 'admin']))
            ->assertSee('Ada Admin')->assertDontSee('Mia Member');

        $this->get(route('admin.users', ['role' => 'none']))
            ->assertSee('Nora Norole')->assertDontSee('Mia Member');

        $this->get(route('admin.users', ['status' => 'unverified']))
            ->assertSee('Una Unconfirmed')->assertDontSee('Mia Member');

        $this->get(route('admin.users', ['q' => 'nobody-like-this']))->assertSee('No users match');
    }

    public function test_add_a_user_and_invite_them(): void
    {
        $this->actingAs($this->admin)->postJson(route('admin.users.store'), [
            'name' => 'Mia Member',
            'email' => 'Mia@Example.com',
            'role_id' => $this->roleId('member'),
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
            ->assertJsonValidationErrors(['name', 'email', 'role_id']);
    }

    public function test_edit_a_user(): void
    {
        $member = User::factory()->role('member')->create();

        $this->actingAs($this->admin)->postJson(route('admin.users.update', $member), [
            'name' => 'Renamed',
            'email' => $member->email,
            'role_id' => $this->roleId('admin'),
        ])->assertOk()->assertJson(['success' => true]);

        $this->assertTrue($member->fresh()->hasRole('admin'));
        $this->assertSame('Renamed', $member->fresh()->name);
    }

    public function test_admins_cant_change_their_own_role_or_delete_themselves(): void
    {
        $this->actingAs($this->admin)->postJson(route('admin.users.update', $this->admin), [
            'name' => 'Ada Admin',
            'email' => $this->admin->email,
            'role_id' => $this->roleId('member'),
        ])->assertStatus(422);

        $this->postJson(route('admin.users.destroy', $this->admin))->assertStatus(422);
        $this->assertTrue($this->admin->fresh()->hasRole('admin'));
    }

    public function test_send_a_password_link(): void
    {
        $member = User::factory()->role('member')->create();

        $this->actingAs($this->admin)->postJson(route('admin.users.password-link', $member))->assertOk()->assertJson(['success' => true]);

        Notification::assertSentTo($member, ResetPassword::class);
    }

    public function test_delete_a_user(): void
    {
        $member = User::factory()->role('member')->create();

        $this->actingAs($this->admin)->postJson(route('admin.users.destroy', $member))->assertOk()->assertJson(['success' => true]);

        $this->assertModelMissing($member);
    }

    public function test_an_unknown_hash_is_a_json_404(): void
    {
        $this->actingAs($this->admin)->postJson('/admin/users/doesnotexist/delete')
            ->assertNotFound()
            ->assertJson(['success' => false, 'message' => 'The requested record was not found']);
    }
}
