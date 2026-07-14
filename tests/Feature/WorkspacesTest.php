<?php

use App\Models\Board;
use App\Models\Tag;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

uses(RefreshDatabase::class);

describe('workspace creation', function () {
    it('creates a personal workspace when a user registers with email/password', function () {
        $this->post(route('register'), [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(route('home'));

        $user = User::where('email', 'ada@example.com')->firstOrFail();

        expect($user->workspaces)->toHaveCount(1);

        $workspace = $user->workspaces->first();
        expect($workspace->owner_id)->toBe($user->id);
        expect($workspace->pivot->role)->toBe(Workspace::ROLE_OWNER);
        expect($workspace->name)->toBe("Ada Lovelace's Workspace");
    });

    it('creates a personal workspace for a brand-new oauth user', function () {
        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'g-123',
            'name' => 'Grace Hopper',
            'email' => 'grace@example.com',
        ]));

        $this->get(route('oauth.callback', 'google'))
            ->assertRedirect(route('home'));

        $user = User::where('email', 'grace@example.com')->firstOrFail();

        expect($user->workspaces)->toHaveCount(1);
        expect($user->workspaces->first()->pivot->role)->toBe(Workspace::ROLE_OWNER);
    });

    it('does not create a duplicate workspace when an oauth login links to an existing account', function () {
        $user = User::factory()->create(['email' => 'linked@example.com']);
        $workspace = $user->workspaces->firstOrFail();

        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'g-789',
            'email' => 'linked@example.com',
        ]));

        $this->get(route('oauth.callback', 'google'))
            ->assertRedirect(route('home'));

        expect($user->fresh()->workspaces)->toHaveCount(1);
        expect($user->fresh()->workspaces->first()->id)->toBe($workspace->id);
    });
});

describe('Workspace::createForUser', function () {
    it('generates a uuid and attaches the owner with the owner role', function () {
        $user = User::factory()->create();

        $workspace = Workspace::createForUser($user, 'Acme Inc');

        expect($workspace->uuid)->not->toBeNull();
        expect($workspace->name)->toBe('Acme Inc');
        expect($workspace->users)->toHaveCount(1);
        expect($workspace->users->first()->id)->toBe($user->id);
        expect($workspace->users->first()->pivot->role)->toBe('owner');
    });

    it('defaults the workspace name based on the user name', function () {
        $user = User::factory()->create(['name' => 'Test User']);

        $workspace = Workspace::createForUser($user);

        expect($workspace->name)->toBe("Test User's Workspace");
    });
});

describe('workspace scoping on boards and tags', function () {
    it('auto-assigns a new board to its creator personal workspace', function () {
        $user = User::factory()->create();

        $board = Board::factory()->create(['user_id' => $user->id]);

        expect($board->workspace_id)->toBe($user->workspaces->firstOrFail()->id);
    });

    it('lets an explicit workspace_id override the default', function () {
        $user = User::factory()->create();
        $otherWorkspace = Workspace::factory()->create();

        $board = Board::factory()->create([
            'user_id' => $user->id,
            'workspace_id' => $otherWorkspace->id,
        ]);

        expect($board->workspace_id)->toBe($otherWorkspace->id);
    });

    it('auto-assigns a new tag to its creator personal workspace', function () {
        $user = User::factory()->create();

        $tag = Tag::factory()->create(['user_id' => $user->id]);

        expect($tag->workspace_id)->toBe($user->workspaces->firstOrFail()->id);
    });
});

describe('Workspace::hasMember', function () {
    it('returns true for the owner and false for a stranger', function () {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $workspace = $owner->workspaces->firstOrFail();

        expect($workspace->hasMember($owner))->toBeTrue();
        expect($workspace->hasMember($stranger))->toBeFalse();
    });
});

describe('Workspace::roleOf and canManageMembers', function () {
    it('reports the correct role per member and gates manage permission to owner/admin', function () {
        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $member = User::factory()->create();
        $viewer = User::factory()->create();
        $stranger = User::factory()->create();
        $workspace = $owner->currentWorkspace();

        $workspace->users()->attach($admin->id, ['role' => Workspace::ROLE_ADMIN]);
        $workspace->users()->attach($member->id, ['role' => Workspace::ROLE_MEMBER]);
        $workspace->users()->attach($viewer->id, ['role' => Workspace::ROLE_VIEWER]);

        expect($workspace->roleOf($owner))->toBe(Workspace::ROLE_OWNER);
        expect($workspace->roleOf($admin))->toBe(Workspace::ROLE_ADMIN);
        expect($workspace->roleOf($member))->toBe(Workspace::ROLE_MEMBER);
        expect($workspace->roleOf($viewer))->toBe(Workspace::ROLE_VIEWER);
        expect($workspace->roleOf($stranger))->toBeNull();

        expect($workspace->canManageMembers($owner))->toBeTrue();
        expect($workspace->canManageMembers($admin))->toBeTrue();
        expect($workspace->canManageMembers($member))->toBeFalse();
        expect($workspace->canManageMembers($viewer))->toBeFalse();
        expect($workspace->canManageMembers($stranger))->toBeFalse();
    });
});

describe('Workspace::isOwner, isViewer, canEditContent', function () {
    it('identifies the owner and viewer roles correctly', function () {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $workspace = $owner->currentWorkspace();
        $workspace->users()->attach($viewer->id, ['role' => Workspace::ROLE_VIEWER]);

        expect($workspace->isOwner($owner))->toBeTrue();
        expect($workspace->isOwner($viewer))->toBeFalse();
        expect($workspace->isViewer($viewer))->toBeTrue();
        expect($workspace->isViewer($owner))->toBeFalse();
    });

    it('allows owner/admin/member to edit content but not viewers or non-members', function () {
        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $member = User::factory()->create();
        $viewer = User::factory()->create();
        $stranger = User::factory()->create();
        $workspace = $owner->currentWorkspace();

        $workspace->users()->attach($admin->id, ['role' => Workspace::ROLE_ADMIN]);
        $workspace->users()->attach($member->id, ['role' => Workspace::ROLE_MEMBER]);
        $workspace->users()->attach($viewer->id, ['role' => Workspace::ROLE_VIEWER]);

        expect($workspace->canEditContent($owner))->toBeTrue();
        expect($workspace->canEditContent($admin))->toBeTrue();
        expect($workspace->canEditContent($member))->toBeTrue();
        expect($workspace->canEditContent($viewer))->toBeFalse();
        expect($workspace->canEditContent($stranger))->toBeFalse();
    });
});

describe('Workspace::canChangeMember', function () {
    it('lets a manager change a non-owner member but never the owner, even by themselves', function () {
        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $member = User::factory()->create();
        $workspace = $owner->currentWorkspace();

        $workspace->users()->attach($admin->id, ['role' => Workspace::ROLE_ADMIN]);
        $workspace->users()->attach($member->id, ['role' => Workspace::ROLE_MEMBER]);

        expect($workspace->canChangeMember($owner, $member))->toBeTrue();
        expect($workspace->canChangeMember($admin, $member))->toBeTrue();
        expect($workspace->canChangeMember($member, $member))->toBeFalse(); // member can't manage anyone
        expect($workspace->canChangeMember($owner, $owner))->toBeFalse(); // owner role is untouchable
        expect($workspace->canChangeMember($admin, $owner))->toBeFalse();
    });
});
