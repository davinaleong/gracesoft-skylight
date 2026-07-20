@php
    $privacyEmail = 'privacy@'.(parse_url(config('app.url'), PHP_URL_HOST) ?? 'example.com');
@endphp
<x-layouts.public :title="'Privacy Policy — '.config('app.name', 'Skylight')">
    <div class="max-w-2xl mx-auto space-y-6">
        <div>
            <h1 class="text-2xl font-semibold">Privacy Policy</h1>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Last updated {{ \Illuminate\Support\Carbon::parse('2026-07-18')->toFormattedDateString() }}</p>
        </div>

        <div class="rounded-xl bg-amber-50 dark:bg-amber-900/20 ring-1 ring-amber-200 dark:ring-amber-900/40 p-4 text-sm text-amber-800 dark:text-amber-300">
            This is a template policy describing what {{ config('app.name', 'Skylight') }} actually collects and does today. It is not legal advice — have a lawyer review and adapt it (especially the jurisdiction, data-retention periods, and any regional requirements like GDPR/CCPA) before relying on it for a real business.
        </div>

        <section class="space-y-2">
            <h2 class="text-base font-semibold">What we collect</h2>
            <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-600 dark:text-gray-400">
                <li><strong>Account information:</strong> name, email address, and password (hashed, never stored in plain text) — or, if you sign up via Google/GitHub, the name and email your provider shares with us.</li>
                <li><strong>Content you create:</strong> boards, cards, comments, checklists, and any files you upload as attachments or as your profile avatar.</li>
                <li><strong>Usage and activity data:</strong> an activity log of actions taken on your boards (who created/moved/edited what, and when), used to power the in-app activity feed.</li>
                <li><strong>Technical data:</strong> IP addresses are hashed (SHA-256) before storage for login-security checks and Client Portal view counts — we do not retain your raw IP address.</li>
                <li><strong>Billing information:</strong> if you subscribe to a paid plan, payment details are collected and processed directly by Stripe — we never see or store your card number.</li>
            </ul>
        </section>

        <section class="space-y-2">
            <h2 class="text-base font-semibold">How we use it</h2>
            <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-600 dark:text-gray-400">
                <li>To provide the service: rendering your boards, sending notifications you've opted into, and enforcing workspace permissions.</li>
                <li>To keep your account secure: detecting logins from a new location and notifying you of security-relevant changes.</li>
                <li>To operate the business: processing payments (via Stripe) and responding to support requests.</li>
                <li>We do not sell your data, and we do not use your board content to train any model.</li>
            </ul>
        </section>

        <section class="space-y-2">
            <h2 class="text-base font-semibold">Who we share it with</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400">We use a small number of service providers to run {{ config('app.name', 'Skylight') }}, each only for the purpose described:</p>
            <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-600 dark:text-gray-400">
                <li><strong>Stripe</strong> — payment processing for paid plans.</li>
                <li><strong>Object storage (S3-compatible)</strong> — hosting uploaded files and avatars.</li>
                <li><strong>Email delivery provider</strong> — sending transactional email (verification, notifications, invites).</li>
                <li><strong>Slack</strong> — only if you connect your own workspace's Slack channel under Integrations; we post the events you choose to your webhook URL and nothing else.</li>
            </ul>
        </section>

        <section class="space-y-2">
            <h2 class="text-base font-semibold">Your choices</h2>
            <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-600 dark:text-gray-400">
                <li><strong>Export your data</strong> any time from a board's "Export" menu (JSON or CSV).</li>
                <li><strong>Revoke access</strong> to Client Portal links, API tokens, or webhooks at any time from their respective settings pages — access stops immediately.</li>
                <li><strong>Delete your account:</strong> self-serve account deletion isn't built yet — email us and we'll delete your account and associated data.</li>
            </ul>
        </section>

        <section class="space-y-2">
            <h2 class="text-base font-semibold">Questions</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Contact <a href="mailto:{{ $privacyEmail }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">{{ $privacyEmail }}</a> with any privacy questions or requests.
            </p>
        </section>
    </div>
</x-layouts.public>
