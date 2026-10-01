# GraceSoft Skylight: SaaS Roadmap Milestones

Last updated: 2026-10-01

## Purpose

Turn Skylight from a personal Kanban board into **the secure way to run client work**: a SaaS product for solo professionals, freelancers, and small agencies who share progress with clients.

Milestones are ordered by dependency and impact. Each one should ship to production behind a feature flag, with its tests from `skylight-testing.md` passing, before the next one starts.

## Milestone overview

| ID  | Milestone                      | Why it matters                             | Depends on |
| --- | ------------------------------ | ------------------------------------------ | ---------- |
| M0  | Foundations and known gaps     | Pays off existing debt; trust features     | —          |
| M1  | Shared boards and roles        | Unblocks every team or client deal         | M0         |
| M2  | Trello import                  | Main switching path for new users          | M1         |
| M3  | Calendar view and iCal feed    | Makes existing dates useful                | M0         |
| M4  | Client portal                  | Core differentiator                        | M1         |
| M5  | Plans and billing              | Free / Pro / Studio gating                 | M1, M4     |
| M6  | Security and privacy dashboard | Makes security visible and sellable        | M1         |
| M7  | Productivity features          | Justifies the subscription                 | M1, M3     |
| M8  | Opt-in AI assistance           | Matches market expectations, privacy-first | M1, M5     |

Not on this roadmap for now: Gantt charts, a full automation builder, native mobile apps (ship a PWA instead), and real-time multi-cursor editing.

---

## M0: Foundations and known gaps

**Goal:** Close the gaps listed in the features document and add the data-rights features a privacy-first SaaS needs.

### Scope

- [ ] **Notification preferences screen** on the profile page for `due_today` and `overdue`, plus every new notification type added later
- [ ] **Bot token management UI:** create a named token (shown once), list tokens with last-used time, and revoke a token
- [ ] **Bot API rate limiter:** a dedicated `bot` limiter (e.g. 60/min per token), with `429` responses including `Retry-After`
- [ ] **Tags decision:** either ship tags in the UI (filter boards by tag) or drop the model, pivots, and migrations
- [ ] **Remove** the unused `livewire/boards/create-board-form.blade.php` placeholder
- [ ] **Data export:** download all of the user's data as JSON (boards → columns → cards → checklists, comments, notes, labels, attachment metadata) plus a CSV of cards; large exports are queued and emailed as a signed, expiring link
- [ ] **Account deletion:** password + 2FA confirmation, a 7-day grace period with a cancel link, then hard deletion of the user's data and stored files
- [ ] **PWA basics:** manifest, icons, and an installable shell (online-only for now)

### Data model

- No new tables for M0 except `account_deletion_requests` (or `users.deletion_scheduled_at`)

### Acceptance criteria

- A user can turn each reminder type on or off, and the daily job respects it
- A user can issue a bot token without tinker, and revoked tokens get `401` immediately
- The export contains every record the user owns and nothing belonging to anyone else
- After the grace period, no rows or files belonging to the deleted user remain (activity logs are anonymised or removed)

---

## M1: Shared boards and roles

**Goal:** Let several people work on one board with clear permissions.

### Scope

- [ ] **Board members** with roles: `owner`, `editor`, `commenter`, `viewer`
- [ ] **Invites by email:** signed, expiring invite links; existing users join directly, new users register and then join
- [ ] **Member management:** change role, remove member, transfer ownership, leave board
- [ ] **Policies** (`BoardPolicy`, `CardPolicy`, etc.) replacing the "owner only" checks across every Volt component action
- [ ] **Card assignees** (members only) shown on the board and in the card modal
- [ ] **Change awareness:** Livewire polling, or a "This board was updated — refresh" banner (no real-time sync yet)
- [ ] **Attribution:** activity log entries and comments show who did what
- [ ] **Search** includes boards the user is a member of
- [ ] **Bot API** returns boards and cards the token owner can access as a member, not only those they own
- [ ] **Notifications:** invited, role changed, removed, assigned to a card

### Data model

