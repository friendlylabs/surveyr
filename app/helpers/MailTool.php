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
            host: getenv('mail_host'),
            username: getenv('mail_username'),
            password: getenv('mail_password'),
            encryption: getenv('mail_encryption'),
            port: intval(getenv('mail_port'))
        );
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
        $mail = new Nette\Mail\Message;
        $mail->setFrom($senderName ?? getenv('mail_username'))
            ->addTo($recipientEmail, $recipientName)
            ->setSubject($subject)
            ->setBody($body);

        $this->mailer->send($mail);
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
        $mail = new Nette\Mail\Message;
        $mail->setFrom($senderName ?? getenv('mail_username'))
            ->setSubject($subject)
            ->setBody($body);

        foreach ($recipients as $recipient) {
            $mail->addTo($recipient['email'], $recipient['name']);
        }

        $this->mailer->send($mail);
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
        $mail = new Nette\Mail\Message;
        $mail->setFrom($senderName ?? getenv('mail_username'))
            ->addTo($recipientEmail, $recipientName)
            ->setSubject($subject)
            ->setHtmlBody($body);

        $this->mailer->send($mail);
    }
}