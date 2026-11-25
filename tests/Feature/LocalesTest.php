<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use App\Support\Workspaces;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * LocalesTest
 *
 * The interface language (App\Support\Locales, SetLocale): the user's choice, then the workspace default, then the
 * browser; and the page direction that follows it.
 */
class LocalesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['surveyr.locales' => [
            'en' => ['name' => 'English', 'native' => 'English', 'dir' => 'ltr'],
            'fr' => ['name' => 'French', 'native' => 'Français', 'dir' => 'ltr'],
            'ar' => ['name' => 'Arabic', 'native' => 'العربية', 'dir' => 'rtl'],
        ]]);
    }

    private function member(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        Workspaces::provision($user);

        return $user->fresh();
    }

    public function test_pages_default_to_english_left_to_right(): void
    {
        $this->actingAs($this->member())->get(route('dashboard'))
            ->assertOk()
            ->assertSee('<html lang="en" dir="ltr"', false);
    }

    public function test_the_users_own_language_wins_and_sets_the_direction(): void
    {
        $this->actingAs($this->member(['locale' => 'ar']))->get(route('dashboard'))
            ->assertOk()
            ->assertSee('<html lang="ar" dir="rtl"', false);
    }

    public function test_without_a_choice_the_workspace_default_applies(): void
    {
        $user = $this->member();
        Workspace::findOrFail($user->current_workspace_id)->update(['locale' => 'fr']);

        $this->actingAs($user)->get(route('dashboard'))->assertSee('<html lang="fr" dir="ltr"', false);
    }

    public function test_sign_in_pages_follow_the_browser_language(): void
    {
        $this->get(route('auth.login'), ['Accept-Language' => 'ar-SA,ar;q=0.9,en;q=0.5'])
            ->assertOk()
            ->assertSee('<html lang="ar" dir="rtl"', false);
    }

    public function test_an_unsupported_browser_language_falls_back_to_the_default(): void
    {
        $this->get(route('auth.login'), ['Accept-Language' => 'de-DE,de;q=0.9'])
            ->assertSee('<html lang="en" dir="ltr"', false);
    }

    public function test_users_pick_their_language_in_their_profile(): void
    {
        $user = $this->member();

        $this->actingAs($user)->postJson(route('profile.update'), ['name' => $user->name, 'email' => $user->email, 'locale' => 'fr'])
            ->assertOk();
        $this->assertSame('fr', $user->fresh()->locale);

        // empty goes back to following the workspace default
        $this->actingAs($user)->postJson(route('profile.update'), ['name' => $user->name, 'email' => $user->email, 'locale' => ''])
            ->assertOk();
        $this->assertNull($user->fresh()->locale);
    }

    public function test_only_supported_languages_can_be_picked(): void
    {
        $user = $this->member();

        $this->actingAs($user)->postJson(route('profile.update'), ['name' => $user->name, 'email' => $user->email, 'locale' => 'xx'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('locale');
    }

    public function test_the_page_hands_its_strings_to_the_browser(): void
    {
        $this->actingAs($this->member())->get(route('dashboard'))->assertSee('window.pcStrings = {', false);
    }
}
