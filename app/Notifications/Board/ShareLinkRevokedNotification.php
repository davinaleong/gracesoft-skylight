<?php

namespace App\Notifications\Board;

use App\Models\Board;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ShareLinkRevokedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Board $board,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Share link revoked: '.$this->board->name)
            ->greeting('Hi '.$notifiable->name.',')
            ->line('A read-only share link for your board "'.$this->board->name.'" was revoked.')
            ->line('Anyone previously given that link can no longer access the board.')
            ->action('Manage share links', route('boards.show', $this->board))
            ->salutation('The '.config('app.name').' team');
    }
}
