<x-layouts.public :title="'Changelog & Roadmap — '.config('app.name', 'Skylight')">
    <div class="max-w-2xl mx-auto space-y-10">
        <div>
            <h1 class="text-2xl font-semibold">Changelog &amp; Roadmap</h1>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">What's shipped, and what's honestly still on the list.</p>
        </div>

        <section>
            <h2 class="text-lg font-semibold mb-4">Changelog</h2>
            <div class="space-y-8">
                <div>
                    <p class="text-xs font-medium text-gray-400 dark:text-gray-500 mb-2">JULY 2026</p>
                    <ul class="space-y-2.5 text-sm text-gray-700 dark:text-gray-300 list-disc list-inside">
                        <li>Public REST API with token authentication, so you can read and write boards, columns, cards, and comments from your own scripts.</li>
                        <li>Outgoing webhooks for card created/moved/completed/deleted events, plus a Slack integration to post updates straight to a channel.</li>
                        <li>Zapier and Make.com support via the webhooks and API above, and CSV/JSON export for any board.</li>
                        <li>File attachments can now live on S3-compatible object storage instead of a single server's disk.</li>
                        <li>Nightly backups with a tested, documented restore process — not just a backup job that's never been verified.</li>
                        <li>A public <a href="{{ route('status') }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">status page</a> and a <a href="{{ route('security') }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">security page</a> explaining exactly what's encrypted, hashed, and rate-limited.</li>
                        <li>Privacy Policy and Terms of Service pages.</li>
                        <li>Production hardening: error tracking, registration rate-limiting, and HTTPS/session security for real deployments.</li>
                        <li>This marketing site — landing page and pricing page — replacing what used to be a bare login screen.</li>
                    </ul>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-400 dark:text-gray-500 mb-2">EARLIER</p>
                    <ul class="space-y-2.5 text-sm text-gray-700 dark:text-gray-300 list-disc list-inside">
                        <li>Client Portals: branded, read-only share links with expiry, revocation, and view analytics — the fastest way to show a client a board without giving them an account.</li>
                        <li>Stripe billing with Free/Pro/Team plans and a 14-day trial on paid plans.</li>
                        <li>Team workspaces with Owner/Admin/Member/Viewer roles, email invites, @mentions, and a per-board activity feed.</li>
                        <li>Board templates, a guided onboarding checklist, and a sample board created automatically on signup.</li>
                        <li>Fast drag-and-drop, keyboard shortcuts, global search, filters, and a full mobile-responsive pass.</li>
                        <li>Checklists, comments, image/link attachments, and markdown notes on every card.</li>
                        <li>Two-factor authentication, email verification, and self-serve password reset.</li>
                    </ul>
                </div>
            </div>
        </section>

        <section>
            <h2 class="text-lg font-semibold mb-4">Roadmap</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                Real, currently-known gaps — not a wishlist of features we haven't thought through. Roughly in the order we'd tackle them:
            </p>
            <ul class="space-y-2.5 text-sm text-gray-700 dark:text-gray-300 list-disc list-inside">
                <li>A proper workspace switcher, so someone who's a member of more than one workspace can actually navigate between them (today, pages like Team and Billing only ever show your own workspace).</li>
                <li>Self-serve account deletion (currently handled by emailing us).</li>
                <li>Custom branding (logo, colors) on Client Portal links per workspace.</li>
                <li>Broader REST API coverage — checklists, attachments, and labels as first-class API resources, not just nested inside a card.</li>
                <li>Per-plan storage limits, to go alongside the existing board and member limits.</li>
            </ul>
        </section>
    </div>
</x-layouts.public>