- `board_members` (`board_id`, `user_id`, `role`, timestamps), unique on (`board_id`, `user_id`)
- `board_invites` (`board_id`, `email`, `role`, `token_hash`, `expires_at`, `accepted_at`)
- `card_user` pivot for assignees
- Backfill: the existing owner of each board becomes an `owner` member

### Acceptance criteria

- Each role can do exactly what the permission matrix in `skylight-testing.md` allows, enforced on the server for every action
- Invite tokens are stored hashed, single-use, and expire
- A board always has exactly one owner
- Existing single-user boards keep working with no visible change

### Risk

- Every action in `boards/show` and `cards/detail` needs an authorisation check. The shared attachment partials are used in four places, so test all four.

---

## M2: Trello import

**Goal:** Let users bring their Trello boards into Skylight in a few clicks.

### Scope

- [ ] Upload a Trello board JSON export (no Trello API keys needed for v1)
- [ ] Map Trello lists → columns, cards → cards, labels → labels (closest colour), checklists → checklists, comments → comments (attributed as "Imported from Trello: <name>"), due and start dates, descriptions (markdown preserved), attachment links → URL attachments
- [ ] Archived lists and cards are skipped by default, with an option to include them
- [ ] Run as a queued job with a progress indicator and a summary when done (counts plus anything skipped)
- [ ] Imports never touch existing boards; each one creates a new board

### Acceptance criteria

- Importing a real Trello export reproduces its structure and order
- Malformed or oversized files fail with a clear message and leave no partial board behind
- Imported Markdown is rendered with the same sanitisation as native content

---

## M3: Calendar view and iCal feed

**Goal:** Make start and due dates visible in a calendar, inside and outside Skylight.

### Scope

- [ ] **Calendar view** per board (month and week), with cards placed by due date (or spanning start → due)
- [ ] Drag a card to a new day to change its due date (editors and owners only)
- [ ] **Personal calendar** across all of the user's boards
- [ ] **iCal feed:** a private, revocable `.ics` URL per user (and optionally per board), token stored hashed, `noindex` header, rate-limited
- [ ] Per-user timezone setting, used for the calendar, the feed, and the 08:00 reminder job

### Acceptance criteria

- The feed validates in Google Calendar, Apple Calendar, and Outlook
- Revoking the feed URL stops it working immediately
- Reminders are sent at 08:00 in each user's own timezone

---

## M4: Client portal

**Goal:** Turn read-only share links into a polished, controllable client view. This is Skylight's main differentiator.

### Scope

- [ ] **Link protection:** optional password, optional expiry date, optional email-gated access (a magic link sent to an allowed email)
- [ ] **Visibility per link:** choose which columns are visible; hide specific cards; keep comments and attachments toggles
- [ ] **Client feedback without an account:**
    - [ ] Clients can leave comments (marked as "client")
    - [ ] Clients can mark a card "Approved" or "Changes requested"
    - [ ] The board owner and assigned members are notified
- [ ] **Branding:** logo, accent colour, and display name on share pages and share-related emails
- [ ] **Link analytics for owners:** view count, last viewed, and unique visitors (based on the hashed IPs already recorded)
- [ ] **Checklist, comment, and note attachments** shown in the viewer when attachments are permitted (closes the current gap)
- [ ] **Custom subdomain** (`client.example.com` → share pages): _stretch goal_, can move to a later release

### Data model

- `board_share_links`: add `password_hash`, `expires_at`, `allowed_emails` (JSON) or a separate table, `visible_column_ids` (JSON), `allow_feedback`
- `share_link_feedback` (`share_link_id`, `card_id`, `type` [comment | approved | changes_requested], `body`, `client_email` nullable, hashed IP, timestamps)
- `branding` settings on the user or workspace

### Acceptance criteria

- Hidden columns and cards are never sent to the browser, not only hidden with CSS
- An expired, revoked, or wrong-password link reveals nothing about the board
- Client feedback is rate-limited and sanitised, and can't be used to post to boards that don't allow it

---

## M5: Plans and billing

