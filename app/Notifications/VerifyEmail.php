<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * VerifyEmail
 *
 * Laravel's verify-email email, queued; the link itself comes from VerifyEmail::createUrlUsing
 * (FortifyServiceProvider).
 */
class VerifyEmail extends BaseVerifyEmail implements ShouldQueue
{
    use Queueable;

    /** Delivery attempts before the job fails. */
    public $tries = 3;

    /** Seconds to wait before each retry. */
    public $backoff = [10, 60];

    /** Sent only once any open transaction commits. */
    public function __construct()
    {
        $this->afterCommit();
    }
}
