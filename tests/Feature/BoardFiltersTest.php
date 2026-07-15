<?php

use App\Models\Board;
use App\Models\Card;
use App\Models\Column;
use App\Models\Label;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

function boardWithTwoColumnsAndLabels(User $user): array
{
    $board = Board::factory()->create(['user_id' => $user->id]);
    $col1 = Column::factory()->create(['board_id' => $board->id, 'name' => 'To Do']);
    $col2 = Column::factory()->create(['board_id' => $board->id, 'name' => 'Done']);
    $bug = Label::factory()->create(['board_id' => $board->id, 'name' => 'Bug']);
    $feature = Label::factory()->create(['board_id' => $board->id, 'name' => 'Feature']);

    return compact('board', 'col1', 'col2', 'bug', 'feature');
}

describe('label filter', function () {
    it('shows only cards with the selected label', function () {
        $user = User::factory()->create();
        ['board' => $board, 'col1' => $col1, 'bug' => $bug] = boardWithTwoColumnsAndLabels($user);
        $bugCard = Card::factory()->create(['column_id' => $col1->id, 'title' => 'Fix crash']);
        $bugCard->labels()->attach($bug->id);
        Card::factory()->create(['column_id' => $col1->id, 'title' => 'Unrelated card']);
        $this->actingAs($user);

        Volt::test('boards.show', ['board' => $board])
            ->call('toggleLabelFilter', $bug->id)
            ->assertSee('Fix crash')
            ->assertDontSee('Unrelated card');
    });

    it('clears the label filter when toggled off again', function () {
        $user = User::factory()->create();
        ['board' => $board, 'bug' => $bug] = boardWithTwoColumnsAndLabels($user);
        $this->actingAs($user);

        $component = Volt::test('boards.show', ['board' => $board])
            ->call('toggleLabelFilter', $bug->id);

        expect($component->get('filterLabelIds'))->toBe([$bug->id]);

        $component->call('toggleLabelFilter', $bug->id);

        expect($component->get('filterLabelIds'))->toBe([]);
    });
});

describe('column (status) filter', function () {
    it('shows only cards in the selected column', function () {
        $user = User::factory()->create();
        ['board' => $board, 'col1' => $col1, 'col2' => $col2] = boardWithTwoColumnsAndLabels($user);
        Card::factory()->create(['column_id' => $col1->id, 'title' => 'In to-do']);
        Card::factory()->create(['column_id' => $col2->id, 'title' => 'In done']);
        $this->actingAs($user);

        Volt::test('boards.show', ['board' => $board])
            ->set('filterColumnIds', [$col2->id])
            ->assertSee('In done')
            ->assertDontSee('In to-do');
    });
});

describe('due date filter', function () {
    it('shows only overdue cards', function () {
        $user = User::factory()->create();
        ['board' => $board, 'col1' => $col1] = boardWithTwoColumnsAndLabels($user);
        Card::factory()->create(['column_id' => $col1->id, 'title' => 'Overdue card', 'ends_at' => now()->subDay()]);
        Card::factory()->create(['column_id' => $col1->id, 'title' => 'Future card', 'ends_at' => now()->addWeek()]);
        Card::factory()->create(['column_id' => $col1->id, 'title' => 'No due date card', 'ends_at' => null]);
        $this->actingAs($user);

        Volt::test('boards.show', ['board' => $board])
            ->set('filterDue', 'overdue')
            ->assertSee('Overdue card')
            ->assertDontSee('Future card')
            ->assertDontSee('No due date card');
    });

    it('shows only cards with no due date', function () {
        $user = User::factory()->create();
        ['board' => $board, 'col1' => $col1] = boardWithTwoColumnsAndLabels($user);
        Card::factory()->create(['column_id' => $col1->id, 'title' => 'Has due date', 'ends_at' => now()->addWeek()]);
        Card::factory()->create(['column_id' => $col1->id, 'title' => 'No due date card', 'ends_at' => null]);
        $this->actingAs($user);

        Volt::test('boards.show', ['board' => $board])
            ->set('filterDue', 'no_due_date')
            ->assertSee('No due date card')
            ->assertDontSee('Has due date');
    });
});

describe('combined filters and clearing', function () {
    it('applies label and column filters together (AND across filter types)', function () {
        $user = User::factory()->create();
        ['board' => $board, 'col1' => $col1, 'col2' => $col2, 'bug' => $bug] = boardWithTwoColumnsAndLabels($user);
        $matching = Card::factory()->create(['column_id' => $col1->id, 'title' => 'Matches both']);
        $matching->labels()->attach($bug->id);
        $wrongColumn = Card::factory()->create(['column_id' => $col2->id, 'title' => 'Wrong column']);
        $wrongColumn->labels()->attach($bug->id);
        $this->actingAs($user);

        Volt::test('boards.show', ['board' => $board])
            ->call('toggleLabelFilter', $bug->id)
            ->set('filterColumnIds', [$col1->id])
            ->assertSee('Matches both')
            ->assertDontSee('Wrong column');
    });

    it('reports hasActiveFilters correctly and clears all filters at once', function () {
        $user = User::factory()->create();
        ['board' => $board, 'bug' => $bug] = boardWithTwoColumnsAndLabels($user);
        $this->actingAs($user);

        $component = Volt::test('boards.show', ['board' => $board]);
        expect($component->instance()->hasActiveFilters())->toBeFalse();

        $component->call('toggleLabelFilter', $bug->id);
        expect($component->instance()->hasActiveFilters())->toBeTrue();

        $component->call('clearFilters');
        expect($component->instance()->hasActiveFilters())->toBeFalse();
        expect($component->get('filterLabelIds'))->toBe([]);
        expect($component->get('filterColumnIds'))->toBe([]);
        expect($component->get('filterDue'))->toBe('all');
    });
});
