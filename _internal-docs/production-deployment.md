# Production Deployment Runbook

This is an operational checklist for standing up a production instance of GraceSoft
Skylight, not a description of what the code already does automatically. Everything
here is either a one-time server/process setup step or a real credential this
codebase has been built against as a placeholder — see `sellable-milestone-progress.md`
for the code-level detail behind each item.

## 1. Web server & HTTPS

The app itself doesn't run a web server — put nginx, Caddy, or a PaaS's managed
proxy in front of `php-fpm` (or `php artisan serve` only for local dev, never
production). Terminate TLS at that layer.

Once HTTPS is terminated upstream of the app:

- Set `TRUSTED_PROXIES` in `.env` — `*` if the app is only reachable through a
  platform-managed proxy (most PaaS setups), otherwise a comma-separated list of
  the proxy's IPs/CIDRs. Without this, Laravel sees the proxy's plain HTTP
  connection, not the client's HTTPS one, and generates `http://` URLs.
- Set `SESSION_SECURE_COOKIE=true` — without this the session cookie's `Secure`
  flag is never set, even when every real client connection is HTTPS (blank/unset
  does **not** auto-detect; verified via `config/session.php`).

Both are blank by default in `.env.example` specifically so a local `php artisan serve`
setup isn't broken by them — this is intentionally something you turn on, not
something the app forces on you.

## 2. Queue workers

Webhooks, Slack notifications, and outgoing email are all queued jobs
(`database` driver by default — see `QUEUE_CONNECTION` in `.env`). Nothing in
that queue is processed unless a worker is running continuously; `php artisan
queue:work` run by hand in a terminal will die the moment that terminal closes.

Run it under a process supervisor that restarts it on crash. Example Supervisor
config (`/etc/supervisor/conf.d/skylight-worker.conf`):

```ini
[program:skylight-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/skylight/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/skylight/storage/logs/worker.log
stopwaitsecs=3600
```

- `--tries=3` matches the retry behavior already built into `SendWebhookRequest`/
  `SendSlackMessage` (see Milestone 7).
- `--max-time=3600` recycles each worker process hourly, which is Laravel's own
  recommended mitigation for gradual memory growth in long-running PHP workers —
  Supervisor immediately restarts it (`autorestart=true`), so this doesn't drop jobs.
- After every deploy, run `php artisan queue:restart` so workers pick up the new
  code on their next iteration instead of continuing to run stale code in memory.

The `/status` page's "Background jobs" check (Milestone 8) is a proxy for whether
this is actually running — a worker that's silently died shows up there as
"N job(s) failed" only once a job is *attempted* and fails outright, not as
"nothing has been processed in hours." A queue-depth alert on the `jobs` table is
a reasonable follow-up if this becomes a real operational concern.

## 3. Scheduler

`routes/console.php` defines several scheduled commands (card-due reminders,
nightly backup/cleanup/monitor). None of them run unless the Laravel scheduler
itself is invoked every minute. Add exactly one cron entry:

```cron
* * * * * cd /var/www/skylight && php artisan schedule:run >> /dev/null 2>&1
```

Verify what's actually registered with `php artisan schedule:list` — this is
also exercised by `tests/Feature/BackupConfigTest.php`, so a regression here
would show up as a failing test, not just a silent production gap.

## 4. Error tracking (Sentry)

`sentry/sentry-laravel` is installed and wired into `bootstrap/app.php`
(`Integration::handles($exceptions)`) and no-ops completely with no DSN
configured — safe to leave off in any environment that doesn't need it.

To enable: create a project at sentry.io (or a self-hosted instance), copy its
DSN into `SENTRY_LARAVEL_DSN`. Consider setting `SENTRY_TRACES_SAMPLE_RATE` to
something like `0.2` rather than leaving tracing off entirely — full request
tracing at high traffic volumes gets expensive fast on most Sentry plans.

## 5. Environment variables that need real values before launch

Every one of these is currently a placeholder (blank or a fake value) in this
codebase, verified against the actual `.env`/`.env.example` at each milestone
that introduced it. The app runs and its tests pass with all of them blank —
this list is "what a real launch needs," not "what's broken today."

| Variable(s) | Needed for | Where it's used |
|---|---|---|
| `GOOGLE_CLIENT_ID` / `GOOGLE_CLIENT_SECRET` | Google OAuth signup | Milestone 1 |
| `GITHUB_CLIENT_ID` / `GITHUB_CLIENT_SECRET` | GitHub OAuth signup | Milestone 1 |
| `STRIPE_KEY` / `STRIPE_SECRET` / `STRIPE_WEBHOOK_SECRET` / `STRIPE_PRICE_PRO` / `STRIPE_PRICE_TEAM` | Billing | Milestone 6 |
| `AWS_ACCESS_KEY_ID` / `AWS_SECRET_ACCESS_KEY` / `AWS_BUCKET` (+ `FILESYSTEM_DISK=s3`) | File attachments on object storage instead of local disk | Milestone 8 |
| `AWS_BACKUP_BUCKET` (ideally a *separate* bucket/account from `AWS_BUCKET`) | Off-site backup destination | Milestone 8 |
| `BACKUP_NOTIFICATION_EMAIL` | Backup failure/health alerts | Milestone 8 |
| `BACKUP_ARCHIVE_PASSWORD` (optional) | Encrypting backup archives — recommended since `.env` itself is inside them | Milestone 8 |
| `SENTRY_LARAVEL_DSN` | Error tracking | This item |
| `TRUSTED_PROXIES` / `SESSION_SECURE_COOKIE=true` | Correct HTTPS detection & secure session cookies behind a reverse proxy | This item |
| `MAIL_*` | Transactional email (verification, invites, notifications) — currently pointed at a Mailtrap-style sandbox | — |

## 6. Post-deploy smoke test

1. Load `/status` — confirm all four checks are green against production's real
   database/cache/storage.
2. Hit `/up` from wherever the external uptime monitor will poll it, confirm 200.
3. Register a real test account through `/register`, confirm the verification
   email arrives and the account works end-to-end, then delete it.
4. Run `php artisan backup:run` once by hand and confirm a file lands in the
   `backups` disk, not just `local` — the restore-drill procedure documented in
   `sellable-milestone-progress.md` (Iteration 33) is the reference for verifying
   it can actually be restored, not just that the command exits 0.
