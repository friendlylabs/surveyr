<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_dashboard_renders_for_every_role(): void
    {
        $this->actingAs(User::factory()->role('member')->create())->get(route('dashboard'))
            ->assertOk()->assertSee('<title>Dashboard', false);

        $this->actingAs(User::factory()->admin()->create())->get(route('dashboard'))
            ->assertOk()->assertSee('<title>Dashboard', false);
    }

    public function test_the_components_reference_renders(): void
    {
        $this->actingAs(User::factory()->role('member')->create())->get(route('components'))
            ->assertOk()
            ->assertSee('id="demoModal"', false)
            ->assertSee('id="demoOffcanvas"', false);
    }
}
