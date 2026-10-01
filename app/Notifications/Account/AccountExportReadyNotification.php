<?php

namespace App\Notifications\Account;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class AccountExportReadyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly string $filename) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function downloadUrl(): string
    {
        return URL::temporarySignedRoute(
            'account.export.download',
            now()->addHours(config('exports.link_lifetime_hours')),
            ['file' => $this->filename],
        );
    }

    public function toMail(object $notifiable): MailMessage
    {
        $hours = config('exports.link_lifetime_hours');

        return (new MailMessage)
            ->subject('Your '.config('app.name').' data export is ready')
            ->greeting('Hi '.$notifiable->name.',')
            ->line('Your data export is ready to download. It contains your account details, the boards in workspaces you own, and your comments and notes elsewhere.')
            ->action('Download export', $this->downloadUrl())
            ->line("This link works for {$hours} hours and only while you're signed in to your account.")
            ->line("If you didn't request this export, change your password and review your active sessions.")
            ->salutation('The '.config('app.name').' team');
    }
}
