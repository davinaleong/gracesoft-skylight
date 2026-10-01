# GraceSoft Skylight: Test Plan

Last updated: 2026-10-01

Companion to `skylight-milestones.md`. Each milestone is done only when its automated tests pass, the regression suite stays green, and its manual QA checklist is signed off.

## 1. Conventions

- **Framework:** Pest (feature and unit tests), with `Livewire::test()` / `Volt::test()` for components
- **Database:** PostgreSQL in CI (same engine as production), `RefreshDatabase`
- **Fakes:** `Notification::fake()`, `Mail::fake()`, `Queue::fake()`, `Storage::fake()`, `Http::fake()` for Stripe/AI/OAuth calls
- **Time:** `$this->travelTo()` for expiry, reminders, recurring cards, and grace periods
- **Factories:** every model has a factory; add states such as `->sharedWith($user, 'editor')`, `->expired()`, `->revoked()`
- **Naming:** `tests/Feature/M1/BoardMemberRolesTest.php`, so coverage maps to milestones
- **CI gate:** tests, Pint (code style), Larastan level ≥ 6, and `composer audit` must pass before merge

## 2. Regression suite (run on every milestone)

These protect what already works. Write any that don't exist yet as part of M0.

- [ ] Auth: register, log in, log out, remember me, forgot/reset password, password confirmation
- [ ] 2FA: enable, confirm, disable, recovery code login, recovery code regeneration
- [ ] Rate limits: login (5/min per email + IP), 2FA challenge (5/min), public viewer (30/min per IP)
- [ ] Boards are addressed by UUID; numeric IDs never appear in URLs or responses
- [ ] Columns and cards: create, delete, reorder, move between columns; positions stay consistent
- [ ] Card modal: dates (due ≥ start), checklists, comments (≤ 2,000 chars), notes, attachments
- [ ] Attachments: allowed types only, ≤ 10 MB, temporary URLs expire after ~5 minutes, stored file removed on delete; tested on card, checklist, comment, and note (all four partial usages)
- [ ] Share links: token shown once, only the hash stored, revoke works, `X-Robots-Tag: noindex, nofollow` sent, access recorded with hashed IP
- [ ] All nine notification emails send on the right trigger (use `app:test-mail` output as visual reference)
- [ ] Activity log: each listed event is written; IPs are hashed; position-only card changes aren't logged
- [ ] Bot API: valid token returns only the owner's data; bot tokens can't access web pages
- [ ] Search: starts at 2 characters, max 5 boards and 8 cards, only accessible boards

## 3. Cross-cutting security tests

Run these against every new route, Livewire action, and API endpoint.

- [ ] **Authorisation (IDOR):** for every action that takes an ID, a user without access gets `403` / `404`, and nothing changes in the database
- [ ] **Livewire tampering:** calling component methods directly with another board's IDs fails
- [ ] **Mass assignment:** forged fields (`user_id`, `role`, `board_id`) are ignored
- [ ] **XSS:** Markdown and plain-text fields (card descriptions, comments, notes, client feedback, imported Trello content, inbound email) strip scripts, event handlers, and `javascript:` links
- [ ] **Tokens:** every secret token (share links, invites, iCal, magic links, inbound email addresses) is stored hashed, compared in constant time, and revocable
- [ ] **Public pages:** send `noindex`, are rate-limited, and return the same response for "not found", "revoked", and "expired" so they don't leak which links exist
- [ ] **CSRF:** all state-changing web routes require a valid token
- [ ] **Headers:** CSP, `X-Frame-Options` / `frame-ancestors`, `Referrer-Policy`, HSTS present on all pages

## 4. Milestone tests

### M0: Foundations and known gaps

- [ ] Toggling `due_today` / `overdue` off stops that reminder; on sends it (`travelTo` 08:00)
- [ ] Creating a bot token shows it once; the list shows only the name and last-used time
- [ ] A revoked bot token returns `401` on the next request
- [ ] The bot API returns `429` with `Retry-After` after the limit
- [ ] Export JSON matches a fixture for a seeded user, and contains no other users' data
- [ ] Large exports are queued; the download link is signed and expires
- [ ] Account deletion requires password + 2FA; cancelling within 7 days restores the account
- [ ] After the grace period (`travelTo` +7 days), the user's rows and stored files are gone; activity logs are anonymised
- [ ] PWA manifest is served and valid

### M1: Shared boards and roles

**Permission matrix:** write one dataset-driven test per row that checks every role.

| Action                                    | Owner | Editor | Commenter | Viewer | Non-member |
| ----------------------------------------- | ----- | ------ | --------- | ------ | ---------- |
| View board                                | ✓     | ✓      | ✓         | ✓      | ✗          |
| Create/edit/move/delete cards and columns | ✓     | ✓      | ✗         | ✗      | ✗          |
| Manage labels                             | ✓     | ✓      | ✗         | ✗      | ✗          |
| Comment                                   | ✓     | ✓      | ✓         | ✗      | ✗          |
| Delete own comment                        | ✓     | ✓      | ✓         | —      | ✗          |
| Upload attachments                        | ✓     | ✓      | ✗         | ✗      | ✗          |
| Create/revoke share links                 | ✓     | ✗      | ✗         | ✗      | ✗          |
| Invite/remove members, change roles       | ✓     | ✗      | ✗         | ✗      | ✗          |
| Delete board / transfer ownership         | ✓     | ✗      | ✗         | ✗      | ✗          |

