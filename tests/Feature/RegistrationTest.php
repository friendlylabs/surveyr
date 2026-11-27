<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\Concerns\ConfiguresRegistration;
use Tests\TestCase;

/**
 * RegistrationTest
 *
 * config/auth.php `registration`: enabled, default_role, allow_invitation_only, allow_email_domains.
 */
class RegistrationTest extends TestCase
{
    use ConfiguresRegistration, RefreshDatabase;

    private function signUp(string $email = 'jane@example.com')
    {
        return $this->postJson('/auth/register', [
            'name' => 'Jane Doe',
            'email' => $email,
            'password' => 'a-good-password',
            'password_confirmation' => 'a-good-password',
        ]);
    }

    public function test_new_accounts_get_the_configured_default_role(): void
    {
        $this->withRegistration(['default_role' => 'guest']);

        $this->signUp()->assertOk();

        // created on first use, so a renamed default needs no seeder run
        $this->assertTrue(User::where('email', 'jane@example.com')->firstOrFail()->hasRole('guest'));
        $this->assertDatabaseHas('roles', ['name' => 'guest']);
    }

    public function test_the_default_role_can_never_be_admin(): void
    {
        $this->withRegistration(['default_role' => 'admin']);
        $this->withoutExceptionHandling();

        $this->expectException(RuntimeException::class);
        $this->signUp();
    }

    public function test_registration_can_be_switched_off(): void
    {
        $this->withRegistration(['enabled' => false]);

        $this->get('/auth/register')->assertNotFound();
        $this->signUp()->assertNotFound();
        $this->get(route('auth.login'))->assertDontSee('Create an account');
        $this->assertDatabaseCount('users', 0);
    }

    public function test_invitation_only_closes_sign_up_but_admins_can_still_invite(): void
    {
        $this->withRegistration(['allow_invitation_only' => true]);

        $this->get('/auth/register')->assertNotFound();
        $this->get(route('auth.login'))->assertDontSee('Create an account');

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->postJson(route('admin.users.store'), [
            'name' => 'Invited Person',
            'email' => 'invited@example.com',
            'role' => Role::firstOrCreate(['name' => 'member'])->hash,
        ])->assertOk();
        $this->assertDatabaseHas('users', ['email' => 'invited@example.com']);
    }

    public function test_sign_up_is_limited_to_the_allowed_email_domains(): void
    {
        $this->withRegistration(['allow_email_domains' => ['Example.com', '@acme.test']]);

        $this->get('/auth/register')->assertOk()->assertSee('Use your @example.com or @acme.test address.');

        $this->signUp('jane@gmail.com')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email' => 'Sign up with an email address on @example.com or @acme.test.']);

        // an exact match: a subdomain isn't the domain
        $this->signUp('jane@mail.example.com')->assertStatus(422)->assertJsonValidationErrors('email');

        $this->signUp('Jane@ACME.test')->assertOk();
        $this->assertDatabaseHas('users', ['email' => 'jane@acme.test']);
    }

    public function test_one_allowed_domain_reads_naturally(): void
    {
        $this->withRegistration(['allow_email_domains' => ['example.com']]);

        $this->signUp('jane@gmail.com')
            ->assertJsonValidationErrors(['email' => 'Sign up with your @example.com email address.']);
    }

    public function test_no_domain_list_allows_any_address(): void
    {
        $this->withRegistration(['allow_email_domains' => []]);

        $this->signUp('jane@anything.test')->assertOk();
    }
}
