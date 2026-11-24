<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_missing_page_gets_the_404_page(): void
    {
        $this->get('/no-such-page')
            ->assertNotFound()
            ->assertSee('pc-error-code', false)
            ->assertSee('Oops... nothing to see here!')
            ->assertSee('Go home')
            ->assertSee('<title>Not found | ', false);
    }

    public function test_a_forbidden_page_gets_the_403_page(): void
    {
        $this->actingAs(User::factory()->role('member')->create())->get(route('admin.users'))
            ->assertForbidden()
            ->assertSee("Sorry... this one's off limits");
    }

    public function test_every_status_page_renders(): void
    {
        foreach ([401, 403, 404, 419, 429, 500, 503] as $code) {
            Route::get("/_error/{$code}", fn () => abort($code));

            $this->get("/_error/{$code}")
                ->assertStatus($code)
                ->assertSee("<div class=\"pc-error-code\" aria-hidden=\"true\">{$code}</div>", false);
        }
    }

    public function test_json_requests_still_get_json_errors(): void
    {
        $this->getJson('/no-such-page')->assertNotFound()->assertJson(['success' => false]);
    }
}
