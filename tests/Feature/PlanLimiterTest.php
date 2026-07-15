<?php

use App\Models\Board;
use App\Models\User;
use App\Models\Workspace;
use App\Services\PlanLimiter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

describe('PlanLimiter', function () {
    it('defaults every new workspace to the free plan', function () {
        $user = User::factory()->create();

        expect($user->currentWorkspace()->plan)->toBe('free');
    });

    it('allows unlimited boards/members on a plan with null limits', function () {
        $user = User::factory()->create();
        $workspace = $user->currentWorkspace();
        $workspace->update(['plan' => 'team']);

        Board::factory()->count(30)->create(['user_id' => $user->id]);

        expect(PlanLimiter::canCreateBoard($workspace->fresh()))->toBeTrue();
    });

    it('blocks board creation once the free plan limit (3) is reached', function () {
        $user = User::factory()->create();
        $workspace = $user->currentWorkspace();

        Board::factory()->count(3)->create(['user_id' => $user->id]);

        expect(PlanLimiter::canCreateBoard($workspace->fresh()))->toBeFalse();
    });

    it('blocks inviting once the free plan member limit (3) is reached', function () {
        $user = User::factory()->create();
        $workspace = $user->currentWorkspace();
        $workspace->users()->attach(User::factory()->create()->id, ['role' => Workspace::ROLE_MEMBER]);
        $workspace->users()->attach(User::factory()->create()->id, ['role' => Workspace::ROLE_MEMBER]);

        expect(PlanLimiter::canInviteMember($workspace->fresh()))->toBeFalse();
    });
});

describe('board creation respects the plan limit', function () {
    it('rejects creating a 4th board on the free plan', function () {
        $user = User::factory()->create();
        Board::factory()->count(3)->create(['user_id' => $user->id]);
        $this->actingAs($user);

        Volt::test('boards.index')
            ->set('name', 'One too many')
            ->call('create')
            ->assertHasErrors(['name']);

        expect(Board::where('name', 'One too many')->exists())->toBeFalse();
    });
});

describe('invites respect the plan limit', function () {
    it('rejects inviting a 4th member on the free plan', function () {
        $owner = User::factory()->create();
        $workspace = $owner->currentWorkspace();
        $workspace->users()->attach(User::factory()->create()->id, ['role' => Workspace::ROLE_MEMBER]);
        $workspace->users()->attach(User::factory()->create()->id, ['role' => Workspace::ROLE_MEMBER]);
        $this->actingAs($owner);

        Volt::test('workspaces.team', ['workspace' => $workspace])
            ->set('email', 'toomany@example.com')
            ->call('invite')
            ->assertHasErrors(['email']);
    });
});
