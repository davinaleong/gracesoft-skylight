<?php

namespace App\Notifications\Auth;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Welcome to '.config('app.name'))
            ->greeting('Welcome, '.$notifiable->name.'!')
            ->line('Your account has been created. You can now create boards, manage your kanban cards, and share read-only links with clients.')
            ->action('Go to your boards', route('home'))
            ->line('If you did not create this account, please contact support immediately.');
    }
}
