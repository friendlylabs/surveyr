<?php

namespace App\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\Login;

/**
 * RecordLastSignIn
 *
 * Stamps users.last_login_at on every sign-in (password, two-factor, email link, passkey, sign-up), for the admin's
 * users list. Written straight to the table so it doesn't move updated_at.
 */
class RecordLastSignIn
{
    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        if ($event->user instanceof User) {
            User::whereKey($event->user->getKey())->toBase()->update(['last_login_at' => now()]);
        }
    }
}
