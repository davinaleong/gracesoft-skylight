<?php

use App\Models\Workspace;
use App\Models\WorkspaceInvite;
use App\Notifications\Workspace\WorkspaceInvitationNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

new class extends Component {
    public string $email = '';
    public string $role = Workspace::ROLE_MEMBER;

    #[Computed]
    public function workspace(): Workspace
    {
        $workspace = auth()->user()->currentWorkspace();

        abort_if(! $workspace, 404);

        return $workspace;
    }

    #[Computed]
    public function canManage(): bool
    {
        return $this->workspace->canManageMembers(auth()->user());
    }

    #[Computed]
    public function members()
    {
        return $this->workspace->users;
    }

    #[Computed]
    public function pendingInvites()
    {
        if (! $this->canManage) {
            return collect();
        }

        return $this->workspace->invites()->whereNull('accepted_at')->latest()->get();
    }

    public function invite(): void
    {
        abort_unless($this->canManage, 403);

        $this->validate([
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', Rule::in(WorkspaceInvite::INVITABLE_ROLES)],
        ]);

        if ($this->workspace->users()->where('email', $this->email)->exists()) {
            $this->addError('email', 'This person is already a member.');

            return;
        }

        ['token' => $token, 'hash' => $hash] = WorkspaceInvite::generateToken();

        $invite = WorkspaceInvite::updateOrCreate(
            ['workspace_id' => $this->workspace->id, 'email' => $this->email],
            [
                'role' => $this->role,
                'token_hash' => $hash,
                'invited_by' => auth()->id(),
                'accepted_at' => null,
                'expires_at' => now()->addDays(7),
            ]
        );

        Notification::route('mail', $invite->email)->notify(new WorkspaceInvitationNotification($invite, $token));

        $this->reset('email');
        unset($this->pendingInvites);
        session()->flash('status', 'Invitation sent to '.$invite->email.'.');
    }
};
?>

<div class="max-w-2xl space-y-6">
    <h1 class="text-2xl font-semibold">Team</h1>

    @if (session('status'))
        <div class="rounded-lg bg-green-50 dark:bg-green-900/20 p-3 text-sm text-green-700 dark:text-green-400">
            {{ session('status') }}
        </div>
    @endif

    <div class="rounded-xl bg-white dark:bg-gray-900 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-gray-800">
        <h3 class="mb-4 text-base font-semibold">{{ $this->workspace->name }}</h3>

        <ul class="divide-y divide-gray-100 dark:divide-gray-800">
            @foreach ($this->members as $member)
                <li class="flex items-center justify-between py-3">
                    <div>
                        <p class="text-sm font-medium">{{ $member->name }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $member->email }}</p>
                    </div>
                    <span class="rounded-full bg-gray-100 dark:bg-gray-800 px-2.5 py-1 text-xs font-medium capitalize text-gray-600 dark:text-gray-300">
                        {{ $member->pivot->role }}
                    </span>
                </li>
            @endforeach
        </ul>
    </div>

    @if ($this->canManage)
        <div class="rounded-xl bg-white dark:bg-gray-900 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-gray-800">
            <h3 class="mb-4 text-base font-semibold">Invite a teammate</h3>

            <form wire:submit="invite" class="flex flex-wrap items-start gap-3">
                <div class="flex-1 min-w-[200px]">
                    <input
                        type="email"
                        wire:model="email"
                        placeholder="teammate@example.com"
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2.5 text-sm shadow-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('email') border-red-500 @enderror"
                    >
                    @error('email')
                        <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
                <select wire:model="role" class="rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2.5 text-sm shadow-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @foreach (\App\Models\WorkspaceInvite::INVITABLE_ROLES as $roleOption)
                        <option value="{{ $roleOption }}">{{ ucfirst($roleOption) }}</option>
                    @endforeach
                </select>
                <button type="submit" class="rounded-lg bg-indigo-600 hover:bg-indigo-700 px-4 py-2.5 text-sm font-medium text-white shadow-xs transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    Send invite
                </button>
            </form>
        </div>

        @if ($this->pendingInvites->isNotEmpty())
            <div class="rounded-xl bg-white dark:bg-gray-900 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-gray-800">
                <h3 class="mb-4 text-base font-semibold">Pending invitations</h3>
                <ul class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach ($this->pendingInvites as $invite)
                        <li class="flex items-center justify-between py-3">
                            <div>
                                <p class="text-sm font-medium">{{ $invite->email }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    Invited as {{ $invite->role }} &middot; {{ $invite->created_at->diffForHumans() }}
                                </p>
                            </div>
                            <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $invite->isExpired() ? 'bg-red-50 text-red-600 dark:bg-red-900/20 dark:text-red-400' : 'bg-amber-50 text-amber-600 dark:bg-amber-900/20 dark:text-amber-400' }}">
                                {{ $invite->isExpired() ? 'Expired' : 'Pending' }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    @endif
</div>
