<?php

use App\Models\Attachment;
use App\Models\Board;
use App\Models\Card;
use App\Models\Checklist;
use App\Models\Column;
use App\Models\Comment;
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

describe('file attachments', function () {
    it('uploads an image', function () {
        Storage::fake('local');

        $user = User::factory()->create();
        $card = cardOnBoard($user);
        $this->actingAs($user);

        $file = UploadedFile::fake()->image('screenshot.png', 100, 100);

        Volt::test('cards.detail', ['card' => $card])
            ->set('fileUpload', $file)
            ->call('uploadFile')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('attachments', [
            'attachable_type' => Card::class,
            'attachable_id' => $card->id,
            'type' => Attachment::TYPE_IMAGE,
            'name' => 'screenshot.png',
        ]);
    });

    it('uploads a PDF as a document attachment', function () {
        Storage::fake('local');

        $user = User::factory()->create();
        $card = cardOnBoard($user);
        $this->actingAs($user);

        $file = UploadedFile::fake()->create('spec.pdf', 100, 'application/pdf');

        Volt::test('cards.detail', ['card' => $card])
            ->set('fileUpload', $file)
            ->call('uploadFile')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('attachments', [
            'attachable_type' => Card::class,
            'attachable_id' => $card->id,
            'type' => Attachment::TYPE_DOCUMENT,
            'name' => 'spec.pdf',
        ]);
    });

    it('rejects file formats other than images and PDFs', function () {
        Storage::fake('local');

        $user = User::factory()->create();
        $card = cardOnBoard($user);
        $this->actingAs($user);

        $file = UploadedFile::fake()->create('report.docx', 100, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

        Volt::test('cards.detail', ['card' => $card])
            ->set('fileUpload', $file)
            ->call('uploadFile')
            ->assertHasErrors(['fileUpload']);
    });

    it('deletes own image attachment', function () {
        Storage::fake('local');
        Storage::disk('local')->put('attachments/test.jpg', 'fake content');

        $user = User::factory()->create();
        $card = cardOnBoard($user);
        $attachment = Attachment::factory()->image()->create([
            'attachable_type' => Card::class,
            'attachable_id' => $card->id,
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
            'attachable_type' => Card::class,
            'attachable_id' => $card->id,
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

describe('item-level attachments', function () {
    it('uploads an image to a checklist', function () {
        Storage::fake('local');

        $user = User::factory()->create();
        $card = cardOnBoard($user);
        $checklist = Checklist::factory()->create(['card_id' => $card->id]);
        $this->actingAs($user);

        $file = UploadedFile::fake()->image('checklist-shot.png', 100, 100);

        Volt::test('cards.detail', ['card' => $card])
            ->call('openAttachmentForm', 'checklist', $checklist->id)
            ->set('itemFileUpload', $file)
            ->call('uploadItemFile')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('attachments', [
            'attachable_type' => Checklist::class,
            'attachable_id' => $checklist->id,
            'type' => Attachment::TYPE_IMAGE,
            'name' => 'checklist-shot.png',
        ]);
    });

    it('uploads a PDF to a comment', function () {
        Storage::fake('local');

        $user = User::factory()->create();
        $card = cardOnBoard($user);
        $comment = Comment::factory()->create(['card_id' => $card->id, 'user_id' => $user->id]);
        $this->actingAs($user);

        $file = UploadedFile::fake()->create('agenda.pdf', 100, 'application/pdf');

        Volt::test('cards.detail', ['card' => $card])
            ->call('openAttachmentForm', 'comment', $comment->id)
            ->set('itemFileUpload', $file)
            ->call('uploadItemFile')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('attachments', [
            'attachable_type' => Comment::class,
            'attachable_id' => $comment->id,
            'type' => Attachment::TYPE_DOCUMENT,
            'name' => 'agenda.pdf',
        ]);
    });

    it('adds a link to a comment', function () {
        $user = User::factory()->create();
        $card = cardOnBoard($user);
        $comment = Comment::factory()->create(['card_id' => $card->id, 'user_id' => $user->id]);
        $this->actingAs($user);

        Volt::test('cards.detail', ['card' => $card])
            ->call('openAttachmentForm', 'comment', $comment->id)
            ->set('itemLinkUrl', 'https://laravel.com')
            ->set('itemLinkName', 'Laravel Docs')
            ->call('addItemLink')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('attachments', [
            'attachable_type' => Comment::class,
            'attachable_id' => $comment->id,
            'type' => Attachment::TYPE_LINK,
            'path' => 'https://laravel.com',
            'name' => 'Laravel Docs',
        ]);
    });

    it('adds a link to a note', function () {
        $user = User::factory()->create();
        $card = cardOnBoard($user);
        $note = MarkdownNote::factory()->create(['card_id' => $card->id, 'user_id' => $user->id]);
        $this->actingAs($user);

        Volt::test('cards.detail', ['card' => $card])
            ->call('openAttachmentForm', 'note', $note->id)
            ->set('itemLinkUrl', 'https://laravel.com')
            ->call('addItemLink')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('attachments', [
            'attachable_type' => MarkdownNote::class,
            'attachable_id' => $note->id,
            'type' => Attachment::TYPE_LINK,
            'path' => 'https://laravel.com',
        ]);
    });

    it('allows multiple attachments on the same checklist', function () {
        $user = User::factory()->create();
        $card = cardOnBoard($user);
        $checklist = Checklist::factory()->create(['card_id' => $card->id]);
        $this->actingAs($user);

        $component = Volt::test('cards.detail', ['card' => $card]);

        $component->call('openAttachmentForm', 'checklist', $checklist->id)
            ->set('itemLinkUrl', 'https://laravel.com')
            ->call('addItemLink')
            ->assertHasNoErrors();

        $component->call('openAttachmentForm', 'checklist', $checklist->id)
            ->set('itemLinkUrl', 'https://example.com')
            ->call('addItemLink')
            ->assertHasNoErrors();

        expect($checklist->attachments()->count())->toBe(2);
    });

    it('deletes an item-level image attachment and its file', function () {
        Storage::fake('local');
        Storage::disk('local')->put('attachments/item.jpg', 'fake content');

        $user = User::factory()->create();
        $card = cardOnBoard($user);
        $note = MarkdownNote::factory()->create(['card_id' => $card->id, 'user_id' => $user->id]);
        $attachment = Attachment::factory()->image()->create([
            'attachable_type' => MarkdownNote::class,
            'attachable_id' => $note->id,
            'user_id' => $user->id,
            'path' => 'attachments/item.jpg',
        ]);
        $this->actingAs($user);

        Volt::test('cards.detail', ['card' => $card])
            ->call('deleteItemAttachment', $attachment->id)
            ->assertHasNoErrors();

        $this->assertModelMissing($attachment);
        Storage::disk('local')->assertMissing('attachments/item.jpg');
    });

    it('prevents attaching to an entity that belongs to another card', function () {
        $user = User::factory()->create();
        $card = cardOnBoard($user);
        $otherCard = cardOnBoard($user);
        $otherChecklist = Checklist::factory()->create(['card_id' => $otherCard->id]);
        $this->actingAs($user);

        expect(fn () => Volt::test('cards.detail', ['card' => $card])
            ->call('openAttachmentForm', 'checklist', $otherChecklist->id)
        )->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
    });
});
