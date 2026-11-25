<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Illuminate\View\ViewException;
use Tests\TestCase;

/**
 * SurveyJsComponentTest
 *
 * <x-surveyjs>: loads one SurveyJS bundle and hands it the licence key from config/surveyr.php.
 */
class SurveyJsComponentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_the_licence_key_reaches_the_page_when_set(): void
    {
        config(['surveyr.surveyjs.license_key' => 'test-licence-key']);

        $html = Blade::render('<x-surveyjs entry="creator" />');

        $this->assertStringContainsString('<meta name="surveyjs-license" content="test-licence-key">', $html);
    }

    public function test_no_licence_meta_is_rendered_without_a_key(): void
    {
        config(['surveyr.surveyjs.license_key' => null]);

        $this->assertStringNotContainsString('surveyjs-license', Blade::render('<x-surveyjs entry="form" />'));
    }

    public function test_unknown_bundles_are_refused(): void
    {
        $this->expectException(ViewException::class);

        Blade::render('<x-surveyjs entry="pdf" />');
    }
}
