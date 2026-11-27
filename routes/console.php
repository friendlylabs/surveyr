<?php

use App\Jobs\ExportResponses;
use App\Models\Upload;
use App\Models\WebhookDelivery;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// uploads whose response was never sent (Upload::prunable), with their files; webhook deliveries past their days
Schedule::command('model:prune', ['--model' => [Upload::class, WebhookDelivery::class]])->hourly();

// background exports (and their emailed links) last a day
Schedule::call(fn () => ExportResponses::prune())->hourly()->name('prune-exports');
