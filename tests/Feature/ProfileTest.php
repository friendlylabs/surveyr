<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_the_name(): void
    {
        $user = User::factory()->role('member')->create(['email' => 'jane@example.com']);

        $this->actingAs($user)->postJson(route('profile.update'), ['name' => 'Jane Renamed', 'email' => 'jane@example.com'])
            ->assertOk()->assertJson(['success' => true, 'message' => 'Profile saved']);

        $this->assertSame('Jane Renamed', $user->fresh()->name);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_a_new_email_must_be_confirmed(): void
    {
        Notification::fake();
        $user = User::factory()->role('member')->create(['email' => 'jane@example.com']);

        $this->actingAs($user)->postJson(route('profile.update'), ['name' => $user->name, 'email' => 'Jane.New@Example.com'])
            ->assertOk()->assertJsonPath('redirect', route('auth.verify-email'));

        $this->assertSame('jane.new@example.com', $user->fresh()->email);
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_the_email_must_be_unique(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);
        $user = User::factory()->role('member')->create();

        $this->actingAs($user)->postJson(route('profile.update'), ['name' => $user->name, 'email' => 'taken@example.com'])
            ->assertStatus(422)->assertJsonValidationErrors('email');
    }

    public function test_change_the_password(): void
    {
        $user = User::factory()->role('member')->create(['password' => 'old-password']);

        $this->actingAs($user)->postJson(route('profile.password'), [
            'current_password' => 'wrong',
            'password' => 'a-new-password',
            'password_confirmation' => 'a-new-password',
        ])->assertStatus(422)->assertJsonValidationErrors('current_password');

        $this->postJson(route('profile.password'), [
            'current_password' => 'old-password',
            'password' => 'a-new-password',
            'password_confirmation' => 'a-new-password',
        ])->assertOk()->assertJson(['success' => true]);

        $this->assertTrue(Hash::check('a-new-password', $user->fresh()->password));
    }
}