**Goal:** Introduce Free, Pro, and Studio plans and charge for them.

### Scope

- [ ] Stripe subscriptions (e.g. Laravel Cashier), monthly and yearly
- [ ] A central entitlements service (`$user->can('feature')` / `Plan::allows()`), not plan checks scattered through views
- [ ] Proposed gating (adjust after pricing research):

| Feature                                     | Free    | Pro       | Studio    |
| ------------------------------------------- | ------- | --------- | --------- |
| Boards                                      | Limited | Unlimited | Unlimited |
| Basic share links                           | ✓       | ✓         | ✓         |
| Calendar, iCal, recurring cards, digest     | —       | ✓         | ✓         |
| Share-link password and expiry              | —       | ✓         | ✓         |
| Board members and roles                     | —       | —         | ✓         |
| Client feedback, branding, custom subdomain | —       | —         | ✓         |
| Audit log export, SSO                       | —       | —         | ✓         |

- [ ] Downgrade behaviour: data stays readable; gated features become read-only and are never deleted
- [ ] Billing portal, invoices, failed-payment emails, and a grace period
- [ ] Update the Terms of Service, Privacy Policy, and DPA for paid plans (add to the lawyer batch)

### Acceptance criteria

- Every gated feature checks the entitlements service on the server, not just in the UI
- A downgrade or a failed payment never deletes or hides existing customer data

---

## M6: Security and privacy dashboard

**Goal:** Make Skylight's security visible and sellable.

### Scope

- [ ] **Security page:** active sessions (device, approximate location, last active) with "log out" and "log out everywhere"; recent logins; active share links; API tokens; iCal feeds
- [ ] **Passkeys (WebAuthn)** as an alternative to TOTP
- [ ] **Audit log UI** per board (who changed what and when), with CSV export (Studio)
- [ ] **SSO:** Google and Microsoft sign-in (Studio), linked to existing accounts only after email verification
- [ ] **Public trust page:** data location, no trackers, no AI training on customer data, subprocessors list, security contact

### Acceptance criteria

- Logging out a session invalidates it immediately
- SSO can never take over an existing account without verification
- Audit logs never expose raw IP addresses

---

## M7: Productivity features

**Goal:** Give paying users daily reasons to stay.

### Scope

- [ ] **Recurring cards:** daily, weekly, monthly, or custom; a new card is created when the previous one is completed or on a schedule
- [ ] **Templates:** save a card or a board as a template; a starter set (client onboarding, content calendar, weekly sprint)
- [ ] **Filters and saved views:** by label, colour, assignee, and due status (due today, overdue, no date)
- [ ] **Simple automation rules** from a fixed list, e.g.:
    - When a card moves to column X → tick all checklist items / set colour / assign someone
    - When a card is due tomorrow → set colour
- [ ] **Daily digest** by email or Telegram: what's due and overdue across all boards
- [ ] **Inbound email → card:** a private forwarding address per board; the subject becomes the title and the body becomes the description

### Acceptance criteria

- Recurring cards never create duplicates, even if the job runs twice
- Automation rules can't loop (a rule triggering itself)
- Inbound email only accepts mail sent to a valid, unrevoked address and sanitises all content

---

## M8: Opt-in AI assistance

**Goal:** Offer useful AI features without breaking the privacy-first promise.

### Scope

- [ ] Off by default; enabled per board by the owner, with a clear notice of what is sent and to which provider
- [ ] **Brief → cards:** paste notes or a brief and get suggested columns, cards, and checklists to accept or edit
- [ ] **Client update summary:** "what moved this week" on a board, ready to send to a client
- [ ] **Ask your boards:** natural-language questions over the user's own data, through the GraceSoft Assistant
- [ ] Usage limits per plan; nothing is stored by the provider for training (where the provider allows)
- [ ] Update the Privacy Policy and subprocessors list

### Acceptance criteria

- No board content is ever sent to an AI provider unless that board has AI enabled
- AI suggestions are never applied without the user confirming
- Answers only draw on boards the user can access
