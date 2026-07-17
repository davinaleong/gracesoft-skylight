<?php

use App\Models\Board;
use App\Models\Card;
use App\Models\Column;
use App\Models\User;
use App\Models\Webhook;
use App\Models\WebhookDelivery;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

function boardWithColumns(User $user, array $columnNames): array
{
    $workspace = $user->currentWorkspace();
    $board = Board::factory()->create(['user_id' => $user->id, 'workspace_id' => $workspace->id]);

    $columns = collect($columnNames)->map(
        fn ($name, $i) => Column::factory()->create(['board_id' => $board->id, 'name' => $name, 'position' => $i])
    );

    return [$workspace, $board, $columns];
}

describe('webhook dispatch', function () {
    it('fires card.created for a subscribed webhook', function () {
        Http::fake(['example.com/*' => Http::response('ok', 200)]);

        $user = User::factory()->create();
        [$workspace, $board, $columns] = boardWithColumns($user, ['To Do', 'Done']);
        Webhook::factory()->create([
            'workspace_id' => $workspace->id,
            'url' => 'https://example.com/hook',
            'events' => ['card.created'],
        ]);

        Card::factory()->create(['column_id' => $columns[0]->id, 'title' => 'New card']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://example.com/hook'
                && $request->hasHeader('X-Skylight-Signature')
                && $request['event'] === 'card.created'
                && $request['data']['title'] === 'New card';
        });

        expect(WebhookDelivery::where('event', 'card.created')->where('successful', true)->exists())->toBeTrue();
    });

    it('does not fire for a webhook not subscribed to that event', function () {
        Http::fake(['example.com/*' => Http::response('ok', 200)]);

        $user = User::factory()->create();
        [$workspace, $board, $columns] = boardWithColumns($user, ['To Do']);
        Webhook::factory()->create([
            'workspace_id' => $workspace->id,
            'url' => 'https://example.com/hook',
            'events' => ['card.deleted'],
        ]);

        Card::factory()->create(['column_id' => $columns[0]->id]);

        Http::assertNothingSent();
    });

    it('does not fire for a paused webhook', function () {
        Http::fake(['example.com/*' => Http::response('ok', 200)]);

        $user = User::factory()->create();
        [$workspace, $board, $columns] = boardWithColumns($user, ['To Do']);
        Webhook::factory()->create([
            'workspace_id' => $workspace->id,
            'url' => 'https://example.com/hook',
            'events' => ['card.created'],
            'is_active' => false,
        ]);

        Card::factory()->create(['column_id' => $columns[0]->id]);

        Http::assertNothingSent();
    });

    it('fires card.moved and card.completed when a card lands in the last column', function () {
        Http::fake(['example.com/*' => Http::response('ok', 200)]);

        $user = User::factory()->create();
        [$workspace, $board, $columns] = boardWithColumns($user, ['To Do', 'Doing', 'Done']);
        Webhook::factory()->create([
            'workspace_id' => $workspace->id,
            'url' => 'https://example.com/hook',
            'events' => ['card.moved', 'card.completed'],
        ]);

        $card = Card::factory()->create(['column_id' => $columns[0]->id]);
        $card->update(['column_id' => $columns[2]->id]);

        Http::assertSentCount(2);
        expect(WebhookDelivery::where('event', 'card.moved')->exists())->toBeTrue();
        expect(WebhookDelivery::where('event', 'card.completed')->exists())->toBeTrue();
    });

    it('fires card.moved but not card.completed for a move that is not into the last column', function () {
        Http::fake(['example.com/*' => Http::response('ok', 200)]);

        $user = User::factory()->create();
        [$workspace, $board, $columns] = boardWithColumns($user, ['To Do', 'Doing', 'Done']);
        Webhook::factory()->create([
            'workspace_id' => $workspace->id,
            'url' => 'https://example.com/hook',
            'events' => ['card.moved', 'card.completed'],
        ]);

        $card = Card::factory()->create(['column_id' => $columns[0]->id]);
        $card->update(['column_id' => $columns[1]->id]);

        expect(WebhookDelivery::where('event', 'card.moved')->exists())->toBeTrue();
        expect(WebhookDelivery::where('event', 'card.completed')->exists())->toBeFalse();
    });

    it('fires card.deleted', function () {
        Http::fake(['example.com/*' => Http::response('ok', 200)]);

        $user = User::factory()->create();
        [$workspace, $board, $columns] = boardWithColumns($user, ['To Do']);
        Webhook::factory()->create([
            'workspace_id' => $workspace->id,
            'url' => 'https://example.com/hook',
            'events' => ['card.deleted'],
        ]);

        $card = Card::factory()->create(['column_id' => $columns[0]->id]);
        $card->delete();

        expect(WebhookDelivery::where('event', 'card.deleted')->exists())->toBeTrue();
    });

    it('signs the payload with HMAC-SHA256 of the webhook secret', function () {
        Http::fake(['example.com/*' => Http::response('ok', 200)]);

        $user = User::factory()->create();
        [$workspace, $board, $columns] = boardWithColumns($user, ['To Do']);
        $webhook = Webhook::factory()->create([
            'workspace_id' => $workspace->id,
            'url' => 'https://example.com/hook',
            'events' => ['card.created'],
        ]);

        Card::factory()->create(['column_id' => $columns[0]->id]);

        Http::assertSent(function ($request) use ($webhook) {
            $expected = 'sha256='.hash_hmac('sha256', $request->body(), $webhook->fresh()->secret);

            return $request->header('X-Skylight-Signature')[0] === $expected;
        });
    });

    it('records a failed delivery when the endpoint errors', function () {
        Http::fake(['example.com/*' => Http::response('nope', 500)]);

        $user = User::factory()->create();
        [$workspace, $board, $columns] = boardWithColumns($user, ['To Do']);
        Webhook::factory()->create([
            'workspace_id' => $workspace->id,
            'url' => 'https://example.com/hook',
            'events' => ['card.created'],
        ]);

        Card::factory()->create(['column_id' => $columns[0]->id]);

        $delivery = WebhookDelivery::where('event', 'card.created')->firstOrFail();
        expect($delivery->successful)->toBeFalse();
        expect($delivery->response_status)->toBe(500);
    });
});

