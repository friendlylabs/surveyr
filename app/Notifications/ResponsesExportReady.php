<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * ResponsesExportReady
 *
 * Sent when a background export (ExportResponses) is ready: the workbook with its attachments, or the attachments
 * alone; a link to download it, which lasts a while.
 */
class ResponsesExportReady extends Notification
{
    use Queueable;

    public function __construct(public string $form, public string $url, public int $hours, public bool $attachmentsOnly = false) {}

    /** Delivered by mail. */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /** The email, with the download button and how long it lasts. */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Your export of :form is ready', ['form' => $this->form]))
            ->line($this->attachmentsOnly
                ? __('The attachments of the responses to :form are zipped and ready to download.', ['form' => $this->form])
                : __('The responses to :form and their attachments are zipped and ready to download.', ['form' => $this->form]))
            ->action(__('Download export'), $this->url)
            ->line($this->attachmentsOnly
                ? __("Each response's files are in a folder named after it (RSP-12); manifest.csv lists them all.")
                : __('Unzip it and open the workbook: the links in it open the attachments beside it.'))
            ->line(__('The link works for :hours hours, signed in to your account.', ['hours' => $this->hours]));
    }
}
