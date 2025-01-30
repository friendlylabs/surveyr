<?php

namespace App\Helpers;

use Nette;
use Nette\Mail\SmtpMailer;

class MailTool
{
    private $mailer;

    public function __construct()
    {
        $this->mailer = new SmtpMailer(
            host: _env('MAIL_HOST'),
            username: _env('MAIL_USERNAME'),
            password: _env('MAIL_PASSWORD'),
            encryption: _env('MAIL_ENCRYPTION'),
            port: intval(_env('MAIL_PORT'))
        );
    }

    private function sendAsync(callable $callback)
    {
        $fiber = new \Fiber($callback);
        $fiber->start();
    }

    /**
     * Send email
     * 
     * @param string $subject
     * @param string $body
     * @param string $recipientEmail
     * @param string $recipientName
     * @param string $senderName
     * 
     * @return void
     */
    public function send($subject, $body, $recipientEmail, $recipientName, $senderName = null)
    {
        $this->sendAsync(function () use ($subject, $body, $recipientEmail, $recipientName, $senderName) {
            $mail = new Nette\Mail\Message;
            $mail->setFrom($senderName ?? _env('MAIL_USERNAME'))
                ->addTo($recipientEmail, $recipientName)
                ->setSubject($subject)
                ->setBody($body);

            $this->mailer->send($mail);
        });
    }

    /**
     * Send email to multiple recipients
     * 
     * @param string $subject
     * @param string $body
     * @param array $recipients
     * @param string $senderName
     * 
     * @return void
     */
    public function sendToAll($subject, $body, $recipients, $senderName = null)
    {
        $this->sendAsync(function () use ($subject, $body, $recipients, $senderName) {
            $mail = new Nette\Mail\Message;
            $mail->setFrom($senderName ?? _env('MAIL_USERNAME'))
                ->setSubject($subject)
                ->setBody($body);

            foreach ($recipients as $recipient) {
                $mail->addTo($recipient['email'], $recipient['name']);
            }

            $this->mailer->send($mail);
        });
    }
    
    /**
     * HTML email
     * 
     * @param string $subject
     * @param string $body 
     * @param string $recipientEmail
     * @param string $recipientName
     * @param string $senderName
     */
    public function sendHtml($subject, $body, $recipientEmail, $recipientName, $senderName = null)
    {
        $this->sendAsync(function () use ($subject, $body, $recipientEmail, $recipientName, $senderName) {
            $mail = new Nette\Mail\Message;
            $mail->setFrom($senderName ?? _env('MAIL_USERNAME'))
                ->addTo($recipientEmail, $recipientName)
                ->setSubject($subject)
                ->setHtmlBody($body);

            $this->mailer->send($mail);
        });
    }
}