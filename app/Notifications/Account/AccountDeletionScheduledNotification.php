<?php

namespace App\Notifications\Account;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class AccountDeletionScheduledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function cancelUrl(object $notifiable): string
    {
        return URL::temporarySignedRoute(
            'account.deletion.cancel',
            $notifiable->deletion_scheduled_at,
            ['user' => $notifiable->getKey()],
        );
    }

    public function toMail(object $notifiable): MailMessage
    {
        $date = $notifiable->deletion_scheduled_at->format('M j, Y \a\t H:i T');

        return (new MailMessage)
            ->subject('Your '.config('app.name').' account will be deleted')
            ->greeting('Hi '.$notifiable->name.',')
            ->line("Your account is scheduled for permanent deletion on {$date}. After that, your account, the workspaces you own, their boards, and all uploaded files are removed and can't be recovered.")
            ->line('Changed your mind? Cancel any time before then.')
            ->action('Keep my account', $this->cancelUrl($notifiable))
            ->line("If you didn't request this, cancel now and change your password.")
            ->salutation('The '.config('app.name').' team');
    }
}
