<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

describe('avatar upload', function () {
    it('shows a fallback initial when no avatar is set', function () {
        $user = User::factory()->create(['name' => 'Ada Lovelace']);

        $this->actingAs($user)
            ->get(route('profile'))
            ->assertOk()
            ->assertSee('A');
    });

    it('uploads an avatar image', function () {
        Storage::fake('local');

        $user = User::factory()->create();
        $this->actingAs($user);

        $file = UploadedFile::fake()->image('me.png', 200, 200);

        Volt::test('profile.avatar')
            ->set('avatarUpload', $file)
            ->call('upload')
            ->assertHasNoErrors();

        $user->refresh();
        expect($user->avatar_path)->not->toBeNull();
        Storage::disk('local')->assertExists($user->avatar_path);
    });

    it('rejects a non-image upload', function () {
        Storage::fake('local');

        $user = User::factory()->create();
        $this->actingAs($user);

        $file = UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf');

        Volt::test('profile.avatar')
            ->set('avatarUpload', $file)
            ->call('upload')
            ->assertHasErrors(['avatarUpload']);

        expect($user->fresh()->avatar_path)->toBeNull();
    });

    it('rejects an oversized upload', function () {
        Storage::fake('local');

        $user = User::factory()->create();
        $this->actingAs($user);

        $file = UploadedFile::fake()->image('huge.png')->size(6000); // 6 MB > 5 MB limit

        Volt::test('profile.avatar')
            ->set('avatarUpload', $file)
            ->call('upload')
            ->assertHasErrors(['avatarUpload']);
    });

    it('deletes the old file when replacing an avatar', function () {
        Storage::fake('local');
        Storage::disk('local')->put('avatars/old.jpg', 'old content');

        $user = User::factory()->create(['avatar_path' => 'avatars/old.jpg']);
        $this->actingAs($user);

        $file = UploadedFile::fake()->image('new.png', 200, 200);

        Volt::test('profile.avatar')
            ->set('avatarUpload', $file)
            ->call('upload')
            ->assertHasNoErrors();

        Storage::disk('local')->assertMissing('avatars/old.jpg');
        expect($user->fresh()->avatar_path)->not->toBe('avatars/old.jpg');
    });

    it('removes an avatar', function () {
        Storage::fake('local');
        Storage::disk('local')->put('avatars/mine.jpg', 'content');

        $user = User::factory()->create(['avatar_path' => 'avatars/mine.jpg']);
        $this->actingAs($user);

        Volt::test('profile.avatar')
            ->call('remove')
            ->assertHasNoErrors();

        Storage::disk('local')->assertMissing('avatars/mine.jpg');
        expect($user->fresh()->avatar_path)->toBeNull();
    });
});
