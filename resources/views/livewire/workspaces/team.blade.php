<?php

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvite;
use App\Notifications\Workspace\WorkspaceInvitationNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

new class extends Component {
    public Workspace $workspace;

    public string $email = '';
    public string $role = Workspace::ROLE_MEMBER;

    public function mount(Workspace $workspace): void
    {
        abort_unless($workspace->hasMember(auth()->user()), 403);

        $this->workspace = $workspace;
    }

    #[Computed]
    public function otherWorkspaces()
    {
        return auth()->user()->workspaces->reject(fn ($w) => $w->id === $this->workspace->id);
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

    public function changeRole(int $userId, string $newRole): void
    {
        $target = User::findOrFail($userId);

        abort_unless($this->workspace->canChangeMember(auth()->user(), $target), 403);
        abort_unless(in_array($newRole, WorkspaceInvite::INVITABLE_ROLES, true), 422);

        $this->workspace->users()->updateExistingPivot($userId, ['role' => $newRole]);

        unset($this->members);
        session()->flash('status', $target->name.'\'s role was updated to '.$newRole.'.');
    }

    public function removeMember(int $userId): void
    {
        $target = User::findOrFail($userId);

        abort_unless($this->workspace->canChangeMember(auth()->user(), $target), 403);

        $this->workspace->users()->detach($userId);

        unset($this->members);
        session()->flash('status', $target->name.' was removed from the workspace.');
    }

    public function resendInvite(int $inviteId): void
    {
        abort_unless($this->canManage, 403);

        $invite = $this->workspace->invites()->findOrFail($inviteId);

        ['token' => $token, 'hash' => $hash] = WorkspaceInvite::generateToken();

        $invite->update(['token_hash' => $hash, 'expires_at' => now()->addDays(7)]);

        Notification::route('mail', $invite->email)->notify(new WorkspaceInvitationNotification($invite, $token));

        unset($this->pendingInvites);
        session()->flash('status', 'Invitation resent to '.$invite->email.'.');
    }

    public function cancelInvite(int $inviteId): void
    {
        abort_unless($this->canManage, 403);

        $invite = $this->workspace->invites()->findOrFail($inviteId);
        $invite->delete();

        unset($this->pendingInvites);
        session()->flash('status', 'Invitation to '.$invite->email.' cancelled.');
    }
};
?>

<div class="max-w-2xl space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold">Team</h1>

        @if ($this->otherWorkspaces->isNotEmpty())
            <div class="flex items-center gap-2 text-sm">
                <span class="text-gray-500 dark:text-gray-400">Switch workspace:</span>
                @foreach ($this->otherWorkspaces as $other)
                    <a href="{{ route('team.show', $other) }}" class="rounded-lg border border-gray-300 dark:border-gray-700 px-2.5 py-1 text-xs font-medium hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                        {{ $other->name }}
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    @if (session('status'))
        <div class="rounded-lg bg-green-50 dark:bg-green-900/20 p-3 text-sm text-green-700 dark:text-green-400">
            {{ session('status') }}
        </div>
    @endif

    <div class="rounded-xl bg-white dark:bg-gray-900 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-gray-800">
        <h3 class="mb-4 text-base font-semibold">{{ $this->workspace->name }}</h3>

        <ul class="divide-y divide-gray-100 dark:divide-gray-800">
            @foreach ($this->members as $member)
                <li class="flex items-center justify-between gap-3 py-3">
                    <div class="min-w-0">
                        <p class="text-sm font-medium truncate">{{ $member->name }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $member->email }}</p>
                    </div>

                    @if ($this->canManage && $this->workspace->canChangeMember(auth()->user(), $member))
                        <div class="flex shrink-0 items-center gap-2">
                            <select
                                wire:change="changeRole({{ $member->id }}, $event.target.value)"
                                class="rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-2.5 py-1.5 text-xs font-medium capitalize focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            >
                                @foreach (\App\Models\WorkspaceInvite::INVITABLE_ROLES as $roleOption)
                                    <option value="{{ $roleOption }}" @selected($member->pivot->role === $roleOption)>{{ ucfirst($roleOption) }}</option>
                                @endforeach
                            </select>
                            <button
                                wire:click="removeMember({{ $member->id }})"
                                wire:confirm="Remove {{ $member->name }} from this workspace?"
                                class="rounded-lg border border-gray-300 dark:border-gray-700 px-2.5 py-1.5 text-xs font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
                            >
                                Remove
                            </button>
                        </div>
                    @else
                        <span class="shrink-0 rounded-full bg-gray-100 dark:bg-gray-800 px-2.5 py-1 text-xs font-medium capitalize text-gray-600 dark:text-gray-300">
                            {{ $member->pivot->role }}
                        </span>
                    @endif
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
                        <li class="flex items-center justify-between gap-3 py-3">
                            <div class="min-w-0">
                                <p class="text-sm font-medium truncate">{{ $invite->email }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    Invited as {{ $invite->role }} &middot; {{ $invite->created_at->diffForHumans() }}
                                </p>
                            </div>
                            <div class="flex shrink-0 items-center gap-2">
                                <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $invite->isExpired() ? 'bg-red-50 text-red-600 dark:bg-red-900/20 dark:text-red-400' : 'bg-amber-50 text-amber-600 dark:bg-amber-900/20 dark:text-amber-400' }}">
                                    {{ $invite->isExpired() ? 'Expired' : 'Pending' }}
                                </span>
                                <button
                                    wire:click="resendInvite({{ $invite->id }})"
                                    class="rounded-lg border border-gray-300 dark:border-gray-700 px-2.5 py-1.5 text-xs font-medium hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
                                >
                                    Resend
                                </button>
                                <button
                                    wire:click="cancelInvite({{ $invite->id }})"
                                    wire:confirm="Cancel this invitation?"
                                    class="rounded-lg border border-gray-300 dark:border-gray-700 px-2.5 py-1.5 text-xs font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
                                >
                                    Cancel
                                </button>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    @endif
</div>