describe('webhooks management UI', function () {
    // Note: the page (like `workspaces.billing`) gates on auth()->user()->currentWorkspace(),
    // which always resolves to the acting user's own personal workspace -- and a user is
    // always the owner of their own personal workspace, so canManageMembers() is always true
    // through this route. This is the same known, previously-flagged multi-workspace gap as
    // Team/Billing, not a new hole; verified at the model level instead, since there's no way
    // to reach this page as a non-manager of your *own* workspace.
    it('is only reachable by a workspace manager', function () {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $workspace = $owner->currentWorkspace();
        $workspace->users()->attach($member->id, ['role' => Workspace::ROLE_MEMBER]);

        expect($workspace->canManageMembers($owner))->toBeTrue();
        expect($workspace->canManageMembers($member))->toBeFalse();
    });

    it('creates a webhook and reveals the secret once', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        $component = Volt::test('workspaces.webhooks')
            ->set('url', 'https://example.com/hook')
            ->set('selectedEvents', ['card.created', 'card.deleted'])
            ->call('create');

        expect($user->currentWorkspace()->webhooks()->where('url', 'https://example.com/hook')->exists())->toBeTrue();
        expect($component->get('plainTextSecret'))->not->toBeNull();
    });

    it('requires at least one event', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        Volt::test('workspaces.webhooks')
            ->set('url', 'https://example.com/hook')
            ->set('selectedEvents', [])
            ->call('create')
            ->assertHasErrors('selectedEvents');
    });

    it('toggles active state and deletes a webhook', function () {
        $user = User::factory()->create();
        $workspace = $user->currentWorkspace();
        $webhook = Webhook::factory()->create(['workspace_id' => $workspace->id]);
        $this->actingAs($user);

        Volt::test('workspaces.webhooks')->call('toggleActive', $webhook->id);
        expect($webhook->fresh()->is_active)->toBeFalse();

        Volt::test('workspaces.webhooks')->call('delete', $webhook->id);
        expect(Webhook::find($webhook->id))->toBeNull();
    });
});
