<?php

use App\Models\Board;
use App\Models\Card;
use App\Models\Checklist;
use App\Models\ChecklistItem;
use App\Models\Column;
use App\Models\Comment;
use App\Models\Label;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function seedExportableBoard(User $user): array
{
    $board = Board::factory()->create(['user_id' => $user->id, 'name' => 'Q3 Roadmap']);
    $column = Column::factory()->create(['board_id' => $board->id, 'name' => 'To Do']);
    $card = Card::factory()->create(['column_id' => $column->id, 'title' => 'Ship the export feature']);
    $label = Label::factory()->create(['board_id' => $board->id, 'name' => 'Urgent']);
    $card->labels()->attach($label->id);
    $checklist = Checklist::factory()->create(['card_id' => $card->id, 'name' => 'Steps']);
    ChecklistItem::factory()->create(['checklist_id' => $checklist->id, 'body' => 'Write tests', 'is_completed' => true, 'position' => 0]);
    ChecklistItem::factory()->create(['checklist_id' => $checklist->id, 'body' => 'Ship it', 'is_completed' => false, 'position' => 1]);
    Comment::factory()->create(['card_id' => $card->id, 'user_id' => $user->id, 'body' => 'Looks good']);

    return [$board, $column, $card];
}

describe('board export', function () {
    it('rejects an unauthenticated request', function () {
        $board = Board::factory()->create();

        $this->get(route('boards.export', $board))->assertRedirect(route('login'));
    });

    it('forbids export by someone outside the workspace', function () {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($stranger)->get(route('boards.export', $board))->assertForbidden();
    });

    it('exports as json by default with the full nested structure', function () {
        $user = User::factory()->create();
        [$board] = seedExportableBoard($user);

        $response = $this->actingAs($user)->get(route('boards.export', $board));

        $response->assertOk();
        expect($response->headers->get('Content-Type'))->toContain('application/json');
        expect($response->headers->get('Content-Disposition'))->toContain('attachment');

        $data = json_decode($response->getContent(), true);
        expect($data['board']['name'])->toBe('Q3 Roadmap');
        expect($data['columns'][0]['name'])->toBe('To Do');

        $card = $data['columns'][0]['cards'][0];
        expect($card['title'])->toBe('Ship the export feature');
        expect($card['labels'][0]['name'])->toBe('Urgent');
        expect($card['checklists'][0]['name'])->toBe('Steps');
        expect($card['checklists'][0]['items'])->toHaveCount(2);
        expect($card['comments'][0]['body'])->toBe('Looks good');
    });

    it('exports as csv with one row per card', function () {
        $user = User::factory()->create();
        [$board] = seedExportableBoard($user);

        $response = $this->actingAs($user)->get(route('boards.export', ['board' => $board, 'format' => 'csv']));

        $response->assertOk();
        expect($response->headers->get('Content-Type'))->toContain('text/csv');

        $lines = array_filter(explode("\n", str_replace("\r\n", "\n", $response->streamedContent())));
        expect($lines)->toHaveCount(2); // header + 1 card

        expect($lines[0])->toBe('column,title,description,color,starts_at,ends_at,labels,checklist_progress,comments_count');
        expect($lines[1])->toContain('To Do');
        expect($lines[1])->toContain('Ship the export feature');
        expect($lines[1])->toContain('Urgent');
        expect($lines[1])->toContain('1/2'); // one of two checklist items completed
    });

    it('allows a viewer to export (read-only access)', function () {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $workspace = $owner->currentWorkspace();
        $workspace->users()->attach($viewer->id, ['role' => Workspace::ROLE_VIEWER]);
        $board = Board::factory()->create(['user_id' => $owner->id, 'workspace_id' => $workspace->id]);

        $this->actingAs($viewer)->get(route('boards.export', $board))->assertOk();
    });

    it('falls back to json for an unrecognized format value', function () {
        $user = User::factory()->create();
        [$board] = seedExportableBoard($user);

        $response = $this->actingAs($user)->get(route('boards.export', ['board' => $board, 'format' => 'xml']));

        $response->assertOk();
        expect($response->headers->get('Content-Type'))->toContain('application/json');
    });
});
