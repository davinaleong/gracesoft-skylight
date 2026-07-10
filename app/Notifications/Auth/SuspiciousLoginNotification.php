<?php

namespace App\Notifications\Auth;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SuspiciousLoginNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $failedAttempts,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Suspicious login activity on your account')
            ->greeting('Security alert')
            ->line('Hi '.$notifiable->name.', we detected '.$this->failedAttempts.' consecutive failed login attempts on your account in the last 10 minutes.')
            ->line('This may indicate someone is trying to access your account without authorisation.')
            ->action('Review account security', route('profile'))
            ->line('If this was you (e.g. you forgot your password), you can safely ignore this email.')
            ->line('If this was not you, reset your password immediately and enable two-factor authentication.')
            ->salutation('The '.config('app.name').' security team');
    }
}
