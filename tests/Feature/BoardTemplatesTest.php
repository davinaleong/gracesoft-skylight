<?php

use App\Models\Board;
use App\Models\User;
use App\Services\BoardTemplates;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('BoardTemplates', function () {
    it('validates known template keys', function () {
        expect(BoardTemplates::isValid('blank'))->toBeTrue();
        expect(BoardTemplates::isValid('sprint'))->toBeTrue();
        expect(BoardTemplates::isValid('content_calendar'))->toBeTrue();
        expect(BoardTemplates::isValid('client_onboarding'))->toBeTrue();
        expect(BoardTemplates::isValid('not-a-template'))->toBeFalse();
    });

    it('applies no columns for the blank template', function () {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);

        BoardTemplates::apply($board, 'blank');

        expect($board->columns)->toHaveCount(0);
    });

    it('applies the content calendar template in order', function () {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);

        BoardTemplates::apply($board, 'content_calendar');

        expect($board->columns()->orderBy('position')->pluck('name')->all())
            ->toBe(['Ideas', 'Writing', 'Editing', 'Scheduled', 'Published']);
    });

    it('applies the client onboarding template in order', function () {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);

        BoardTemplates::apply($board, 'client_onboarding');

        expect($board->columns()->orderBy('position')->pluck('name')->all())
            ->toBe(['New Client', 'Kickoff', 'In Progress', 'Review', 'Complete']);
    });
});
