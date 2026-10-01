<?php

use App\Features\M0Foundations;
use App\Jobs\ExportAccountData;
use App\Models\Attachment;
use App\Models\Board;
use App\Models\Card;
use App\Models\Checklist;
use App\Models\ChecklistItem;
use App\Models\Column;
use App\Models\Comment;
use App\Models\Label;
use App\Models\MarkdownNote;
use App\Models\User;
use App\Models\Workspace;
use App\Notifications\Account\AccountExportReadyNotification;
use App\Services\AccountExporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Pennant\Feature;

uses(RefreshDatabase::class);

/**
 * A user who owns one board with one fully-populated card, is a member of a
 * stranger's workspace where they left a comment and a note, plus a stranger
 * whose private data must never appear in the export.
 *
 * @return array{user: User, stranger: User}
 */
function seedExportScenario(): array
{
    $user = User::factory()->create(['name' => 'Ada Owner', 'email' => 'ada@example.com']);
    $stranger = User::factory()->create(['name' => 'Stranger Danger']);

    $board = Board::factory()->create(['user_id' => $user->id, 'name' => 'Launch plan', 'description' => 'Q4 launch']);
    $label = Label::factory()->create(['board_id' => $board->id, 'name' => 'Urgent', 'color' => '#ef4444']);
    $column = Column::factory()->create(['board_id' => $board->id, 'name' => 'Doing', 'position' => 0]);
    $card = Card::factory()->create([
        'column_id' => $column->id,
        'title' => 'Write press release',
        'description' => 'Draft **v1**',
        'color' => 'yellow',
        'position' => 0,
        'starts_at' => '2026-10-01 09:00:00',
        'ends_at' => '2026-10-05 17:00:00',
    ]);
    $card->labels()->attach($label);
    $checklist = Checklist::factory()->create(['card_id' => $card->id, 'name' => 'Steps']);
    ChecklistItem::factory()->create(['checklist_id' => $checklist->id, 'body' => 'Outline', 'is_completed' => true, 'position' => 0]);
    ChecklistItem::factory()->create(['checklist_id' => $checklist->id, 'body' => 'Review', 'is_completed' => false, 'position' => 1]);
    Comment::factory()->create(['card_id' => $card->id, 'user_id' => $user->id, 'body' => 'Looks good']);
    MarkdownNote::factory()->create(['card_id' => $card->id, 'user_id' => $user->id, 'name' => 'Brief', 'content' => '# Brief']);
    Attachment::factory()->create([
        'attachable_type' => Card::class,
        'attachable_id' => $card->id,
        'user_id' => $user->id,
        'type' => Attachment::TYPE_LINK,
        'path' => 'https://example.com/spec',
        'name' => 'Spec',
        'mime_type' => null,
        'size' => null,
    ]);

    // The user is a member of the stranger's workspace and contributed there.
    $strangerWorkspace = $stranger->workspaces()->firstOrFail();
    $strangerWorkspace->users()->attach($user->id, ['role' => Workspace::ROLE_MEMBER]);
    $strangerBoard = Board::factory()->create(['user_id' => $stranger->id, 'name' => 'Stranger secret board']);
    $strangerCard = Card::factory()->create([
        'column_id' => Column::factory()->create(['board_id' => $strangerBoard->id])->id,
        'title' => 'Stranger secret card',
    ]);
    Comment::factory()->create(['card_id' => $strangerCard->id, 'user_id' => $user->id, 'body' => 'My comment elsewhere']);
    MarkdownNote::factory()->create(['card_id' => $strangerCard->id, 'user_id' => $user->id, 'name' => 'My note', 'content' => 'Note elsewhere']);
    Comment::factory()->create(['card_id' => $strangerCard->id, 'user_id' => $stranger->id, 'body' => 'Stranger private comment']);

    return ['user' => $user, 'stranger' => $stranger];
}

