<?php

namespace App\Notifications\Card;

use App\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class CommentMentionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Comment $comment,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $card = $this->comment->card;
        $author = $this->comment->user;

        return (new MailMessage)
            ->subject($author->name.' mentioned you in a comment')
            ->greeting('Hi '.$notifiable->name.',')
            ->line($author->name.' mentioned you in a comment on "'.$card->title.'":')
            ->line('"'.$this->comment->body.'"')
            ->action('View card', route('boards.show', $card->column->board))
            ->salutation('The '.config('app.name').' team');
    }

    public function toArray(object $notifiable): array
    {
        $card = $this->comment->card;

        return [
            'title' => $this->comment->user->name.' mentioned you',
            'body' => 'On "'.$card->title.'": '.Str::limit($this->comment->body, 120),
            'url' => route('boards.show', $card->column->board),
        ];
    }
}
