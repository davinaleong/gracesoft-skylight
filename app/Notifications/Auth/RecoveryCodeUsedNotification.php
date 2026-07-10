<?php

namespace App\Notifications\Auth;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RecoveryCodeUsedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('A 2FA recovery code was used on your account')
            ->greeting('Security notice')
            ->line('Hi '.$notifiable->name.', one of your two-factor authentication recovery codes was just used to sign in.')
            ->line('When: '.now()->format('d M Y \a\t H:i').' UTC')
            ->line('Recovery codes are single-use. If you used it yourself, regenerate your remaining codes from profile settings.')
            ->action('Manage 2FA settings', route('profile'))
            ->line('If you did not sign in, your account may be compromised. Change your password immediately.')
            ->salutation('The '.config('app.name').' security team');
    }
}
