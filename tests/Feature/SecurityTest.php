<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\LoginLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    private function member(array $attributes = []): User
    {
        return User::factory()->role('member')->create(['email' => 'jane@example.com', 'password' => 'secret-password'] + $attributes);
    }

    // a user with two-factor already on, and the plain secret to make codes from
    private function withTwoFactor(): array
    {
        $secret = (new Google2FA)->generateSecretKey();
        $user = $this->member();
        $user->forceFill([
            'two_factor_secret' => encrypt($secret),
            'two_factor_recovery_codes' => encrypt(json_encode(['recovery-code-one', 'recovery-code-two'])),
            'two_factor_confirmed_at' => now(),
        ])->save();

        return [$user, $secret];
    }

    private function confirmed(): array
    {
        return ['auth.password_confirmed_at' => time()];
    }

    public function test_the_profile_page_shows_every_section(): void
    {
        $this->actingAs($this->member())->get(route('profile'))
            ->assertOk()
            ->assertSeeInOrder(['Profile information', 'Password', 'Two-factor authentication', 'Browser sessions', 'Passkeys'])
            ->assertSee('id="confirmPassword"', false);
    }

    public function test_security_actions_need_the_password_confirmed(): void
    {
        $this->actingAs($this->member())
            ->postJson(route('profile.two-factor.enable'))
            ->assertStatus(423);

        $this->getJson(route('profile.passkeys.options'))->assertStatus(423);

        // a page visit goes to the confirm-password page instead
        $this->get(route('profile.two-factor.recovery-codes'))->assertRedirect(route('auth.confirm-password'));
        $this->get(route('auth.confirm-password'))->assertOk();
    }

    public function test_confirming_the_password(): void
    {
        $this->actingAs($this->member());

        $this->postJson(route('auth.confirm-password.store'), ['password' => 'wrong'])
            ->assertStatus(422)->assertJsonValidationErrors('password');

        $this->postJson(route('auth.confirm-password.store'), ['password' => 'secret-password'])
            ->assertOk()->assertJson(['success' => true]);

        $this->postJson(route('profile.two-factor.enable'))->assertOk();
    }

    public function test_turning_two_factor_on_takes_a_code_from_the_app(): void
    {
        $user = $this->member();
        $this->actingAs($user)->withSession($this->confirmed());

        $this->postJson(route('profile.two-factor.enable'))->assertOk();
        $this->assertNotNull($user->fresh()->two_factor_secret);
        $this->assertFalse($user->fresh()->hasTwoFactorEnabled());

        $this->getJson(route('profile.two-factor.qr-code'))->assertOk()->assertJsonStructure(['svg', 'url']);
        $this->getJson(route('profile.two-factor.secret-key'))->assertOk()->assertJsonStructure(['secretKey']);

        $this->postJson(route('profile.two-factor.confirm'), ['code' => '000000'])
            ->assertStatus(422)->assertJson(['success' => false])->assertJsonValidationErrors('code');

        $code = (new Google2FA)->getCurrentOtp(decrypt($user->fresh()->two_factor_secret));
        $this->postJson(route('profile.two-factor.confirm'), ['code' => $code])->assertOk();

        $this->assertTrue($user->fresh()->hasTwoFactorEnabled());
        $this->assertCount(8, $this->getJson(route('profile.two-factor.recovery-codes'))->assertOk()->json());

        $this->postJson(route('profile.two-factor.disable'))->assertOk();
        $this->assertNull($user->fresh()->two_factor_secret);
    }

    public function test_sign_in_with_two_factor_asks_for_a_code(): void
    {
        [$user, $secret] = $this->withTwoFactor();

        $this->postJson(route('auth.authenticate'), ['email' => 'jane@example.com', 'password' => 'secret-password'])
            ->assertOk()
            ->assertJson(['success' => true, 'twoFactor' => true, 'redirect' => route('auth.two-factor')]);
        $this->assertGuest();

        $this->get(route('auth.two-factor'))->assertOk()->assertSee('Authentication code');

        $this->postJson(route('auth.two-factor.login'), ['code' => '000000'])
            ->assertStatus(422)->assertJsonValidationErrors('code');
        $this->assertGuest();

        $this->postJson(route('auth.two-factor.login'), ['code' => (new Google2FA)->getCurrentOtp($secret)])
            ->assertOk()->assertJson(['success' => true, 'redirect' => route('dashboard')]);
        $this->assertAuthenticatedAs($user);
    }

    public function test_a_recovery_code_signs_in_once(): void
    {
        [$user] = $this->withTwoFactor();

        $this->postJson(route('auth.authenticate'), ['email' => 'jane@example.com', 'password' => 'secret-password']);
        $this->postJson(route('auth.two-factor.login'), ['recovery_code' => 'recovery-code-one'])->assertOk();
        $this->assertAuthenticatedAs($user);
        $this->assertNotContains('recovery-code-one', $user->fresh()->recoveryCodes());
    }

    public function test_the_challenge_needs_a_password_first(): void
    {
        $this->get(route('auth.two-factor'))->assertRedirect(route('auth.login'));
        $this->postJson(route('auth.two-factor.login'), ['code' => '123456'])->assertStatus(419);
    }

    public function test_an_email_link_still_asks_for_the_second_factor(): void
    {
        Notification::fake();
        [$user] = $this->withTwoFactor();

        $this->postJson(route('auth.send-email-link'), ['email' => 'jane@example.com'])->assertOk();

        $url = null;
        Notification::assertSentTo($user, LoginLink::class, function (LoginLink $notification) use (&$url) {
            $url = $notification->url;

            return true;
        });

        $this->get($url)->assertRedirect(route('auth.two-factor'));
        $this->assertGuest();
    }

    public function test_log_out_another_device(): void
    {
        config(['session.driver' => 'database']);
        $user = $this->member();
        foreach (['other-one', 'other-two'] as $id) {
            DB::table('sessions')->insert(['id' => $id, 'user_id' => $user->id, 'ip_address' => '10.0.0.1', 'user_agent' => 'Mozilla/5.0 (iPhone) Safari/604.1', 'payload' => '', 'last_activity' => now()->subHour()->timestamp]);
        }

        $this->actingAs($user)->withSession($this->confirmed())->get(route('profile'))
            ->assertOk()->assertSee('Safari on iPhone')->assertSee('Log out other devices');

        $this->postJson(route('profile.sessions.destroy', 'other-one'))->assertOk();
        $this->assertDatabaseMissing('sessions', ['id' => 'other-one']);

        $this->postJson(route('profile.sessions.destroy', 'someone-else'))->assertNotFound();

        $this->postJson(route('profile.sessions.destroy-others'))->assertOk();
        $this->assertDatabaseMissing('sessions', ['id' => 'other-two']);
    }

    public function test_expired_sessions_are_not_listed(): void
    {
        config(['session.driver' => 'database']);
        $user = $this->member();
        $row = ['user_id' => $user->id, 'ip_address' => '10.0.0.1', 'payload' => ''];
        DB::table('sessions')->insert([
            $row + ['id' => 'live', 'user_agent' => 'Mozilla/5.0 (Windows NT 10.0) HeadlessChrome/140.0 Safari/537.36', 'last_activity' => now()->subMinutes(5)->timestamp],
            $row + ['id' => 'expired', 'user_agent' => 'Mozilla/5.0 (Macintosh) Firefox/130.0', 'last_activity' => now()->subMinutes(config('session.lifetime') + 1)->timestamp],
        ]);

        $this->actingAs($user)->get(route('profile'))
            ->assertOk()
            ->assertSee('Headless Chrome on Windows')
            ->assertDontSee('Firefox on Mac');
    }

    public function test_passkey_registration_starts_once_confirmed(): void
    {
        $this->actingAs($this->member())->withSession($this->confirmed())
            ->getJson(route('profile.passkeys.options'))
            ->assertOk()
            ->assertJsonStructure(['options' => ['challenge', 'rp', 'user']]);

        $this->postJson(route('profile.passkeys.store'), ['name' => 'Laptop', 'credential' => ['id' => 'bogus']])
            ->assertStatus(422)->assertJson(['success' => false]);
    }

    public function test_passkey_sign_in_options_are_public(): void
    {
        $this->getJson(route('auth.passkeys.options'))->assertOk()->assertJsonStructure(['options' => ['challenge']]);
        $this->get(route('auth.login'))->assertSee('Sign in with a passkey');
    }
}
