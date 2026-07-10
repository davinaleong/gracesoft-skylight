# GraceSoft Skylight — Discussion Points & Decisions

A running log of the architectural decisions made during planning, and the reasoning behind them.

## Naming

- **Decision:** GraceSoft Skylight
- Considered alternatives: Trellis, Ledger, Cairn, Slate, Hearth, Bastion, Loom, Waypost, Vestry, Vantage, Lookout, Aperture, Vista, GraceSoft Kanban, GraceSoft Board
- Rationale: fits the GraceSoft one-word naming pattern (Sentinel, Beacon, Capture, Desk, Sprout); "light in, but you control the frame" quietly nods to the viewer-link feature without spelling it out

## Relationship to GraceSoft Board

- **Decision:** Skylight is not currently the same as Board — Board is still undecided/in discussion
- Data model deliberately keeps `users` + `board_user` pivot in place so this could absorb or become the eventual Board product without a rearchitecture

## Tech Stack

- **Decision:** Laravel + Livewire/Volt, Postgres, S3-compatible storage
- Rationale: reuses existing GraceSoft muscle memory (Beacon, Capture, Board concept all Laravel); Livewire avoids standing up a separate SPA/API layer, reducing security surface area; Fortify gives email + TOTP 2FA out of the box rather than hand-rolling OTP crypto
- Fallback considered: Inertia + Svelte, kept as an option if full frontend/backend decoupling is ever wanted — not needed for v1

## Runtime / Hosting

- **Decision:** Web app (not desktop/local-only like Sprout)

## Users & Collaboration

- **Decision:** Strictly single-user for v1 — no roles/permissions system, no invite flows needed now
- **Decision:** `board_user` pivot table reserved (unused in v1) so multi-user/enterprise collaboration can be added later without breaking the schema

## Viewer / Share Links — the differentiator

- **Decision:** Read-only share links, scoped to a **whole board** (not column- or card-level) for v1
- Rationale: per-scope granularity (column/card-level) adds real complexity to the query layer — every read path would need scope-filtering logic. Deferred to a future `scope` + `scope_id` column (nullable, defaults to whole-board), which can be added later without a breaking migration
- Kept as coarse toggles: `can_see_comments`, `can_see_attachments` — on/off per link, not granular
- **Why this matters competitively:** most kanban tools force guests into an account or offer only an all-or-nothing public toggle. A no-signup, revocable, scoped viewer link is a real gap in the market for freelancer/client workflows

### Share link security decisions

- Tokens: 32-byte random, base62-encoded, stored **hashed** (never raw) — same pattern as password reset tokens
- Public viewer route rate-limited (~30 req/min/IP) since it's unauthenticated by design
- `X-Robots-Tag: noindex` on all viewer pages — never indexed by search engines
- Attachments viewed via share link served through short-lived signed S3 URLs (~5 min expiry), never permanent public URLs
- Share link lifecycle events (`created`, `revoked`, `accessed`) logged into the same activity log as owner actions

## Activity Log

- **Decision:** Discrete, meaningful action logging — not per-keystroke/field-diff CRDT-style tracking
- Rationale: per-keystroke logging is what you'd need for collaborative-editing conflict resolution; not needed for a single-user tool. Discrete events (create/move/update-with-diff/delete) give a sufficient "who did what when" trail for spotting unauthorized access
- Field-level diffs (old → new) captured specifically on `*.updated` events, not on every field independently
- IP addresses stored **hashed**, never raw — privacy-preserving even against yourself
- Auth events (`login.success/failed`, `2fa.verified/failed`) explicitly included since these matter most given the "detect unauthorized access" motivation

## Attachments

- **Decision:** S3-compatible storage (same pattern as Desk)
- Four attachment types: images, embeds, links, markdown notes
- Embeds flagged as highest-risk / build last — needs an allow-list of trusted embed sources to avoid arbitrary iframe injection
- Markdown notes get their own sub-record: name, content, date created

## Open / Deferred Questions

- [ ] Activity log retention policy — keep forever vs. auto-prune after N months?
- [ ] Whether to eventually add per-scope (column/card-level) share links
- [ ] Whether real-time collaborative editing is ever needed (would require reworking activity log granularity)
- [ ] Whether Skylight and the planned Board product formally merge, or stay separate

---

## Mail Notifications — Analysis & Priority

### Context constraints that shape the list

- **Single-user v1.** No collaboration means no "someone commented on your card" or "assigned to you" notifications — those whole categories are deferred.
- **Privacy-first.** Never send unsolicited digests; all non-security emails should be opt-in or directly user-triggered.
- **Security-first.** Security emails must always fire; they cannot be turned off by user preference.
- **Fortify already handles two.** Password-reset and email-verification mail are sent by Fortify out of the box — implement nothing new for those.

---

### P0 — Critical / Always Send (security, no opt-out)

