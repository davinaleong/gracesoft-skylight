# GraceSoft Skylight — Mail Notifications

Analysis of all mail notifications the app should send, categorised by priority.

---

## Context constraints

- **Single-user v1.** No collaboration → no "assigned to you" or "someone replied" notifications. Those categories are deferred to multi-user.
- **Privacy-first.** Never send unsolicited digests; all non-security emails should be opt-in or directly user-triggered.
- **Security-first.** Security emails must always fire and cannot be disabled by user preferences.
- **Fortify already handles two.** Password-reset and email-verification mail are sent by Fortify — nothing new to implement for those.

---

## P0 — Critical / Always Send (security, no opt-out)

These protect the account. They fire regardless of any future notification preferences.

| #   | Notification                      | Trigger                                                                                          | Implementation                                                                                                                                                            |
| --- | --------------------------------- | ------------------------------------------------------------------------------------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | **Password reset link**           | User clicks "Forgot password"                                                                    | Fortify built-in — already works                                                                                                                                          |
| 2   | **Password changed confirmation** | `PasswordReset` Fortify event fires after a successful reset                                     | `Notification` on `User` via event listener in `AppServiceProvider`                                                                                                       |
| 3   | **2FA recovery code used**        | A recovery code is consumed during login                                                         | `Notification` on `User` via Fortify event or observer on `User.two_factor_recovery_codes` update                                                                         |
| 4   | **Suspicious login alert**        | 5+ `login.failed` events for this account within a rolling 10-minute window                      | Count rows in `activity_logs` by `event = 'login.failed'` and `user_id`; queue a `Notification` from the `Failed` event listener already in `AppServiceProvider`          |
| 5   | **Login from new IP**             | `login.success` with a hashed IP that has never appeared before in `activity_logs` for this user | Hash current request IP; compare against past `ip_hash` values in `activity_logs`; queue a `Notification` from the `Login` event listener already in `AppServiceProvider` |

> **Privacy note on #4 and #5:** The IP comparison uses only hashed values already stored in `activity_logs` — no raw IPs are ever read or sent in the email.

---

## P1 — High / Transactional (user-triggered or account lifecycle, no opt-out)

The user directly caused the event, so sending is appropriate without an opt-in preference.

| #   | Notification                        | Trigger                                                                              | Implementation                                                                                                                   |
| --- | ----------------------------------- | ------------------------------------------------------------------------------------ | -------------------------------------------------------------------------------------------------------------------------------- |
| 6   | **Welcome / account created**       | `Registered` Fortify event fires after registration                                  | `Notification` on `User` via event listener                                                                                      |
| 7   | **Email verification**              | Registration — if `Features::emailVerification()` is enabled in `config/fortify.php` | Fortify built-in — just uncomment the feature flag                                                                               |
| 8   | **Share link created (token copy)** | Owner generates a new board share link                                               | Queued `Notification` on `User` — sends the raw token once (mirrors the one-time display already in the Volt UI, before hashing) |
| 9   | **Share link revoked confirmation** | Owner revokes an active share link                                                   | Queued `Notification` on `User`                                                                                                  |

> **Security note on #8:** The email is a belt-and-suspenders copy for the owner's records. The raw token is available at creation time only. It must never be re-read from the database (which stores only the SHA-256 hash).

---

## P2 — Medium / Productivity (time-based, opt-in)

User-configurable. Default: on for reminders, off for digest. Requires a `notification_preferences` JSON column on `users` (or a separate `user_notification_preferences` table for cleanliness).

Suggested preference keys: `due_today` (bool), `overdue` (bool), `weekly_digest` (bool).

| #   | Notification       | Trigger                                                                                              | Default      |
| --- | ------------------ | ---------------------------------------------------------------------------------------------------- | ------------ |
| 10  | **Card due today** | Scheduled command (morning) — queries `cards.ends_at = today` for all active cards owned by the user | On           |
| 11  | **Card overdue**   | Scheduled command (daily) — queries `cards.ends_at < today AND ends_at IS NOT NULL`                  | On           |
| 12  | **Weekly digest**  | Scheduled command (Monday morning) — summary of overdue cards + cards due in the next 7 days         | Off (opt-in) |

> **Implementation pattern:** One `SendCardDueReminders` Artisan command queued via `Schedule::command(...)->dailyAt('08:00')`. One `CardDueNotification` class that accepts a `Collection` of cards and the notification type, so a single Mailable covers all three cases.

---

## P3 — Deferred / Low Priority

These require multi-user features not yet built, or are low signal-to-noise for a single-user tool.

| #   | Notification                     | Why deferred                                                                     |
| --- | -------------------------------- | -------------------------------------------------------------------------------- |
| 13  | Board shared with a collaborator | Requires `board_user` multi-user roles — not v1                                  |
| 14  | Someone commented on your card   | Requires multi-user — comments are currently single-author                       |
| 15  | Share link viewed by a client    | Spammy if the client views multiple times; better as an in-app activity log page |
| 16  | Attachment upload failed         | Requires async upload pipeline with job failure handling                         |
| 17  | Account inactivity (30+ days)    | Privacy concern — only send if user explicitly opts in; deferred                 |

---

## Recommended build order

1. **P0 #2** — Password changed confirmation. One `PasswordChangedNotification` class + listen to Fortify's `PasswordReset` event. Low effort.
2. **P0 #4 & #5** — Suspicious login and new-IP alert. Both build on the `Login` / `Failed` event listeners already wired in `AppServiceProvider`. Add IP-history check; queue a notification.
3. **P1 #6** — Welcome email. One `WelcomeNotification` class + listen to Fortify's `Registered` event.
4. **P0 #3** — Recovery code used. Hook into Fortify's `TwoFactorAuthenticationFailed` event or the point where recovery codes are decremented.
5. **P2 #10 & #11** — Due-today and overdue reminders. Add `notification_preferences` to `users`, one scheduled command, one `CardDueNotification`.
6. **P1 #8 & #9 and P2 #12** — Share link emails and weekly digest when there's demand.
