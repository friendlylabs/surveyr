<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Notifications\LoginLink;
use App\Notifications\ResetPassword;
use App\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\Concerns\ConfiguresEmailVerification;
use Tests\Concerns\ConfiguresRegistration;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use ConfiguresEmailVerification, ConfiguresRegistration, RefreshDatabase;

    private function user(array $attributes = []): User
    {
        return User::factory()->role('member')->create(['email' => 'jane@example.com', 'password' => 'secret-password'] + $attributes);
    }

    public function test_guests_are_sent_to_login_and_users_to_the_dashboard(): void
    {
        $this->get('/')->assertRedirect('/app/dashboard');
        $this->get('/app/dashboard')->assertRedirect(route('auth.login'));

        $this->actingAs($this->user())->get(route('auth.login'))->assertRedirect(route('dashboard'));
    }

    public function test_the_app_shell_shows_the_account_menu(): void
    {
        $this->actingAs($this->user())->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('auth.logout'))
            ->assertSee(route('profile'));
    }

    public function test_sign_in_with_valid_credentials(): void
    {
        $this->user();

        $this->postJson(route('auth.authenticate'), ['email' => 'Jane@Example.com', 'password' => 'secret-password'])
            ->assertOk()
            ->assertJson(['success' => true, 'redirect' => route('dashboard')]);

        $this->assertAuthenticated();
    }

    public function test_sign_in_with_a_wrong_password_is_a_field_error(): void
    {
        $this->user();

        $this->postJson(route('auth.authenticate'), ['email' => 'jane@example.com', 'password' => 'nope'])
            ->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonValidationErrors('email');

        $this->assertGuest();
    }

    public function test_sign_in_is_throttled_after_five_attempts(): void
    {
        $this->user();

        foreach (range(1, 5) as $i) {
            $this->postJson(route('auth.authenticate'), ['email' => 'jane@example.com', 'password' => 'nope']);
        }

        $this->postJson(route('auth.authenticate'), ['email' => 'jane@example.com', 'password' => 'nope'])
            ->assertStatus(429)
            ->assertJson(['success' => false]);
    }

    public function test_logout(): void
    {
        $this->actingAs($this->user())
            ->postJson(route('auth.logout'))
            ->assertOk()
            ->assertJsonPath('redirect', route('auth.login', ['logged-out' => 1, 'email' => 'jane@example.com']));

        $this->assertGuest();
    }

    public function test_register_creates_an_account_with_the_default_role_and_asks_them_to_confirm_their_email(): void
    {
        $this->withRegistration();
        $this->withEmailVerification();
        Notification::fake();
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'member']); // an existing role that isn't the default

        $this->postJson(route('auth.store'), [
            'name' => 'Jane Doe',
            'email' => 'Jane@Example.com',
            'password' => 'a-good-password',
            'password_confirmation' => 'a-good-password',
        ])->assertOk()->assertJson(['success' => true, 'redirect' => route('auth.verify-email')]);

        $user = User::where('email', 'jane@example.com')->firstOrFail();
        $this->assertTrue($user->hasRole(config('auth.registration.default_role')));
        $this->assertFalse($user->hasVerifiedEmail());
        $this->assertAuthenticatedAs($user);
        Notification::assertSentTo($user, VerifyEmail::class);

        // the app waits for the confirmation
        $this->get(route('dashboard'))->assertRedirect(route('auth.verify-email'));
        $this->get(route('auth.verify-email'))->assertOk()->assertSee('jane@example.com');
    }

    public function test_register_validates_each_field(): void
    {
        $this->withRegistration();
        $this->user();

        $this->postJson(route('auth.store'), ['name' => '', 'email' => 'jane@example.com', 'password' => 'short', 'password_confirmation' => 'other'])
            ->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_self_hosted_sign_ups_need_no_email_confirmation(): void
    {
        $this->withRegistration();
        Notification::fake();

        $this->postJson(route('auth.store'), [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'a-good-password',
            'password_confirmation' => 'a-good-password',
        ])->assertOk()->assertJson(['redirect' => route('dashboard')]);

        Notification::assertNothingSent();
        $this->get(route('dashboard'))->assertOk();
    }

    public function test_signing_in_records_the_time(): void
    {
        $user = $this->user();
        $this->assertNull($user->last_login_at);

        $this->postJson(route('auth.authenticate'), ['email' => $user->email, 'password' => 'secret-password'])->assertOk();

        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_a_disabled_account_cannot_sign_in(): void
    {
        $user = $this->user(['disabled_at' => now()]);

        $this->postJson(route('auth.authenticate'), ['email' => $user->email, 'password' => 'secret-password'])
            ->assertStatus(422)->assertJsonValidationErrors(['email' => __('This account is disabled. Ask an admin to enable it.')]);
        $this->assertGuest();
    }

    public function test_a_disabled_account_is_signed_out_on_its_next_request(): void
    {
        $user = $this->user(['disabled_at' => now()]);

        $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('auth.login'));
        $this->assertGuest();
    }

    public function test_the_verification_link_confirms_the_email(): void
    {
        $this->withEmailVerification();
        Notification::fake();
        $user = $this->user(['email_verified_at' => null]);

        $this->actingAs($user)->postJson(route('auth.verify-email.send'))->assertOk()->assertJson(['success' => true]);

        $url = null;
        Notification::assertSentTo($user, VerifyEmail::class, function (VerifyEmail $notification) use ($user, &$url) {
            $url = $notification->toMail($user)->actionUrl;

            return true;
        });
        $this->assertStringContainsString('/auth/verify-email/', $url);

        $this->get($url)->assertRedirect(route('dashboard'))->assertSessionHas('toast');
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->get(route('dashboard'))->assertOk();
        $this->get(route('auth.verify-email'))->assertRedirect(route('dashboard'));
    }

    public function test_a_tampered_verification_link_is_rejected(): void
    {
        $this->withEmailVerification();
        $user = $this->user(['email_verified_at' => null]);

        $this->actingAs($user)->get(route('auth.verify-email.verify', ['id' => $user->id, 'hash' => sha1($user->email)]))
            ->assertForbidden(); // unsigned

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_reset_link_is_emailed_and_unknown_addresses_look_the_same(): void
    {
        Notification::fake();
        $user = $this->user();

        $this->postJson(route('auth.send-reset-link'), ['email' => 'jane@example.com'])
            ->assertOk()
            ->assertJsonPath('redirect', route('auth.check-email', ['type' => 'reset', 'email' => 'jane@example.com']));

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
            $url = $notification->toMail($user)->actionUrl;

            return str_contains($url, '/auth/reset-password/'.$notification->token);
        });

        $this->postJson(route('auth.send-reset-link'), ['email' => 'nobody@example.com'])->assertOk()->assertJson(['success' => true]);
    }

    public function test_password_reset(): void
    {
        $user = $this->user(['email_verified_at' => null]);
        $token = Password::createToken($user);

        $this->get(route('auth.reset-password', ['token' => $token, 'email' => $user->email]))
            ->assertOk()->assertViewHas('expired', false);

        $this->postJson(route('auth.update-password'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'a-new-password',
            'password_confirmation' => 'a-new-password',
        ])->assertOk()->assertJson(['success' => true]);

        $this->assertTrue(Hash::check('a-new-password', $user->fresh()->password));
        // the link reached their inbox, so the address counts as confirmed
        $this->assertTrue($user->fresh()->hasVerifiedEmail());

        // the token works once
        $this->get(route('auth.reset-password', ['token' => $token, 'email' => $user->email]))
            ->assertViewHas('expired', true);
    }

    public function test_password_reset_with_a_bad_token_reports_expired(): void
    {
        $user = $this->user();

        $this->get(route('auth.reset-password', ['token' => 'bogus', 'email' => $user->email]))->assertViewHas('expired', true);

        $this->postJson(route('auth.update-password'), [
            'token' => 'bogus',
            'email' => $user->email,
            'password' => 'a-new-password',
            'password_confirmation' => 'a-new-password',
        ])->assertStatus(422)->assertJson(['success' => false, 'expired' => true]);
    }

    public function test_email_link_signs_in_once(): void
    {
        Notification::fake();
        $user = $this->user();

        $this->postJson(route('auth.send-email-link'), ['email' => 'jane@example.com'])->assertOk();

        $url = null;
        Notification::assertSentTo($user, LoginLink::class, function (LoginLink $notification) use (&$url) {
            $url = $notification->url;

            return true;
        });

        $this->get($url)->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);

        Auth::logout();
        $this->get($url)->assertRedirect(route('auth.email-link'))->assertSessionHas('error');
        $this->assertGuest();
    }

    public function test_auth_pages_render(): void
    {
        $this->withRegistration();

        foreach (['auth.login', 'auth.register', 'auth.forgot-password', 'auth.check-email', 'auth.email-link'] as $name) {
            $this->get(route($name))->assertOk();
        }

        $this->get(route('auth.login'))->assertSee(route('auth.register'))->assertSee(route('auth.email-link'));
    }
}
