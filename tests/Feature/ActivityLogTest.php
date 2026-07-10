<?php

use App\Models\Board;
use App\Models\Card;
use App\Models\Column;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

describe('ActivityLogger service', function () {
    it('hashes IP addresses', function () {
        $hash = ActivityLogger::hashIp('192.168.1.1');
        expect($hash)->toBe(hash('sha256', '192.168.1.1'));
        expect($hash)->not->toBe('192.168.1.1');
    });

    it('returns null for null IP', function () {
        expect(ActivityLogger::hashIp(null))->toBeNull();
    });

    it('builds field diffs', function () {
        $diff = ActivityLogger::diff(
            ['name' => 'New Name'],
            ['name' => 'Old Name', 'description' => 'Desc']
        );

        expect($diff)->toBe(['name' => ['old' => 'Old Name', 'new' => 'New Name']]);
    });

    it('logs an event with subject', function () {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);

        ActivityLogger::log('board.created', $board, null, $user->id);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'event' => 'board.created',
            'subject_type' => 'App\Models\Board',
            'subject_id' => $board->id,
        ]);
    });
});

describe('Board observer', function () {
    it('logs board.created when a board is created', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        Volt::test('boards.index')
            ->set('name', 'New Board')
            ->call('create');

        $this->assertDatabaseHas('activity_logs', ['event' => 'board.created']);
    });

    it('logs board.deleted when a board is deleted', function () {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user);

        Volt::test('boards.index')
            ->call('delete', $board->id);

        $this->assertDatabaseHas('activity_logs', [
            'event' => 'board.deleted',
            'subject_id' => $board->id,
        ]);
    });
});

describe('Card observer', function () {
    it('logs card.created when a card is created', function () {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $column = Column::factory()->create(['board_id' => $board->id]);
        $this->actingAs($user);

        Volt::test('boards.show', ['board' => $board])
            ->set('newCardTitle', 'My new card')
            ->call('createCard', $column->id);

        $this->assertDatabaseHas('activity_logs', ['event' => 'card.created']);
    });

    it('logs card.moved when a card is moved to another column', function () {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $col1 = Column::factory()->create(['board_id' => $board->id]);
        $col2 = Column::factory()->create(['board_id' => $board->id]);
        $card = Card::factory()->create(['column_id' => $col1->id]);
        $this->actingAs($user);

        Volt::test('boards.show', ['board' => $board])
            ->call('moveCard', $card->id, $col2->id, 0);

        $this->assertDatabaseHas('activity_logs', [
            'event' => 'card.moved',
            'subject_id' => $card->id,
        ]);
    });
});

describe('Login events', function () {
    it('logs login.success on successful login', function () {
        $user = User::factory()->create(['password' => bcrypt('secret')]);

        $this->post(route('login'), ['email' => $user->email, 'password' => 'secret']);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'event' => 'login.success',
        ]);
    });

    it('logs login.failed on wrong password', function () {
        $user = User::factory()->create(['password' => bcrypt('secret')]);

        $this->post(route('login'), ['email' => $user->email, 'password' => 'wrong']);

        $this->assertDatabaseHas('activity_logs', ['event' => 'login.failed']);
    });
});
