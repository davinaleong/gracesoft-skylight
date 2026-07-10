<?php

namespace App\Notifications\Auth;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewIpLoginNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New sign-in to your account')
            ->greeting('Hi '.$notifiable->name.',')
            ->line('We noticed a sign-in to your '.config('app.name').' account from a location we have not seen before.')
            ->line('When: '.now()->format('d M Y \a\t H:i').' UTC')
            ->line('If this was you, no action is needed.')
            ->action('Review your account', route('profile'))
            ->line('If this was not you, please change your password immediately and enable two-factor authentication.')
            ->salutation('The '.config('app.name').' security team');
    }
}
