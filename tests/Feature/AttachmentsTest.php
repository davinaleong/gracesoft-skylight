<?php

use App\Models\Attachment;
use App\Models\Board;
use App\Models\Card;
use App\Models\Column;
use App\Models\MarkdownNote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

function cardOnBoard(User $user): Card
{
    $board = Board::factory()->create(['user_id' => $user->id]);
    $column = Column::factory()->create(['board_id' => $board->id]);

    return Card::factory()->create(['column_id' => $column->id]);
}

describe('image attachments', function () {
    it('uploads an image', function () {
        Storage::fake('local');

        $user = User::factory()->create();
        $card = cardOnBoard($user);
        $this->actingAs($user);

        $file = UploadedFile::fake()->image('screenshot.png', 100, 100);

        Volt::test('cards.detail', ['card' => $card])
            ->set('imageUpload', $file)
            ->call('uploadImage')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('attachments', [
            'card_id' => $card->id,
            'type' => Attachment::TYPE_IMAGE,
            'name' => 'screenshot.png',
        ]);
    });

    it('rejects non-image uploads', function () {
        Storage::fake('local');

        $user = User::factory()->create();
        $card = cardOnBoard($user);
        $this->actingAs($user);

        $file = UploadedFile::fake()->create('malware.exe', 100, 'application/octet-stream');

        Volt::test('cards.detail', ['card' => $card])
            ->set('imageUpload', $file)
            ->call('uploadImage')
            ->assertHasErrors(['imageUpload']);
    });

    it('deletes own image attachment', function () {
        Storage::fake('local');
        Storage::disk('local')->put('attachments/test.jpg', 'fake content');

        $user = User::factory()->create();
        $card = cardOnBoard($user);
        $attachment = Attachment::factory()->image()->create([
            'card_id' => $card->id,
            'user_id' => $user->id,
            'path' => 'attachments/test.jpg',
        ]);
        $this->actingAs($user);

        Volt::test('cards.detail', ['card' => $card])
            ->call('deleteAttachment', $attachment->id)
            ->assertHasNoErrors();

        $this->assertModelMissing($attachment);
        Storage::disk('local')->assertMissing('attachments/test.jpg');
    });
});

describe('link attachments', function () {
    it('adds a link', function () {
        $user = User::factory()->create();
        $card = cardOnBoard($user);
        $this->actingAs($user);

        Volt::test('cards.detail', ['card' => $card])
            ->set('linkUrl', 'https://laravel.com')
            ->set('linkName', 'Laravel Docs')
            ->call('addLink')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('attachments', [
            'card_id' => $card->id,
            'type' => Attachment::TYPE_LINK,
            'path' => 'https://laravel.com',
            'name' => 'Laravel Docs',
        ]);
    });

    it('requires a valid URL', function () {
        $user = User::factory()->create();
        $card = cardOnBoard($user);
        $this->actingAs($user);

        Volt::test('cards.detail', ['card' => $card])
            ->set('linkUrl', 'not-a-url')
            ->call('addLink')
            ->assertHasErrors(['linkUrl']);
    });
});

describe('markdown notes', function () {
    it('creates a markdown note', function () {
        $user = User::factory()->create();
        $card = cardOnBoard($user);
        $this->actingAs($user);

        Volt::test('cards.detail', ['card' => $card])
            ->set('noteName', 'Meeting notes')
            ->set('noteContent', '## Key points\n- Point 1\n- Point 2')
            ->call('saveNote')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('markdown_notes', [
            'card_id' => $card->id,
            'name' => 'Meeting notes',
        ]);
    });

    it('updates an existing note', function () {
        $user = User::factory()->create();
        $card = cardOnBoard($user);
        $note = MarkdownNote::factory()->create(['card_id' => $card->id, 'user_id' => $user->id]);
        $this->actingAs($user);

        Volt::test('cards.detail', ['card' => $card])
            ->set('editingNoteId', $note->id)
            ->set('noteName', 'Updated title')
            ->set('noteContent', 'Updated content')
            ->call('saveNote')
            ->assertHasNoErrors();

        expect($note->fresh()->name)->toBe('Updated title');
    });

    it('deletes a note', function () {
        $user = User::factory()->create();
        $card = cardOnBoard($user);
        $note = MarkdownNote::factory()->create(['card_id' => $card->id, 'user_id' => $user->id]);
        $this->actingAs($user);

        Volt::test('cards.detail', ['card' => $card])
            ->call('deleteNote', $note->id)
            ->assertHasNoErrors();

        $this->assertModelMissing($note);
    });
});
