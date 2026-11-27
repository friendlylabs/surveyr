<?php

use App\Models\Upload;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// uploads whose response was never sent (Upload::prunable), with their files
Schedule::command('model:prune', ['--model' => [Upload::class]])->hourly();
