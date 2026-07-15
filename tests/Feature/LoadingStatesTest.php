<?php

use App\Models\Board;
use App\Models\Column;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

describe('loading states', function () {
    it('shows a wire:loading target on the create-board button', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        Volt::test('boards.index')
            ->set('showCreateForm', true)
            ->assertSee('wire:target="create"', false)
            ->assertSee('Creating…');
    });

    it('shows a wire:loading target on the create-column button', function () {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user);

        Volt::test('boards.show', ['board' => $board])
            ->set('showColumnForm', true)
            ->assertSee('wire:target="createColumn"', false)
            ->assertSee('Adding…');
    });

    it('shows a wire:loading target on the create-card button', function () {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $column = Column::factory()->create(['board_id' => $board->id]);
        $this->actingAs($user);

        Volt::test('boards.show', ['board' => $board])
            ->set('addingCardToColumn', $column->id)
            ->assertSee('wire:target="createCard"', false);
    });

    it('shows a wire:loading target on the send-invite button', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('team'))
            ->assertOk()
            ->assertSee('wire:target="invite"', false)
            ->assertSee('Sending…');
    });
});
