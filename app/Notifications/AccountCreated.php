<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * AccountCreated
 *
 * Sent when an admin creates an account: the user picks their own password through a reset link.
 */
class AccountCreated extends Notification implements ShouldQueue
{
    use Queueable;

    /** Delivery attempts before the job fails. */
    public $tries = 3;

    /** Seconds to wait before each retry. */
    public $backoff = [10, 60];

    /** Sent only once the account's transaction commits. */
    public function __construct(public string $token)
    {
        $this->afterCommit();
    }

    /** Delivered by mail. */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /** The invite email, with a link to choose a password. */
    public function toMail(object $notifiable): MailMessage
    {
        $url = route('auth.reset-password', ['token' => $this->token, 'email' => $notifiable->getEmailForPasswordReset()]);
        $minutes = config('auth.passwords.users.expire');

        return (new MailMessage)
            ->subject('Your '.config('app.name').' account')
            ->greeting("Hello {$notifiable->name},")
            ->line('An account has been created for you on '.config('app.name').'. Choose a password to sign in.')
            ->action('Choose a password', $url)
            ->line("The link expires in {$minutes} minutes. After that, use \"Forgot password\" on the sign-in page to get a new one.");
    }
}
