<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * AssetVersionTest
 *
 * @asset (App\Support\Assets): public/assets files load with their last-changed time, so browsers drop cached copies
 * as soon as a file changes.
 */
class AssetVersionTest extends TestCase
{
    public function test_a_file_gets_its_last_changed_time(): void
    {
        $stamp = filemtime(public_path('assets/js/pc-core.js'));

        $this->assertSame(asset('assets/js/pc-core.js')."?v={$stamp}", Blade::render("@asset('assets/js/pc-core.js')"));
    }

    public function test_a_missing_file_gets_a_plain_url(): void
    {
        $this->assertSame(asset('assets/js/missing.js'), Blade::render("@asset('assets/js/missing.js')"));
    }

    public function test_the_app_pages_load_scripts_with_a_version(): void
    {
        $this->get(route('auth.login'))->assertOk()
            ->assertSee('/assets/js/pc-core.js?v=', false)
            ->assertDontSee('/assets/js/pc-core.js"', false);
    }
}
