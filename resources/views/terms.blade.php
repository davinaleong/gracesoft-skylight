@php
    $legalEmail = 'legal@'.(parse_url(config('app.url'), PHP_URL_HOST) ?? 'example.com');
@endphp
<x-layouts.public :title="'Terms of Service — '.config('app.name', 'Skylight')">
    <div class="max-w-2xl mx-auto space-y-6">
        <div>
            <h1 class="text-2xl font-semibold">Terms of Service</h1>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Last updated {{ \Illuminate\Support\Carbon::parse('2026-07-18')->toFormattedDateString() }}</p>
        </div>

        <div class="rounded-xl bg-amber-50 dark:bg-amber-900/20 ring-1 ring-amber-200 dark:ring-amber-900/40 p-4 text-sm text-amber-800 dark:text-amber-300">
            This is a template — have a lawyer review and adapt it to your jurisdiction and business before relying on it. It describes the product as it actually works today, not aspirational terms.
        </div>

        <section class="space-y-2">
            <h2 class="text-base font-semibold">1. Your account</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                You're responsible for the security of your account, including enabling two-factor authentication if you choose to. You must be old enough to enter a binding contract in your jurisdiction to use {{ config('app.name', 'Skylight') }}.
            </p>
        </section>

        <section class="space-y-2">
            <h2 class="text-base font-semibold">2. Your content</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                You own everything you create — boards, cards, comments, and files you upload. We don't claim any ownership over it, and we don't use it for anything beyond providing the service to you. You're responsible for having the rights to anything you upload.
            </p>
        </section>

        <section class="space-y-2">
            <h2 class="text-base font-semibold">3. Client Portal &amp; sharing</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Anyone with a Client Portal link can view what it's configured to show, without an account. You're responsible for who you share links with and for revoking them when they're no longer needed.
            </p>
        </section>

        <section class="space-y-2">
            <h2 class="text-base font-semibold">4. Subscriptions &amp; billing</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Paid plans are billed in advance on a recurring basis through Stripe. You can cancel, upgrade, or downgrade at any time from your workspace's Billing page — changes are handled by Stripe's billing portal. We don't offer refunds for partial billing periods except where required by law.
            </p>
        </section>

        <section class="space-y-2">
            <h2 class="text-base font-semibold">5. Acceptable use</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Don't use {{ config('app.name', 'Skylight') }} to store or share unlawful content, attempt to breach other workspaces' data, or abuse the public API or webhooks beyond their documented rate limits.
            </p>
        </section>

        <section class="space-y-2">
            <h2 class="text-base font-semibold">6. Service availability</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                We aim to keep the service available and back it up regularly, but we don't guarantee uninterrupted uptime. See our <a href="{{ route('status') }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">status page</a> for current health.
            </p>
        </section>

        <section class="space-y-2">
            <h2 class="text-base font-semibold">7. Termination</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                You can stop using {{ config('app.name', 'Skylight') }} and request account deletion at any time. We may suspend accounts that violate these terms, with notice where practical.
            </p>
        </section>

        <section class="space-y-2">
            <h2 class="text-base font-semibold">8. Changes</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                We'll update the "last updated" date above if these terms change, and notify active account holders of material changes by email.
            </p>
        </section>

        <section class="space-y-2">
            <h2 class="text-base font-semibold">Questions</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Contact <a href="mailto:{{ $legalEmail }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">{{ $legalEmail }}</a> with any questions about these terms.
            </p>
        </section>
    </div>
</x-layouts.public>
