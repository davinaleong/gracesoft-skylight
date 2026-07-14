<?php

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvite;
use App\Notifications\Workspace\WorkspaceInvitationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

describe('changing a member role', function () {
    it('lets the owner change a member to admin', function () {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $owner->currentWorkspace()->users()->attach($member->id, ['role' => Workspace::ROLE_MEMBER]);
        $this->actingAs($owner);

        Volt::test('workspaces.team', ['workspace' => $owner->currentWorkspace()])
            ->call('changeRole', $member->id, Workspace::ROLE_ADMIN)
            ->assertHasNoErrors();

        expect($owner->currentWorkspace()->roleOf($member))->toBe(Workspace::ROLE_ADMIN);
    });

    it('rejects an invalid role', function () {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $owner->currentWorkspace()->users()->attach($member->id, ['role' => Workspace::ROLE_MEMBER]);
        $this->actingAs($owner);

        Volt::test('workspaces.team', ['workspace' => $owner->currentWorkspace()])
            ->call('changeRole', $member->id, 'superadmin')
            ->assertStatus(422);
    });

    it('refuses to let anyone change the owner role, even the owner themselves', function () {
        $owner = User::factory()->create();
        $this->actingAs($owner);

        Volt::test('workspaces.team', ['workspace' => $owner->currentWorkspace()])
            ->call('changeRole', $owner->id, Workspace::ROLE_MEMBER)
            ->assertForbidden();

        expect($owner->currentWorkspace()->roleOf($owner))->toBe(Workspace::ROLE_OWNER);
    });

    it('forbids a member from changing another member\'s role', function () {
        $owner = User::factory()->create();
        $memberA = User::factory()->create();
        $memberB = User::factory()->create();
        $owner->currentWorkspace()->users()->attach($memberA->id, ['role' => Workspace::ROLE_MEMBER]);
        $owner->currentWorkspace()->users()->attach($memberB->id, ['role' => Workspace::ROLE_MEMBER]);
        $this->actingAs($memberA);

        Volt::test('workspaces.team', ['workspace' => $owner->currentWorkspace()])
            ->call('changeRole', $memberB->id, Workspace::ROLE_ADMIN)
            ->assertForbidden();
    });
});

describe('removing a member', function () {
    it('lets an admin remove a member', function () {
        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $member = User::factory()->create();
        $workspace = $owner->currentWorkspace();
        $workspace->users()->attach($admin->id, ['role' => Workspace::ROLE_ADMIN]);
        $workspace->users()->attach($member->id, ['role' => Workspace::ROLE_MEMBER]);
        $this->actingAs($admin);

        Volt::test('workspaces.team', ['workspace' => $owner->currentWorkspace()])
            ->call('removeMember', $member->id)
            ->assertHasNoErrors();

        expect($workspace->fresh()->hasMember($member))->toBeFalse();
    });

    it('refuses to remove the owner', function () {
        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $owner->currentWorkspace()->users()->attach($admin->id, ['role' => Workspace::ROLE_ADMIN]);
        $this->actingAs($admin);

        Volt::test('workspaces.team', ['workspace' => $owner->currentWorkspace()])
            ->call('removeMember', $owner->id)
            ->assertForbidden();

        expect($owner->currentWorkspace()->fresh()->hasMember($owner))->toBeTrue();
    });
});

describe('managing pending invites', function () {
    it('resends an invite with a fresh token and expiry', function () {
        Notification::fake();

        $owner = User::factory()->create();
        $invite = WorkspaceInvite::factory()->create([
            'workspace_id' => $owner->currentWorkspace()->id,
            'invited_by' => $owner->id,
            'expires_at' => now()->addHours(2),
        ]);
        $originalHash = $invite->token_hash;
        $this->actingAs($owner);

        Volt::test('workspaces.team', ['workspace' => $owner->currentWorkspace()])
            ->call('resendInvite', $invite->id)
            ->assertHasNoErrors();

        $invite->refresh();
        expect($invite->token_hash)->not->toBe($originalHash);
        expect($invite->expires_at->isAfter(now()->addDays(6)))->toBeTrue();

        Notification::assertSentOnDemand(WorkspaceInvitationNotification::class);
    });

    it('cancels a pending invite', function () {
        $owner = User::factory()->create();
        $invite = WorkspaceInvite::factory()->create([
            'workspace_id' => $owner->currentWorkspace()->id,
            'invited_by' => $owner->id,
        ]);
        $this->actingAs($owner);

        Volt::test('workspaces.team', ['workspace' => $owner->currentWorkspace()])
            ->call('cancelInvite', $invite->id)
            ->assertHasNoErrors();

        $this->assertModelMissing($invite);
    });

    it('forbids a member from resending or cancelling invites', function () {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $owner->currentWorkspace()->users()->attach($member->id, ['role' => Workspace::ROLE_MEMBER]);
        $invite = WorkspaceInvite::factory()->create(['workspace_id' => $owner->currentWorkspace()->id]);
        $this->actingAs($member);

        Volt::test('workspaces.team', ['workspace' => $owner->currentWorkspace()])
            ->call('resendInvite', $invite->id)
            ->assertForbidden();
    });
});
