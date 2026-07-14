<?php

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvite;
use App\Notifications\Workspace\WorkspaceInvitationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

describe('sending invites', function () {
    it('lets the workspace owner invite a teammate by email', function () {
        Notification::fake();

        $owner = User::factory()->create();
        $this->actingAs($owner);

        Volt::test('workspaces.team')
            ->set('email', 'teammate@example.com')
            ->set('role', Workspace::ROLE_MEMBER)
            ->call('invite')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('workspace_invites', [
            'workspace_id' => $owner->currentWorkspace()->id,
            'email' => 'teammate@example.com',
            'role' => Workspace::ROLE_MEMBER,
        ]);

        Notification::assertSentOnDemand(WorkspaceInvitationNotification::class);
    });

    it('rejects an invalid role', function () {
        $owner = User::factory()->create();
        $this->actingAs($owner);

        Volt::test('workspaces.team')
            ->set('email', 'teammate@example.com')
            ->set('role', 'superadmin')
            ->call('invite')
            ->assertHasErrors(['role']);
    });

    it('rejects inviting someone who is already a member', function () {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $owner->currentWorkspace()->users()->attach($other->id, ['role' => Workspace::ROLE_MEMBER]);
        $this->actingAs($owner);

        Volt::test('workspaces.team')
            ->set('email', $other->email)
            ->set('role', Workspace::ROLE_MEMBER)
            ->call('invite')
            ->assertHasErrors(['email']);
    });

    it('forbids a non-manager from inviting on a workspace they do not own', function () {
        // currentWorkspace() always resolves to the user's own (owned) workspace today --
        // there's no workspace switcher yet -- so this exercises the underlying
        // Workspace::canManageMembers() check directly rather than through the Volt
        // component, which only ever operates on the acting user's own workspace.
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $workspace = $owner->currentWorkspace();
        $workspace->users()->attach($member->id, ['role' => Workspace::ROLE_MEMBER]);

        expect($workspace->canManageMembers($member))->toBeFalse();
        expect($workspace->canManageMembers($owner))->toBeTrue();
    });

    it('re-inviting the same email refreshes the existing invite instead of erroring', function () {
        Notification::fake();

        $owner = User::factory()->create();
        $this->actingAs($owner);

        Volt::test('workspaces.team')->set('email', 'teammate@example.com')->call('invite');
        Volt::test('workspaces.team')->set('email', 'teammate@example.com')->call('invite')->assertHasNoErrors();

        expect(WorkspaceInvite::where('email', 'teammate@example.com')->count())->toBe(1);
    });
});

describe('accepting invites', function () {
    it('shows the invite to a guest with sign-up/sign-in links', function () {
        ['token' => $token, 'hash' => $hash] = WorkspaceInvite::generateToken();
        WorkspaceInvite::factory()->create([
            'email' => 'invitee@example.com',
            'token_hash' => $hash,
        ]);

        $this->get(route('invites.show', $token))
            ->assertOk()
            ->assertSee('Create an account')
            ->assertSee('Sign in');
    });

    it('lets a matching authenticated user accept the invite', function () {
        $workspace = Workspace::factory()->create();
        ['token' => $token, 'hash' => $hash] = WorkspaceInvite::generateToken();
        $invite = WorkspaceInvite::factory()->create([
            'workspace_id' => $workspace->id,
            'email' => 'invitee@example.com',
            'role' => Workspace::ROLE_ADMIN,
            'token_hash' => $hash,
        ]);

        $user = User::factory()->create(['email' => 'invitee@example.com']);

        $this->actingAs($user)
            ->get(route('invites.show', $token))
            ->assertOk()
            ->assertSee('Accept invitation');

        $this->actingAs($user)
            ->post(route('invites.accept', $token))
            ->assertRedirect(route('home'));

        expect($workspace->fresh()->hasMember($user))->toBeTrue();
        expect($workspace->fresh()->roleOf($user))->toBe(Workspace::ROLE_ADMIN);
        expect($invite->fresh()->isAccepted())->toBeTrue();
    });

    it('refuses to accept when the logged-in email does not match the invite', function () {
        ['token' => $token, 'hash' => $hash] = WorkspaceInvite::generateToken();
        $invite = WorkspaceInvite::factory()->create([
            'email' => 'invitee@example.com',
            'token_hash' => $hash,
        ]);

        $stranger = User::factory()->create(['email' => 'stranger@example.com']);

        $this->actingAs($stranger)
            ->post(route('invites.accept', $token))
            ->assertForbidden();

        expect($invite->fresh()->isAccepted())->toBeFalse();
    });

    it('refuses to accept an expired invite', function () {
        ['token' => $token, 'hash' => $hash] = WorkspaceInvite::generateToken();
        $invite = WorkspaceInvite::factory()->expired()->create([
            'email' => 'invitee@example.com',
            'token_hash' => $hash,
        ]);

        $user = User::factory()->create(['email' => 'invitee@example.com']);

        $this->actingAs($user)
            ->post(route('invites.accept', $token))
            ->assertStatus(410);
    });

    it('404s for an unknown token', function () {
        $this->get(route('invites.show', 'does-not-exist'))->assertNotFound();
    });
});