- [ ] Invites: token stored hashed, single-use, expired invite rejected, wrong email can't accept
- [ ] Existing user accepts directly; new user registers then joins with the invited role
- [ ] Ownership transfer leaves exactly one owner; the owner can't leave without transferring
- [ ] Removing a member revokes access immediately (open tab gets `403` on the next action)
- [ ] Assignees must be board members; removing a member unassigns them
- [ ] Search and the bot API include member boards and exclude boards the user was removed from
- [ ] Migration backfill: every existing board has its owner as an `owner` member
- [ ] Notifications sent for invited, role changed, removed, and assigned

### M2: Trello import

- [ ] A fixture Trello export imports with the correct columns, cards, order, labels, checklists, comments, dates, and links
- [ ] Archived items are skipped by default and included when the option is set
- [ ] Malformed JSON, the wrong file type, or an oversized file fails cleanly with no partial board left behind
- [ ] Imported Markdown containing a `<script>` payload is sanitised
- [ ] The summary counts match what was created

### M3: Calendar and iCal

- [ ] Cards appear on the correct day in month and week views across timezones (e.g. Asia/Singapore vs America/New_York)
- [ ] Dragging to a new day updates the due date for editors; viewers and commenters can't
- [ ] The `.ics` feed validates (parse it in the test) and contains only accessible cards
- [ ] A revoked feed URL returns the generic not-found response
- [ ] Reminders run at 08:00 in each user's timezone (`travelTo` several timezones)

### M4: Client portal

- [ ] A password-protected link needs the correct password; wrong passwords are rate-limited
- [ ] An expired link (`travelTo` past `expires_at`) shows the generic not-found response
- [ ] Email-gated: only allowed emails receive a magic link; the magic link is single-use and expires
- [ ] Hidden columns and cards are absent from the HTML and from Livewire payloads, not merely hidden
- [ ] Checklist, comment, and note attachments appear only when attachments are permitted
- [ ] Client feedback (comment, approve, changes requested) is saved, sanitised, rate-limited, and notifies the owner and assignees
- [ ] Feedback is rejected on links where feedback isn't allowed
- [ ] Branding (logo, colour, name) renders on share pages and share emails; the logo upload is validated like other images
- [ ] Owner analytics show correct view counts and last viewed; no raw IPs anywhere

### M5: Plans and billing

- [ ] Each gated feature is blocked on the server for plans that don't include it (dataset test per feature × plan)
- [ ] Stripe webhooks (`Http::fake` / Cashier test helpers): subscribe, upgrade, downgrade, cancel, payment failed
- [ ] Webhook signature verification rejects forged requests
- [ ] Downgrading keeps all data readable; gated features become read-only, nothing is deleted
- [ ] Failed payment: grace period, reminder emails, then downgrade (not deletion)

### M6: Security and privacy dashboard

- [ ] The sessions list shows the user's sessions only; "log out" and "log out everywhere" invalidate them immediately
- [ ] Passkeys: register, log in, remove; can't remove the last login method without another in place
- [ ] Audit log shows board events only to members; the CSV export is Studio-only and has no raw IPs
- [ ] SSO: new sign-in creates an account; existing email requires verification before linking; a forged OAuth state is rejected
- [ ] The trust page is public and lists current subprocessors

### M7: Productivity

- [ ] Recurring cards: correct next date for daily/weekly/monthly/custom; running the job twice creates no duplicates
- [ ] Templates: saving and creating from a template copies structure but not comments, attachments, or members
- [ ] Filters and saved views return the right cards for each filter combination
- [ ] Automation rules fire once per event and can't trigger themselves in a loop
- [ ] The digest respects preferences and contains only accessible cards
- [ ] Inbound email: a valid address creates a card; an invalid or revoked address is rejected; HTML is sanitised; attachments follow the normal type and size rules

### M8: AI

- [ ] With AI disabled on a board, no request is made to the provider (`Http::fake` asserts nothing sent)
- [ ] Suggestions are not saved until the user confirms
- [ ] Prompts include only data from boards the user can access
- [ ] Plan usage limits are enforced; provider errors and timeouts show a friendly message
- [ ] Prompt injection in card content doesn't change actions taken or leak other boards

## 5. Manual QA checklist (per release)

- [ ] Light and dark themes on every new screen
- [ ] Phone width (≈375px), tablet, and desktop; drag-and-drop still works with the drag handle on touch
- [ ] Keyboard navigation and visible focus on new dialogs and forms
- [ ] Every new email checked in Gmail, Outlook, and Apple Mail (light and dark)
- [ ] Share pages viewed logged out, in a private window
- [ ] Error states: offline, failed upload, expired session, 429

## 6. Release checklist

- [ ] All milestone and regression tests pass in CI
- [ ] Migrations tested against a copy of production data, including rollback
- [ ] Feature flag on for your own account first, then all users
- [ ] Backups verified before the deploy
- [ ] Privacy Policy, Terms, and subprocessors list updated if data handling changed
- [ ] Changelog entry published
