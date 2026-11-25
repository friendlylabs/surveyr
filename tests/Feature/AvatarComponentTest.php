<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * AvatarComponentTest
 *
 * <x-avatar>: the generated picture is seeded with the user's public hash, never personal data, over initials.
 */
class AvatarComponentTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_users_avatar_is_seeded_with_their_hash_not_personal_data(): void
    {
        config(['surveyr.avatars.url' => 'https://api.dicebear.com/7.x/lorelei/svg?seed=:seed']);
        $user = User::factory()->create(['name' => 'Amina Juma', 'email' => 'amina@example.com']);

        $html = Blade::render('<x-avatar :user="$user" />', ['user' => $user]);

        $this->assertStringContainsString('https://api.dicebear.com/7.x/lorelei/svg?seed='.$user->hash, $html);
        preg_match('/src="([^"]+)"/', $html, $src);
        $this->assertStringNotContainsStringIgnoringCase('amina', $src[1]);
        $this->assertStringContainsString('AJ', $html);
    }

    public function test_initials_only_when_avatars_are_switched_off(): void
    {
        config(['surveyr.avatars.url' => '']);
        $user = User::factory()->create(['name' => 'Amina Juma']);

        $html = Blade::render('<x-avatar :user="$user" />', ['user' => $user]);

        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringContainsString('AJ', $html);
    }

    public function test_a_name_alone_renders_initials(): void
    {
        $html = Blade::render('<x-avatar name="Northwind Traders" />');

        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringContainsString('NT', $html);
    }
}
