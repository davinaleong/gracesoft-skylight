<?php

namespace App\Services;

use App\Models\Board;
use App\Models\User;

class DemoBoardSeeder
{
    /**
     * Create a sample board so a brand-new user has something to look at
     * (and learn from) instead of a blank slate. Called only from the real
     * signup flows, not from UserObserver -- factories/seeders/tinker users
     * don't need demo content, only people who just signed up do.
     */
    public static function seed(User $user): Board
    {
        $workspace = $user->currentWorkspace();

        $board = Board::create([
            'user_id' => $user->id,
            'workspace_id' => $workspace->id,
            'name' => 'Welcome to '.config('app.name', 'Skylight'),
            'description' => "A quick tour of what you can do here. Delete this board whenever you're ready to start your own.",
            'position' => 0,
        ]);

        $todo = $board->columns()->create(['name' => 'To Do', 'position' => 0]);
        $inProgress = $board->columns()->create(['name' => 'In Progress', 'position' => 1]);
        $done = $board->columns()->create(['name' => 'Done', 'position' => 2]);

        $welcomeCard = $todo->cards()->create([
            'title' => 'Click me to see a card in detail',
            'description' => 'Cards can have **markdown** descriptions, checklists, comments, attachments, and due dates.',
            'position' => 0,
        ]);

        $checklist = $welcomeCard->checklists()->create(['name' => 'Try this']);
        $checklist->items()->createMany([
            ['body' => 'Check off this item', 'position' => 0],
            ['body' => 'Drag this card to another column', 'position' => 1],
        ]);

        $todo->cards()->create([
            'title' => 'Invite your team',
            'description' => 'Head to the **Team** page in the nav bar to invite teammates and assign roles.',
            'position' => 1,
        ]);

        $inProgress->cards()->create([
            'title' => 'Try dragging a card between columns',
            'position' => 0,
        ]);

        $done->cards()->create([
            'title' => "You're all set — create your first real board from the boards page!",
            'position' => 0,
        ]);

        return $board;
    }
}
