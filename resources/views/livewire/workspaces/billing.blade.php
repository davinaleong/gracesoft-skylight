<?php

use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Laravel\Cashier\Checkout;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

new class extends Component {
    public Workspace $workspace;

    public function mount(): void
    {
        $this->workspace = auth()->user()->currentWorkspace();

        abort_unless($this->workspace->canManageMembers(auth()->user()), 403);
    }

    #[Computed]
    public function plans(): array
    {
        return config('plans');
    }

    public function subscribe(string $plan): Checkout|RedirectResponse
    {
        abort_unless($this->workspace->isOwner(auth()->user()), 403, 'Only the workspace owner can manage billing.');
        abort_unless(array_key_exists($plan, config('plans')), 404);

        $priceId = config("plans.{$plan}.stripe_price_id");

        abort_if(! $priceId, 422, 'This plan is not yet available for checkout — Stripe price id not configured.');

        // First-ever paid subscription gets a 14-day trial; re-subscribing after
        // cancelling does not.
        $eligibleForTrial = ! $this->workspace->subscription('default');

        $subscription = $this->workspace->newSubscription('default', $priceId);

        if ($eligibleForTrial) {
            $subscription->trialDays(14);
        }

        return $subscription->checkout([
            'success_url' => route('billing').'?checkout=success',
            'cancel_url' => route('billing').'?checkout=cancelled',
        ]);
    }

    public function manageBilling(): RedirectResponse
    {
        abort_unless($this->workspace->isOwner(auth()->user()), 403, 'Only the workspace owner can manage billing.');
        abort_unless($this->workspace->hasStripeId(), 404);

        return $this->workspace->redirectToBillingPortal(route('billing'));
    }
};
?>

<div class="max-w-3xl space-y-6">
    <h1 class="text-2xl font-semibold">Billing</h1>

    @if (request('checkout') === 'success')
        <div class="rounded-lg bg-green-50 dark:bg-green-900/20 p-3 text-sm text-green-700 dark:text-green-400">
            Subscription updated. Thanks for upgrading!
        </div>
    @elseif (request('checkout') === 'cancelled')
        <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-3 text-sm text-gray-600 dark:text-gray-400">
            Checkout was cancelled — you're still on your current plan.
        </div>
    @endif

    <div class="rounded-xl bg-white dark:bg-gray-900 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-gray-800">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-base font-semibold">Current plan: {{ $this->workspace->planLimits()['name'] }}</h3>
                @if ($this->workspace->onTrial())
                    <p class="mt-1 text-sm text-indigo-600 dark:text-indigo-400">
                        Trial ends {{ $this->workspace->trial_ends_at->format('d M Y') }}
                    </p>
                @elseif ($this->workspace->subscribed())
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Active subscription</p>
                @endif
            </div>
            @if ($this->workspace->hasStripeId() && $this->workspace->isOwner(auth()->user()))
                <button
                    wire:click="manageBilling"
                    class="rounded-lg border border-gray-300 dark:border-gray-700 px-3.5 py-2 text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
                >
                    Manage billing
                </button>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        @foreach ($this->plans as $key => $plan)
            @php $isCurrent = $workspace->plan === $key; @endphp
            <div @class([
                'rounded-xl p-5 ring-1',
                'bg-white dark:bg-gray-900 ring-gray-200 dark:ring-gray-800' => ! $isCurrent,
                'bg-indigo-50 dark:bg-indigo-900/20 ring-indigo-300 dark:ring-indigo-700' => $isCurrent,
            ])>
                <h3 class="text-base font-semibold">{{ $plan['name'] }}</h3>
                <p class="mt-1 text-2xl font-bold">
                    ${{ $plan['price_monthly'] }}<span class="text-sm font-normal text-gray-500">/mo</span>
                </p>
                <ul class="mt-4 space-y-1.5 text-sm text-gray-600 dark:text-gray-400">
                    <li>{{ $plan['board_limit'] ?? 'Unlimited' }} boards</li>
                    <li>{{ $plan['member_limit'] ?? 'Unlimited' }} members</li>
                </ul>

                @if ($isCurrent)
                    <span class="mt-4 inline-flex items-center rounded-full bg-indigo-100 dark:bg-indigo-900/40 px-2.5 py-1 text-xs font-medium text-indigo-700 dark:text-indigo-300">
                        Current plan
                    </span>
                @elseif ($key === 'free')
                    {{-- No downgrade-to-free button here: use "Manage billing" (Stripe portal) to cancel a paid plan. --}}
                @else
                    <button
                        wire:click="subscribe('{{ $key }}')"
                        wire:loading.attr="disabled"
                        wire:target="subscribe('{{ $key }}')"
                        class="mt-4 w-full rounded-lg bg-indigo-600 hover:bg-indigo-700 px-3 py-2 text-sm font-medium text-white shadow-xs transition-colors disabled:opacity-60"
                    >
                        <span wire:loading.remove wire:target="subscribe('{{ $key }}')">Upgrade to {{ $plan['name'] }}</span>
                        <span wire:loading wire:target="subscribe('{{ $key }}')">Redirecting…</span>
                    </button>
                @endif
            </div>
        @endforeach
    </div>
</div>
