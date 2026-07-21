@php
    $plans = config('plans');
@endphp
<x-layouts.public :title="'Pricing — '.config('app.name', 'Skylight')">
    <div class="max-w-4xl mx-auto">
        <div class="text-center mb-10">
            <h1 class="text-3xl font-semibold">Pricing</h1>
            <p class="mt-3 text-gray-600 dark:text-gray-400">
                Every plan includes every feature — Client Portals, the REST API, webhooks, Slack, 2FA, everything.
                Plans only differ by how many boards and teammates you need.
            </p>
        </div>

        <div class="grid gap-6 md:grid-cols-3">
            @foreach ($plans as $key => $plan)
                <div class="rounded-xl bg-white dark:bg-gray-900 p-6 shadow-sm ring-1 {{ $key === 'pro' ? 'ring-2 ring-indigo-500' : 'ring-gray-200 dark:ring-gray-800' }}">
                    @if ($key === 'pro')
                        <p class="text-xs font-medium text-indigo-600 dark:text-indigo-400 mb-2">MOST POPULAR</p>
                    @endif
                    <h2 class="text-lg font-semibold">{{ $plan['name'] }}</h2>
                    <p class="mt-2 text-4xl font-semibold">
                        ${{ $plan['price_monthly'] }}<span class="text-sm font-normal text-gray-500 dark:text-gray-400">/month</span>
                    </p>
                    <ul class="mt-5 space-y-2.5 text-sm text-gray-600 dark:text-gray-400">
                        <li class="flex items-center gap-2">
                            <span class="text-green-500">&check;</span>
                            {{ $plan['board_limit'] ?? 'Unlimited' }} boards
                        </li>
                        <li class="flex items-center gap-2">
                            <span class="text-green-500">&check;</span>
                            {{ $plan['member_limit'] ?? 'Unlimited' }} team members
                        </li>
                        <li class="flex items-center gap-2">
                            <span class="text-green-500">&check;</span>
                            Client Portals, checklists, comments, attachments
                        </li>
                        <li class="flex items-center gap-2">
                            <span class="text-green-500">&check;</span>
                            REST API, webhooks &amp; Slack integration
                        </li>
                        <li class="flex items-center gap-2">
                            <span class="text-green-500">&check;</span>
                            Two-factor authentication
                        </li>
                    </ul>
                    <a
                        href="{{ auth()->check() ? route('billing') : route('register') }}"
                        class="mt-6 block text-center rounded-lg px-4 py-2.5 text-sm font-medium transition-colors {{ $key === 'pro' ? 'bg-indigo-600 hover:bg-indigo-700 text-white' : 'border border-gray-300 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800' }}"
                    >
                        {{ $key === 'free' ? 'Start free' : 'Get started' }}
                    </a>
                </div>
            @endforeach
        </div>

        <div class="mt-14 max-w-2xl mx-auto space-y-6">
            <h2 class="text-xl font-semibold text-center">Questions</h2>

            <div>
                <h3 class="font-medium">What happens if I go over my plan's limit?</h3>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">You'll see a message when creating a board or inviting a teammate that would put you over the limit, with a link to upgrade. Nothing you've already created is ever locked or deleted.</p>
            </div>
            <div>
                <h3 class="font-medium">Can I cancel or change plans anytime?</h3>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Yes — upgrade, downgrade, or cancel anytime from your workspace's Billing page. Changes are handled through Stripe's billing portal.</p>
            </div>
            <div>
                <h3 class="font-medium">Is there a free trial on paid plans?</h3>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Yes — your first subscription to a paid plan includes a 14-day trial before you're charged.</p>
            </div>
            <div>
                <h3 class="font-medium">Do you offer refunds?</h3>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">See our <a href="{{ route('terms') }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">Terms of Service</a> for our billing policy.</p>
            </div>
        </div>
    </div>
</x-layouts.public>
