@php
    $plans = config('plans');
@endphp
<x-layouts.public :title="config('app.name', 'Skylight').' — Kanban boards your clients can actually see'">
    {{-- Hero --}}
    <div class="text-center max-w-3xl mx-auto py-8 sm:py-16">
        <h1 class="text-3xl sm:text-5xl font-semibold tracking-tight">
            Kanban boards your team runs on — and your clients can actually see.
        </h1>
        <p class="mt-5 text-lg text-gray-600 dark:text-gray-400">
            {{ config('app.name', 'Skylight') }} is a fast, drag-and-drop project board with real team permissions,
            two-factor security, and a read-only <strong>Client Portal</strong> link you can hand to anyone —
            no account, no seat, no awkward guest invite.
        </p>
        <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
            <a href="{{ route('register') }}" class="rounded-lg bg-indigo-600 hover:bg-indigo-700 px-5 py-3 text-sm font-medium text-white shadow-xs transition-colors">
                Start free — no card required
            </a>
            <a href="{{ route('pricing') }}" class="rounded-lg border border-gray-300 dark:border-gray-700 px-5 py-3 text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                See pricing
            </a>
        </div>
        <p class="mt-4 text-xs text-gray-400 dark:text-gray-500">Free plan: 3 boards, 3 teammates, forever.</p>
    </div>

    {{-- Feature grid --}}
    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 py-10">
        <div class="rounded-xl bg-white dark:bg-gray-900 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-gray-800">
            <h3 class="font-semibold mb-1.5">Boards that feel instant</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">Client-side drag-and-drop, keyboard shortcuts, saved filters, and global search — built for people who move fast, not for people who like spinners.</p>
        </div>
        <div class="rounded-xl bg-white dark:bg-gray-900 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-gray-800">
            <h3 class="font-semibold mb-1.5">Client Portals</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">Share a read-only, branded link to any board — toggle comments and attachments on or off, set an expiry, and revoke it instantly. No client ever needs an account.</p>
        </div>
        <div class="rounded-xl bg-white dark:bg-gray-900 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-gray-800">
            <h3 class="font-semibold mb-1.5">Real team permissions</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">Owner, Admin, Member, and Viewer roles enforced on the server, not just hidden in the UI. Invite by email, manage roles, and see a full activity feed per board.</p>
        </div>
        <div class="rounded-xl bg-white dark:bg-gray-900 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-gray-800">
            <h3 class="font-semibold mb-1.5">Checklists, comments &amp; files</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">Checklists with progress tracking, @mentions in comments, image attachments, links, and markdown notes — everything lives on the card, not scattered across tools.</p>
        </div>
        <div class="rounded-xl bg-white dark:bg-gray-900 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-gray-800">
            <h3 class="font-semibold mb-1.5">Built to connect</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">A token-authenticated REST API, outgoing webhooks, and a Slack integration for card events — plus CSV/JSON export whenever you want your data out.</p>
        </div>
        <div class="rounded-xl bg-white dark:bg-gray-900 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-gray-800">
            <h3 class="font-semibold mb-1.5">Secure by default</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">Optional two-factor authentication, hashed/encrypted secrets, and nightly backups with a tested restore process. See our <a href="{{ route('security') }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">security page</a> for the details.</p>
        </div>
    </div>

    {{-- Pricing teaser --}}
    <div class="pt-16 pb-10 border-t border-gray-200 dark:border-gray-800">
        <h2 class="text-center text-2xl font-semibold mb-8">Simple pricing, no seat games</h2>
        <div class="grid gap-6 md:grid-cols-3 max-w-4xl mx-auto">
            @foreach ($plans as $key => $plan)
                <div class="rounded-xl bg-white dark:bg-gray-900 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-gray-800 text-center">
                    <h3 class="font-semibold">{{ $plan['name'] }}</h3>
                    <p class="mt-2 text-3xl font-semibold">
                        ${{ $plan['price_monthly'] }}<span class="text-sm font-normal text-gray-500 dark:text-gray-400">/mo</span>
                    </p>
                    <p class="mt-3 text-sm text-gray-600 dark:text-gray-400">
                        {{ $plan['board_limit'] ?? 'Unlimited' }} boards &middot; {{ $plan['member_limit'] ?? 'Unlimited' }} members
                    </p>
                </div>
            @endforeach
        </div>
        <p class="text-center mt-6">
            <a href="{{ route('pricing') }}" class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:underline">Full pricing details &rarr;</a>
        </p>
    </div>

    {{-- Final CTA --}}
    <div class="text-center py-10 border-t border-gray-200 dark:border-gray-800">
        <h2 class="text-2xl font-semibold">Ready to see it on your own boards?</h2>
        <p class="mt-2 text-gray-600 dark:text-gray-400">Set up your first board in under a minute.</p>
        <a href="{{ route('register') }}" class="mt-6 inline-block rounded-lg bg-indigo-600 hover:bg-indigo-700 px-5 py-3 text-sm font-medium text-white shadow-xs transition-colors">
            Create your free account
        </a>
    </div>
</x-layouts.public>
