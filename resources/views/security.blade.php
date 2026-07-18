<x-layouts.public :title="'Security — '.config('app.name', 'Skylight')">
    <div class="max-w-2xl mx-auto space-y-8">
        <div>
            <h1 class="text-2xl font-semibold">Security</h1>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                A plain-language account of how {{ config('app.name', 'Skylight') }} protects your data. Every claim
                below describes something actually implemented in the product, not an aspiration.
            </p>
        </div>

        <div class="space-y-6">
            <section class="rounded-xl bg-white dark:bg-gray-900 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-gray-800">
                <h2 class="text-base font-semibold mb-2">Account security</h2>
                <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400 list-disc list-inside">
                    <li>Optional two-factor authentication (TOTP, any authenticator app) with a QR-code setup flow and one-time recovery codes for account recovery.</li>
                    <li>Passwords are hashed with bcrypt and are never logged, emailed, or stored in a reversible form.</li>
                    <li>Login attempts are rate-limited, and you're notified by email of password changes, logins from a new IP address, and recovery-code use.</li>
                </ul>
            </section>

            <section class="rounded-xl bg-white dark:bg-gray-900 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-gray-800">
                <h2 class="text-base font-semibold mb-2">Encryption</h2>
                <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400 list-disc list-inside">
                    <li><strong>In transit:</strong> production traffic is served over HTTPS/TLS — set up as part of standard deployment (see our <a href="{{ route('status') }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">status page</a> for current health).</li>
                    <li><strong>At rest:</strong> secrets that the app must be able to use again later — Slack webhook URLs, outgoing webhook signing secrets — are stored with authenticated encryption (AES-256), not plain text.</li>
                    <li><strong>Never stored at all:</strong> API tokens, Client Portal share links, and team invite links are stored only as a one-way SHA-256 hash of the token — the original value exists only once, at creation time, and can't be recovered from the database even by us.</li>
                    <li>Visitor IP addresses in activity logs and Client Portal view analytics are hashed (SHA-256), never stored raw.</li>
                </ul>
            </section>

            <section class="rounded-xl bg-white dark:bg-gray-900 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-gray-800">
                <h2 class="text-base font-semibold mb-2">Access control</h2>
                <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400 list-disc list-inside">
                    <li>Every workspace, board, column, card, and comment is scoped to a workspace, with server-side checks on every read and write — not just hidden buttons in the UI.</li>
                    <li>Role-based permissions (Owner / Admin / Member / Viewer) govern who can invite teammates, edit content, or only view it.</li>
                    <li>Client Portal links are read-only, individually revocable, support an expiry date, and can toggle whether comments or attachments are visible.</li>
                </ul>
            </section>

            <section class="rounded-xl bg-white dark:bg-gray-900 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-gray-800">
                <h2 class="text-base font-semibold mb-2">Infrastructure</h2>
                <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400 list-disc list-inside">
                    <li>File attachments can be stored on S3-compatible object storage rather than a single server's disk, with private buckets and short-lived signed URLs for image delivery.</li>
                    <li>Nightly backups (database + uploaded files + configuration) are written to a dedicated off-site destination, separate from primary storage, with optional archive encryption. Our restore process is tested, not just assumed to work.</li>
                    <li>Sensitive endpoints (login, two-factor challenges, the public API, Client Portal links) are all rate-limited.</li>
                </ul>
            </section>
        </div>

        @php
            $securityEmail = 'security@'.(parse_url(config('app.url'), PHP_URL_HOST) ?? 'example.com');
        @endphp
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Found a security issue? Please report it responsibly rather than filing a public issue —
            <a href="mailto:{{ $securityEmail }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">{{ $securityEmail }}</a>.
        </p>
    </div>
</x-layouts.public>
