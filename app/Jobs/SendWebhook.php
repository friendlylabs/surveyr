<?php

namespace App\Jobs;

use App\Models\WebhookDelivery;
use App\Support\Webhooks;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * SendWebhook
 *
 * Tells a form's webhook about a new response (App\Support\Webhooks::attempt). A failed attempt is tried again after
 * BACKOFF seconds, up to TRIES attempts in all (about two and a half hours), then the delivery is marked failed.
 */
class SendWebhook implements ShouldQueue
{
    use Queueable;

    /** Attempts in all. */
    const TRIES = 5;

    /** Seconds before each retry: a minute, 5 minutes, half an hour, 2 hours. */
    const BACKOFF = [60, 300, 1800, 7200];

    /** The worker's limit on attempts (each failed one is released, and counts). */
    public int $tries = self::TRIES;

    /** Seconds one attempt may run. */
    public int $timeout = 30;

    /** A delivery pruned in the meantime is dropped. */
    public bool $deleteWhenMissingModels = true;

    /** Create a new job instance. */
    public function __construct(public WebhookDelivery $delivery) {}

    /** Execute the job. */
    public function handle(): void
    {
        if ($this->delivery->isDone()) {
            return;
        }

        Webhooks::attempt($this->delivery, $this->attempts() >= self::TRIES);

        if ($this->delivery->status === WebhookDelivery::RETRYING) {
            $this->release(self::BACKOFF[$this->attempts() - 1] ?? self::BACKOFF[array_key_last(self::BACKOFF)]);
        }
    }

    /** Stopped some other way (it timed out, say): the delivery is failed. */
    public function failed(?Throwable $exception): void
    {
        if (! $this->delivery->isDone()) {
            $this->delivery->update(['status' => WebhookDelivery::FAILED, 'error' => __('Stopped: :reason', ['reason' => $exception?->getMessage() ?: __('unknown')])]);
        }
    }
}