it('exports everything the user owns in the expected shape', function () {
    ['user' => $user] = seedExportScenario();

    $export = AccountExporter::toArray($user);

    expect($export['account'])->toMatchArray([
        'name' => 'Ada Owner',
        'email' => 'ada@example.com',
        'two_factor_enabled' => false,
        'notification_preferences' => ['due_today' => true, 'overdue' => true],
    ]);
    expect($export['workspace_memberships'])->toHaveCount(2);
    expect($export['owned_workspaces'])->toHaveCount(1);

    $board = $export['owned_workspaces'][0]['boards'][0];
    expect($board)->toMatchArray([
        'name' => 'Launch plan',
        'description' => 'Q4 launch',
        'labels' => [['name' => 'Urgent', 'color' => '#ef4444']],
    ]);

    $card = $board['columns'][0]['cards'][0];
    expect($card)->toMatchArray([
        'title' => 'Write press release',
        'description' => 'Draft **v1**',
        'color' => 'yellow',
        'labels' => ['Urgent'],
    ]);
    expect($card['checklists'][0]['items'])->toBe([
        ['body' => 'Outline', 'is_completed' => true],
        ['body' => 'Review', 'is_completed' => false],
    ]);
    expect($card['comments'][0])->toMatchArray(['author' => 'Ada Owner', 'body' => 'Looks good']);
    expect($card['notes'][0])->toMatchArray(['author' => 'Ada Owner', 'name' => 'Brief', 'content' => '# Brief']);
    expect($card['attachments'][0])->toMatchArray(['type' => 'link', 'name' => 'Spec', 'url' => 'https://example.com/spec']);

    expect($export['contributions_elsewhere']['comments'])->toHaveCount(1);
    expect($export['contributions_elsewhere']['comments'][0]['body'])->toBe('My comment elsewhere');
    expect($export['contributions_elsewhere']['notes'][0])->toMatchArray(['name' => 'My note', 'content' => 'Note elsewhere']);
});

it("never includes anyone else's data", function () {
    ['user' => $user] = seedExportScenario();

    $json = json_encode(AccountExporter::toArray($user));
    $csv = json_encode(AccountExporter::cardRows($user));

    foreach ([$json, $csv] as $output) {
        expect($output)
            ->not->toContain('Stranger secret board')
            ->not->toContain('Stranger secret card')
            ->not->toContain('Stranger private comment');
    }
});

it('builds one CSV row per owned card', function () {
    ['user' => $user] = seedExportScenario();

    expect(AccountExporter::cardRows($user))->toBe([[
        'workspace' => $user->workspaces()->firstOrFail()->name,
        'board' => 'Launch plan',
        'column' => 'Doing',
        'title' => 'Write press release',
        'description' => 'Draft **v1**',
        'color' => 'yellow',
        'starts_at' => '2026-10-01',
        'ends_at' => '2026-10-05',
        'labels' => 'Urgent',
        'checklist_progress' => '1/2',
        'comments_count' => '1',
    ]]);
});

it('downloads a small export immediately as a zip with JSON and CSV', function () {
    ['user' => $user] = seedExportScenario();
    Feature::for($user)->activate(M0Foundations::class);

    $response = $this->actingAs($user)->post(route('account.export'))->assertOk();

    expect($response->headers->get('Content-Disposition'))->toContain('skylight-export-');

    $zip = new ZipArchive;
    $zip->open($response->baseResponse->getFile()->getPathname());
    expect($zip->getFromName('skylight-export.json'))->toContain('Write press release');
    expect($zip->getFromName('cards.csv'))->toStartWith('workspace,board,column,title');
    $zip->close();
});

it('is unavailable when the flag is off', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('account.export'))->assertNotFound();
});

it('queues large exports and emails a signed link that only the owner can use', function () {
    config(['exports.queue_threshold_cards' => 0]);
    Storage::fake('local');
    Queue::fake();
    Notification::fake();
    ['user' => $user, 'stranger' => $stranger] = seedExportScenario();
    Feature::for($user)->activate(M0Foundations::class);

    $this->actingAs($user)->post(route('account.export'))
        ->assertRedirect()
        ->assertSessionHas('status', 'account-export-queued');

    Queue::assertPushed(ExportAccountData::class, fn (ExportAccountData $job) => $job->user->is($user));

    (new ExportAccountData($user))->handle();

    $url = null;
    Notification::assertSentTo($user, AccountExportReadyNotification::class, function (AccountExportReadyNotification $notification) use (&$url) {
        $url = $notification->downloadUrl();

        return true;
    });

    expect(Storage::disk('local')->allFiles("exports/{$user->id}"))->toHaveCount(1);

    $this->actingAs($user)->get($url)->assertOk()->assertDownload('skylight-export.zip');
    $this->actingAs($stranger)->get($url)->assertNotFound();
    $this->actingAs($user)->get(strtok($url, '?'))->assertForbidden();

    $this->travel(config('exports.link_lifetime_hours') + 1)->hours();
    $this->actingAs($user)->get($url)->assertForbidden();
});

it('prunes export archives older than the link lifetime', function () {
    Storage::fake('local');
    Storage::disk('local')->put('exports/1/old.zip', 'old');
    touch(Storage::disk('local')->path('exports/1/old.zip'), now()->subHours(25)->getTimestamp());
    Storage::disk('local')->put('exports/1/new.zip', 'new');

    $this->artisan('app:prune-account-exports')->assertSuccessful();

    expect(Storage::disk('local')->exists('exports/1/old.zip'))->toBeFalse();
    expect(Storage::disk('local')->exists('exports/1/new.zip'))->toBeTrue();
});
