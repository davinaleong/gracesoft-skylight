<?php

namespace App\Notifications\Workspace;

use App\Models\WorkspaceInvite;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorkspaceInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /** @param string $rawToken The one-time visible token - only passed at creation, never re-read from DB. */
    public function __construct(
        public readonly WorkspaceInvite $invite,
        public readonly string $rawToken,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $acceptUrl = route('invites.show', $this->rawToken);
        $inviterName = $this->invite->inviter?->name ?? 'A teammate';

        return (new MailMessage)
            ->subject($inviterName.' invited you to '.$this->invite->workspace->name)
            ->greeting('Hi,')
            ->line($inviterName.' invited you to join "'.$this->invite->workspace->name.'" on '.config('app.name').' as a '.$this->invite->role.'.')
            ->action('View invitation', $acceptUrl)
            ->line('This invitation expires on '.$this->invite->expires_at->format('M j, Y').'.')
            ->salutation('The '.config('app.name').' team');
    }
}
