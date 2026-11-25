<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * ResetPassword
 *
 * Laravel's reset-password email, queued; the link itself comes from ResetPassword::createUrlUsing
 * (FortifyServiceProvider).
 */
class ResetPassword extends BaseResetPassword implements ShouldQueue
{
    use Queueable;

    /** Delivery attempts before the job fails. */
    public $tries = 3;

    /** Seconds to wait before each retry. */
    public $backoff = [10, 60];

    /** Sent only once any open transaction commits. */
    public function __construct(string $token)
    {
        parent::__construct($token);
        $this->afterCommit();
    }
}
