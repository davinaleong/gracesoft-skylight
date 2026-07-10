<?php

use App\Models\Board;
use App\Models\Card;
use App\Models\Column;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

describe('global search', function () {
    it('finds boards matching the query', function () {
        $user = User::factory()->create();
        $matchingBoard = Board::factory()->create(['user_id' => $user->id, 'name' => 'Product Roadmap']);
        Board::factory()->create(['user_id' => $user->id, 'name' => 'Design Sprint']);
        $this->actingAs($user);

        Volt::test('search.global')
            ->set('open', true)
            ->set('query', 'Product')
            ->assertSee('Product Roadmap')
            ->assertDontSee('Design Sprint');
    });

    it('finds cards matching the query', function () {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $column = Column::factory()->create(['board_id' => $board->id]);
        Card::factory()->create(['column_id' => $column->id, 'title' => 'Fix login bug']);
        Card::factory()->create(['column_id' => $column->id, 'title' => 'Add dark mode']);
        $this->actingAs($user);

        Volt::test('search.global')
            ->set('open', true)
            ->set('query', 'login')
            ->assertSee('Fix login bug')
            ->assertDontSee('Add dark mode');
    });

    it('returns no results for short queries', function () {
        $user = User::factory()->create();
        Board::factory()->create(['user_id' => $user->id, 'name' => 'Alpha Project']);
        $this->actingAs($user);

        Volt::test('search.global')
            ->set('open', true)
            ->set('query', 'a')
            ->assertDontSee('Alpha Project');
    });

    it('does not return boards from other users', function () {
        $user = User::factory()->create();
        $other = User::factory()->create();
        Board::factory()->create(['user_id' => $other->id, 'name' => 'Secret Board']);
        $this->actingAs($user);

        Volt::test('search.global')
            ->set('open', true)
            ->set('query', 'Secret')
            ->assertDontSee('Secret Board');
    });
});
