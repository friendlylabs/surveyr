<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * ResponsesExportFailed
 *
 * Sent when a background export (ExportResponses) couldn't be made: why, when that's known, and a way back to try again.
 */
class ResponsesExportFailed extends Notification
{
    use Queueable;

    public function __construct(public string $form, public string $url, public ?string $reason = null) {}

    /** Delivered by mail. */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /** The email, with a link back to the responses. */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Your export of :form could not be made', ['form' => $this->form]))
            ->line($this->reason ?? __("Something went wrong while preparing the export of :form's responses.", ['form' => $this->form]))
            ->action(__('Back to the responses'), $this->url)
            ->line(__('Try again from there, with fewer responses if there were many.'));
    }
}
