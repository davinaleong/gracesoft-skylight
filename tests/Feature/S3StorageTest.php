<?php

use App\Models\Attachment;
use App\Models\Board;
use App\Models\Card;
use App\Models\Column;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

/**
 * These mirror the existing local-disk upload tests (ProfileAvatarTest,
 * AttachmentsTest) but force `filesystems.default` to `s3` for the duration
 * of each test, proving the upload/delete/URL code paths are genuinely
 * disk-agnostic (they all resolve the disk via `config('filesystems.default')`
 * rather than hardcoding `local`) and not just "should work in theory."
 */
describe('S3-compatible storage', function () {
    it('uploads an avatar to the s3 disk', function () {
        config(['filesystems.default' => 's3']);
        Storage::fake('s3');

        $user = User::factory()->create();
        $this->actingAs($user);

        $file = UploadedFile::fake()->image('me.png', 200, 200);

        Volt::test('profile.avatar')
            ->set('avatarUpload', $file)
            ->call('upload')
            ->assertHasNoErrors();

        $user->refresh();
        expect($user->avatar_path)->not->toBeNull();
        Storage::disk('s3')->assertExists($user->avatar_path);
        Storage::disk('local')->assertMissing($user->avatar_path);
    });

    it('deletes the old avatar from the s3 disk when replacing it', function () {
        config(['filesystems.default' => 's3']);
        Storage::fake('s3');
        Storage::disk('s3')->put('avatars/old.jpg', 'old content');

        $user = User::factory()->create(['avatar_path' => 'avatars/old.jpg']);
        $this->actingAs($user);

        Volt::test('profile.avatar')
            ->set('avatarUpload', UploadedFile::fake()->image('new.png', 200, 200))
            ->call('upload')
            ->assertHasNoErrors();

        Storage::disk('s3')->assertMissing('avatars/old.jpg');
    });

    it('generates a URL for an avatar stored on s3', function () {
        config(['filesystems.default' => 's3']);
        Storage::fake('s3');
        Storage::disk('s3')->put('avatars/mine.jpg', 'content');

        $user = User::factory()->create(['avatar_path' => 'avatars/mine.jpg']);

        expect($user->avatarUrl())->toBeString()->not->toBeEmpty();
    });

    it('uploads a card image attachment to the s3 disk', function () {
        config(['filesystems.default' => 's3']);
        Storage::fake('s3');

        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $column = Column::factory()->create(['board_id' => $board->id]);
        $card = Card::factory()->create(['column_id' => $column->id]);
        $this->actingAs($user);

        $file = UploadedFile::fake()->image('screenshot.png', 400, 300);

        Volt::test('cards.detail', ['card' => $card])
            ->set('fileUpload', $file)
            ->call('uploadFile')
            ->assertHasNoErrors();

        $attachment = $card->attachments()->firstOrFail();
        Storage::disk('s3')->assertExists($attachment->path);
        expect($attachment->temporaryUrl())->toBeString()->not->toBeEmpty();
    });

    it('deletes a card image attachment from the s3 disk', function () {
        config(['filesystems.default' => 's3']);
        Storage::fake('s3');

        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $column = Column::factory()->create(['board_id' => $board->id]);
        $card = Card::factory()->create(['column_id' => $column->id]);
        Storage::disk('s3')->put('attachments/photo.png', 'content');
        $attachment = Attachment::factory()->create([
            'attachable_type' => Card::class,
            'attachable_id' => $card->id,
            'user_id' => $user->id,
            'type' => Attachment::TYPE_IMAGE,
            'path' => 'attachments/photo.png',
        ]);
        $this->actingAs($user);

        Volt::test('cards.detail', ['card' => $card])
            ->call('deleteAttachment', $attachment->id);

        Storage::disk('s3')->assertMissing('attachments/photo.png');
        expect(Attachment::find($attachment->id))->toBeNull();
    });
});
