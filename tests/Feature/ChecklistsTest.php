<?php

use App\Models\Board;
use App\Models\Card;
use App\Models\Checklist;
use App\Models\ChecklistItem;
use App\Models\Column;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

function makeCardOnBoard(User $user): array
{
    $board = Board::factory()->create(['user_id' => $user->id]);
    $column = Column::factory()->create(['board_id' => $board->id]);
    $card = Card::factory()->create(['column_id' => $column->id]);

    return [$board, $column, $card];
}

describe('dates', function () {
    it('saves start and end dates on a card', function () {
        $user = User::factory()->create();
        [, , $card] = makeCardOnBoard($user);
        $this->actingAs($user);

        Volt::test('cards.detail', ['card' => $card])
            ->set('startsAt', '2026-08-01')
            ->set('endsAt', '2026-08-31')
            ->call('saveDates')
            ->assertHasNoErrors();

        expect($card->fresh()->starts_at->format('Y-m-d'))->toBe('2026-08-01');
        expect($card->fresh()->ends_at->format('Y-m-d'))->toBe('2026-08-31');
    });

    it('rejects end date before start date', function () {
        $user = User::factory()->create();
        [, , $card] = makeCardOnBoard($user);
        $this->actingAs($user);

        Volt::test('cards.detail', ['card' => $card])
            ->set('startsAt', '2026-08-31')
            ->set('endsAt', '2026-08-01')
            ->call('saveDates')
            ->assertHasErrors(['endsAt']);
    });
});

describe('checklists', function () {
    it('creates a checklist', function () {
        $user = User::factory()->create();
        [, , $card] = makeCardOnBoard($user);
        $this->actingAs($user);

        Volt::test('cards.detail', ['card' => $card])
            ->set('newChecklistName', 'Acceptance criteria')
            ->call('createChecklist')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('checklists', [
            'card_id' => $card->id,
            'name' => 'Acceptance criteria',
        ]);
    });

    it('deletes a checklist', function () {
        $user = User::factory()->create();
        [, , $card] = makeCardOnBoard($user);
        $checklist = Checklist::factory()->create(['card_id' => $card->id]);
        $this->actingAs($user);

        Volt::test('cards.detail', ['card' => $card])
            ->call('deleteChecklist', $checklist->id)
            ->assertHasNoErrors();

        $this->assertModelMissing($checklist);
    });
});

describe('checklist items', function () {
    it('creates a checklist item', function () {
        $user = User::factory()->create();
        [, , $card] = makeCardOnBoard($user);
        $checklist = Checklist::factory()->create(['card_id' => $card->id]);
        $this->actingAs($user);

        Volt::test('cards.detail', ['card' => $card])
            ->set('newItemBody', 'Write unit tests')
            ->call('createItem', $checklist->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('checklist_items', [
            'checklist_id' => $checklist->id,
            'body' => 'Write unit tests',
        ]);
    });

    it('toggles a checklist item', function () {
        $user = User::factory()->create();
        [, , $card] = makeCardOnBoard($user);
        $checklist = Checklist::factory()->create(['card_id' => $card->id]);
        $item = ChecklistItem::factory()->create(['checklist_id' => $checklist->id, 'is_completed' => false]);
        $this->actingAs($user);

        Volt::test('cards.detail', ['card' => $card])
            ->call('toggleItem', $item->id)
            ->assertHasNoErrors();

        expect($item->fresh()->is_completed)->toBeTrue();
    });

    it('deletes a checklist item', function () {
        $user = User::factory()->create();
        [, , $card] = makeCardOnBoard($user);
        $checklist = Checklist::factory()->create(['card_id' => $card->id]);
        $item = ChecklistItem::factory()->create(['checklist_id' => $checklist->id]);
        $this->actingAs($user);

        Volt::test('cards.detail', ['card' => $card])
            ->call('deleteItem', $item->id)
            ->assertHasNoErrors();

        $this->assertModelMissing($item);
    });
});
