<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SidebarTest extends TestCase
{
    use RefreshDatabase;

    public function test_members_get_the_app_menu_without_administration(): void
    {
        $this->actingAs(User::factory()->role('member')->create())->get(route('dashboard'))
            ->assertOk()
            ->assertSee('fa-solid fa-grid-2', false)
            ->assertSee('aria-current="page"', false)
            ->assertSeeInOrder(['Dashboard', 'Components'])
            ->assertDontSee('Administration')
            ->assertDontSee(route('admin.users'));
    }

    public function test_admins_also_get_the_administration_items(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get(route('dashboard'))
            ->assertSeeInOrder(['Dashboard', 'Administration', 'Users', 'Settings', 'Components']);
    }

    public function test_an_item_without_a_route_opens_the_under_development_dialog(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get(route('dashboard'))
            ->assertSee('onclick="underDevelopment(event)"', false);
    }

    public function test_admin_routes_are_admin_only(): void
    {
        Route::middleware(['web', 'auth', 'can:admin'])->name('admin.')->prefix('admin')
            ->get('/_test', fn () => 'ok')->name('test');

        $this->actingAs(User::factory()->admin()->create())->get('/admin/_test')->assertOk();
        $this->actingAs(User::factory()->role('member')->create())->get('/admin/_test')->assertForbidden();
    }
}
