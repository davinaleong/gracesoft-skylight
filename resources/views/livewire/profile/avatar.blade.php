<?php

use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public $avatarUpload = null;

    #[Computed]
    public function avatarUrl(): ?string
    {
        return auth()->user()->avatarUrl();
    }

    public function upload(): void
    {
        $this->validate([
            'avatarUpload' => ['required', 'image', 'max:5120'], // 5 MB
        ]);

        $user = auth()->user();
        $oldPath = $user->avatar_path;

        $path = $this->avatarUpload->store('avatars', config('filesystems.default'));

        $user->update(['avatar_path' => $path]);

        if ($oldPath) {
            Storage::disk(config('filesystems.default'))->delete($oldPath);
        }

        $this->reset('avatarUpload');
        unset($this->avatarUrl);
    }

    public function remove(): void
    {
        $user = auth()->user();

        if ($user->avatar_path) {
            Storage::disk(config('filesystems.default'))->delete($user->avatar_path);
            $user->update(['avatar_path' => null]);
        }

        unset($this->avatarUrl);
    }
};
?>

<div class="rounded-xl bg-white dark:bg-gray-900 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-gray-800">
    <h3 class="mb-4 text-base font-semibold">Avatar</h3>

    <div class="flex items-center gap-4">
        @if ($this->avatarUrl)
            <img src="{{ $this->avatarUrl }}" alt="{{ auth()->user()->name }}" class="h-16 w-16 rounded-full object-cover ring-1 ring-gray-200 dark:ring-gray-700">
        @else
            <div class="flex h-16 w-16 items-center justify-center rounded-full bg-indigo-100 dark:bg-indigo-900/40 text-lg font-semibold text-indigo-600 dark:text-indigo-300">
                {{ Str::of(auth()->user()->name)->substr(0, 1)->upper() }}
            </div>
        @endif

        <div class="flex-1">
            <form wire:submit="upload" class="flex flex-wrap items-center gap-3">
                <input
                    type="file"
                    wire:model="avatarUpload"
                    accept="image/*"
                    class="block w-full max-w-xs text-sm text-gray-600 dark:text-gray-400 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 dark:file:bg-indigo-900/30 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-indigo-600 dark:file:text-indigo-300 hover:file:bg-indigo-100 dark:hover:file:bg-indigo-900/50"
                >
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    class="rounded-lg bg-indigo-600 hover:bg-indigo-700 px-3.5 py-2 text-sm font-medium text-white shadow-xs transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:opacity-50"
                >
                    Upload
                </button>
                @if ($this->avatarUrl)
                    <button
                        type="button"
                        wire:click="remove"
                        wire:confirm="Remove your avatar?"
                        class="rounded-lg border border-gray-300 dark:border-gray-700 px-3.5 py-2 text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
                    >
                        Remove
                    </button>
                @endif
            </form>
            @error('avatarUpload')
                <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>
    </div>
</div>
