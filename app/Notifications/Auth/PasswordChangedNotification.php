<?php

namespace App\Notifications\Auth;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your password has been changed')
            ->greeting('Hi '.$notifiable->name.',')
            ->line('Your password was successfully changed on '.now()->format('d M Y \a\t H:i').' UTC.')
            ->line('If you made this change, no action is needed.')
            ->action('Review account security', route('profile'))
            ->line('If you did not change your password, please reset it immediately.')
            ->salutation('The '.config('app.name').' team');
    }
}
