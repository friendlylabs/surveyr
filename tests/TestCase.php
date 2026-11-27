<?php

namespace Tests;

use App\Support\FileStorage;
use App\Support\Settings;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /** Every test starts with no saved settings read, and uploads kept on fake disks, never real storage. */
    protected function setUp(): void
    {
        parent::setUp();
        Settings::forget();
        FileStorage::fake();
    }
}
