<?php

namespace App\Notifications\Board;

use App\Models\Board;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ShareLinkCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /** @param string $rawToken The one-time visible token - only passed at creation, never re-read from DB. */
    public function __construct(
        public readonly Board $board,
        public readonly string $rawToken,
        public readonly bool $canSeeComments,
        public readonly bool $canSeeAttachments,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $viewerUrl = route('viewer', $this->rawToken);

        $perms = collect([
            $this->canSeeComments ? 'comments visible' : null,
            $this->canSeeAttachments ? 'attachments visible' : null,
        ])->filter()->join(', ');

        $message = (new MailMessage)
            ->subject('Share link created: '.$this->board->name)
            ->greeting('Hi '.$notifiable->name.',')
            ->line('A read-only share link was created for your board "'.$this->board->name.'".')
            ->line('Viewer URL (keep this safe): '.$viewerUrl)
            ->action('Open viewer link', $viewerUrl);

        if ($perms) {
            $message->line('Permissions: '.$perms.'.');
        }

        return $message
            ->line('This link is active until you revoke it from your board settings.')
            ->line('This is the only time this URL will appear in an email. Store it securely.')
            ->salutation('The '.config('app.name').' team');
    }
}
