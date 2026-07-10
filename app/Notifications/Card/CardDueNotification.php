<?php

namespace App\Notifications\Card;

use App\Models\Card;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class CardDueNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public const TYPE_DUE_TODAY = 'due_today';

    public const TYPE_OVERDUE = 'overdue';

    /** @param Collection<int, Card> $cards */
    public function __construct(
        public readonly Collection $cards,
        public readonly string $type,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $isDueToday = $this->type === self::TYPE_DUE_TODAY;
        $count = $this->cards->count();

        $subject = $isDueToday
            ? "You have {$count} card".($count !== 1 ? 's' : '').' due today'
            : "{$count} overdue card".($count !== 1 ? 's' : '').' need your attention';

        $intro = $isDueToday
            ? 'The following card'.($count !== 1 ? 's are' : ' is').' due today:'
            : 'The following card'.($count !== 1 ? 's are' : ' is').' past their due date:';

        $message = (new MailMessage)
            ->subject($subject)
            ->greeting('Hi '.$notifiable->name.',')
            ->line($intro);

        foreach ($this->cards as $card) {
            $boardName = $card->column->board->name ?? 'Unknown board';
            $due = $card->ends_at?->format('d M Y') ?? '';
            $message->line('- '.$card->title.' ('.$boardName.') - due '.$due);
        }

        return $message
            ->action('Open your boards', route('home'))
            ->salutation('The '.config('app.name').' team');
    }
}