These protect the user's account. They fire even if a future "notification preferences" system is added.

| #   | Notification                      | Trigger                                                                                      | Laravel mechanism                                        |
| --- | --------------------------------- | -------------------------------------------------------------------------------------------- | -------------------------------------------------------- |
| 1   | **Password reset link**           | User clicks "Forgot password"                                                                | Fortify built-in — already works                         |
| 2   | **Password changed confirmation** | `PasswordReset` event (after successful reset)                                               | `Notification` on `User` via event listener              |
| 3   | **2FA recovery code used**        | A recovery code is consumed during login                                                     | `Notification` on `User` via observer/event              |
| 4   | **Suspicious login alert**        | 5+ `login.failed` events for this account in a rolling 10-minute window                      | Rate-counted from `activity_logs`; queued `Notification` |
| 5   | **Login from new IP**             | `login.success` and the hashed IP has never appeared before in `activity_logs` for this user | `Notification` on `User` via login event listener        |

> **Implementation note on P0 #4 and #5:** These query `activity_logs` by `event` and `user_id`. Since IPs are stored hashed, "new IP" is checked by hashing the current request IP and comparing against past `ip_hash` values — no raw IPs ever handled.

---

### P1 — High / Transactional (user-triggered or lifecycle)

Send on explicit action; no opt-out is appropriate since the user directly caused the event.

| #   | Notification                        | Trigger                                                               | Laravel mechanism                                                                                                     |
| --- | ----------------------------------- | --------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------- |
| 6   | **Welcome / account created**       | `Registered` event after registration                                 | `Notification` on `User` via event listener                                                                           |
| 7   | **Email verification**              | Registration (if `emailVerification()` feature is enabled in Fortify) | Fortify built-in — enable `Features::emailVerification()`                                                             |
| 8   | **Share link created**              | Owner generates a new board share link                                | Queued `Notification` on `User` — sends the raw token one time before it's hashed, since the UI already shows it once |
| 9   | **Share link revoked confirmation** | Owner revokes a share link                                            | Queued `Notification` on `User`                                                                                       |

> **Implementation note on P1 #8:** The raw token is already shown once in the Volt UI. The email is a belt-and-suspenders copy for safekeeping. The token shown in the email is identical to what's displayed on-screen — it's the caller's responsibility not to re-display it from the database (which only stores the hash).

---

### P2 — Medium / Productivity (time-based, should be opt-in)

User-configurable; default could be on or off. Implement via a scheduled `Artisan` command + `Notification`.

| #   | Notification       | Trigger                                                                     | Default      |
| --- | ------------------ | --------------------------------------------------------------------------- | ------------ |
| 10  | **Card due today** | Scheduled: morning run checks `cards.ends_at = today`                       | On           |
| 11  | **Card overdue**   | Scheduled: daily run checks `cards.ends_at < today AND ends_at IS NOT NULL` | On           |
| 12  | **Weekly digest**  | Scheduled: Monday morning — summary of overdue cards + cards due this week  | Off (opt-in) |

> **Implementation note:** These require a `notification_preferences` column on `users` (JSON or separate table). Simplest for v1: a `notifications` JSON column with boolean keys like `due_today`, `overdue`, `weekly_digest`.

---

### P3 — Low / Nice-to-Have (future / multi-user path)

Deferred. These either require multi-user features not built yet, or are low signal-to-noise for a single-user tool.

| #   | Notification                      | Blocked by                                                                                                |
| --- | --------------------------------- | --------------------------------------------------------------------------------------------------------- |
| 13  | **Board shared with you**         | Requires `board_user` multi-user collaboration (not v1)                                                   |
| 14  | **Someone commented on a card**   | Requires multi-user (comments are currently single-author)                                                |
| 15  | **Share link viewed by a client** | Low value for single-user; spammy if the client views often. Better suited to an in-app activity log page |
| 16  | **Attachment upload failed**      | Requires async upload pipeline with job failure handling                                                  |
| 17  | **Account inactivity (30 days)**  | Privacy concern — only send if user explicitly opts in                                                    |

---

### Recommended build order

1. **P0 #1–2** — Password reset already works (Fortify). Add password-changed confirmation (one `Notification` class + event listener). Low effort, high value.
2. **P0 #4–5** — Suspicious login + new IP alert. Query `activity_logs` in the `Login` event listener already wired in `AppServiceProvider`. Add IP-history check and queue a notification.
3. **P0 #3** — 2FA recovery code consumed. Hook into Fortify's recovery code validation.
4. **P1 #6** — Welcome email. One `Notification` class + listen to `Registered` event.
5. **P2 #10–11** — Due-today and overdue card reminders. Add a scheduled command + one `Notification` class. Add `notification_preferences` JSON column to `users`.
6. **P1 #8–9 and P2 #12** — Share link emails and weekly digest when there's demand.
