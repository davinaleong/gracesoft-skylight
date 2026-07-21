<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FeedbackReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $fromName,
        public string $fromEmail,
        public string $message,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New feedback: '.config('app.name'))
            ->replyTo($this->fromEmail, $this->fromName)
            ->greeting('New feedback submitted')
            ->line("From: {$this->fromName} ({$this->fromEmail})")
            ->line($this->message)
            ->line('Reply directly to this email to respond to them.');
    }
}
