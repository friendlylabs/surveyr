<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * LoginLink
 *
 * One-time sign-in link, sent from the email-link page.
 */
class LoginLink extends Notification implements ShouldQueue
{
    use Queueable;

    /** Delivery attempts before the job fails. */
    public $tries = 3;

    /** Seconds to wait before each retry. */
    public $backoff = [10, 60];

    /** Sent only once any open transaction commits. */
    public function __construct(public string $url, public int $minutes)
    {
        $this->afterCommit();
    }

    /** Delivered by mail. */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /** The sign-in email, with the link and how long it lasts. */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your '.config('app.name').' sign-in link')
            ->line('Use the button below to sign in. No password needed.')
            ->action('Sign in', $this->url)
            ->line("The link expires in {$this->minutes} minutes and works once.")
            ->line("If you didn't ask for it, you can ignore this email.");
    }
}
