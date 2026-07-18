<?php

use App\Models\Board;
use App\Models\Card;
use App\Models\Column;
use App\Models\User;
use App\Models\Webhook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

function boardWithColumnsForSlack(User $user, array $columnNames): array
{
    $workspace = $user->currentWorkspace();
    $board = Board::factory()->create(['user_id' => $user->id, 'workspace_id' => $workspace->id]);

    $columns = collect($columnNames)->map(
        fn ($name, $i) => Column::factory()->create(['board_id' => $board->id, 'name' => $name, 'position' => $i])
    );

    return [$workspace, $board, $columns];
}

describe('slack notifications', function () {
    it('posts a message when a subscribed event fires', function () {
        Http::fake(['hooks.slack.com/*' => Http::response('ok', 200)]);

        $user = User::factory()->create();
        [$workspace, $board, $columns] = boardWithColumnsForSlack($user, ['To Do', 'Done']);
        $workspace->update([
            'slack_webhook_url' => 'https://hooks.slack.com/services/T00/B00/xxx',
            'slack_events' => ['card.created'],
        ]);

        Card::factory()->create(['column_id' => $columns[0]->id, 'title' => 'New card']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://hooks.slack.com/services/T00/B00/xxx'
                && str_contains($request['text'], 'New card');
        });
    });

    it('stays silent when slack is not connected', function () {
        Http::fake(['hooks.slack.com/*' => Http::response('ok', 200)]);

        $user = User::factory()->create();
        [$workspace, $board, $columns] = boardWithColumnsForSlack($user, ['To Do']);

        Card::factory()->create(['column_id' => $columns[0]->id]);

        Http::assertNothingSent();
    });

    it('stays silent for an event not in slack_events', function () {
        Http::fake(['hooks.slack.com/*' => Http::response('ok', 200)]);

        $user = User::factory()->create();
        [$workspace, $board, $columns] = boardWithColumnsForSlack($user, ['To Do']);
        $workspace->update([
            'slack_webhook_url' => 'https://hooks.slack.com/services/T00/B00/xxx',
            'slack_events' => ['card.deleted'],
        ]);

        Card::factory()->create(['column_id' => $columns[0]->id]);

        Http::assertNothingSent();
    });

    it('mentions the source column when a card moves', function () {
        Http::fake(['hooks.slack.com/*' => Http::response('ok', 200)]);

        $user = User::factory()->create();
        [$workspace, $board, $columns] = boardWithColumnsForSlack($user, ['To Do', 'Doing', 'Done']);
        $workspace->update([
            'slack_webhook_url' => 'https://hooks.slack.com/services/T00/B00/xxx',
            'slack_events' => ['card.moved'],
        ]);

        $card = Card::factory()->create(['column_id' => $columns[0]->id]);
        $card->update(['column_id' => $columns[1]->id]);

        Http::assertSent(fn ($request) => str_contains($request['text'], 'To Do') && str_contains($request['text'], 'Doing'));
    });

    it('fires card.completed when a card reaches the last column', function () {
        Http::fake(['hooks.slack.com/*' => Http::response('ok', 200)]);

        $user = User::factory()->create();
        [$workspace, $board, $columns] = boardWithColumnsForSlack($user, ['To Do', 'Done']);
        $workspace->update([
            'slack_webhook_url' => 'https://hooks.slack.com/services/T00/B00/xxx',
            'slack_events' => ['card.moved', 'card.completed'],
        ]);

        $card = Card::factory()->create(['column_id' => $columns[0]->id]);
        $card->update(['column_id' => $columns[1]->id]);

        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => str_contains($request['text'], 'completed'));
    });
});

describe('integrations UI', function () {
    it('connects slack with a valid webhook url and event selection', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        Volt::test('workspaces.integrations')
            ->set('slackWebhookUrl', 'https://hooks.slack.com/services/T00/B00/xxx')
            ->set('slackEvents', ['card.created', 'card.completed'])
            ->call('connectSlack');

        $workspace = $user->currentWorkspace()->fresh();
        expect($workspace->slack_webhook_url)->toBe('https://hooks.slack.com/services/T00/B00/xxx');
        expect($workspace->slack_events)->toBe(['card.created', 'card.completed']);
    });

    it('rejects a non-slack webhook url', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        Volt::test('workspaces.integrations')
            ->set('slackWebhookUrl', 'https://evil.example.com/steal')
            ->set('slackEvents', ['card.created'])
            ->call('connectSlack')
            ->assertHasErrors('slackWebhookUrl');
    });

    it('requires at least one event', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        Volt::test('workspaces.integrations')
            ->set('slackWebhookUrl', 'https://hooks.slack.com/services/T00/B00/xxx')
            ->set('slackEvents', [])
            ->call('connectSlack')
            ->assertHasErrors('slackEvents');
    });

    it('sends a test message and reports success', function () {
        Http::fake(['hooks.slack.com/*' => Http::response('ok', 200)]);

        $user = User::factory()->create();
        $user->currentWorkspace()->update([
            'slack_webhook_url' => 'https://hooks.slack.com/services/T00/B00/xxx',
            'slack_events' => array_keys(Webhook::EVENTS),
        ]);
        $this->actingAs($user);

        $component = Volt::test('workspaces.integrations')->call('sendTestMessage');

        expect($component->get('testResult'))->toContain('Test message sent');
        Http::assertSent(fn ($request) => str_contains($request['text'], 'test message'));
    });

    it('reports failure when slack responds with an error', function () {
        Http::fake(['hooks.slack.com/*' => Http::response('invalid_payload', 400)]);

        $user = User::factory()->create();
        $user->currentWorkspace()->update([
            'slack_webhook_url' => 'https://hooks.slack.com/services/T00/B00/xxx',
            'slack_events' => array_keys(Webhook::EVENTS),
        ]);
        $this->actingAs($user);

        $component = Volt::test('workspaces.integrations')->call('sendTestMessage');

        expect($component->get('testResult'))->toContain('error');
    });

    it('disconnects slack', function () {
        $user = User::factory()->create();
        $user->currentWorkspace()->update([
            'slack_webhook_url' => 'https://hooks.slack.com/services/T00/B00/xxx',
            'slack_events' => array_keys(Webhook::EVENTS),
        ]);
        $this->actingAs($user);

        Volt::test('workspaces.integrations')->call('disconnectSlack');

        expect($user->currentWorkspace()->fresh()->slack_webhook_url)->toBeNull();
    });
});
