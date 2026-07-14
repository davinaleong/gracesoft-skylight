<?php

use App\Models\User;
use App\Models\Workspace;
use App\Services\DemoBoardSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

describe('onboarding checklist widget', function () {
    it('shows for a brand new user with all steps incomplete', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        Volt::test('onboarding.checklist')
            ->assertSee('Get started')
            ->assertSee('0 of 3 complete')
            ->assertSee('Create your first board')
            ->assertSee('Invite a teammate')
            ->assertSee('Add a profile photo');
    });

    it('marks "create your first board" done once a real board exists beyond the demo board', function () {
        $user = User::factory()->create();
        DemoBoardSeeder::seed($user); // demo board only = 1 board, step not yet done
        $this->actingAs($user);

        Volt::test('onboarding.checklist')->assertSee('0 of 3 complete');

        $user->boards()->create(['name' => 'Real board', 'position' => 1]);

        Volt::test('onboarding.checklist')->assertSee('1 of 3 complete');
    });

    it('marks "invite a teammate" done once the workspace has more than one member', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        $other = User::factory()->create();
        $user->currentWorkspace()->users()->attach($other->id, ['role' => Workspace::ROLE_MEMBER]);

        Volt::test('onboarding.checklist')->assertSee('1 of 3 complete');
    });

    it('marks "add a profile photo" done once avatar_path is set', function () {
        $user = User::factory()->create(['avatar_path' => 'avatars/me.jpg']);
        $this->actingAs($user);

        Volt::test('onboarding.checklist')->assertSee('1 of 3 complete');
    });

    it('hides once dismissed', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        Volt::test('onboarding.checklist')
            ->call('dismiss')
            ->assertDontSee('Get started');

        expect($user->fresh()->onboarding_dismissed_at)->not->toBeNull();
    });

    it('hides automatically once every step is complete, without needing a dismiss', function () {
        $user = User::factory()->create(['avatar_path' => 'avatars/me.jpg']);
        $this->actingAs($user);

        $other = User::factory()->create();
        $user->currentWorkspace()->users()->attach($other->id, ['role' => Workspace::ROLE_MEMBER]);
        $user->boards()->create(['name' => 'Board one', 'position' => 0]);
        $user->boards()->create(['name' => 'Board two', 'position' => 1]);

        Volt::test('onboarding.checklist')->assertDontSee('Get started');
    });
});
