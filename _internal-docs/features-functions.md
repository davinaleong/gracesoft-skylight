# GraceSoft Skylight: Features and Functions

Last updated: 2026-10-01

## 1. Overview

GraceSoft Skylight is a Kanban board SaaS built on Laravel 13, Livewire 4 + Volt, Fortify, Sanctum, Cashier (Stripe), and Tailwind CSS 4. It provides:

- Accounts with email verification, two-factor authentication, and Google/GitHub sign-in
- Workspaces (teams) with owner/admin/member/viewer roles and email invites
- Boards, columns, and cards with drag-and-drop, filters, templates, and an activity feed
- Rich card details: dates, colours, labels, checklists, comments with @mentions, markdown notes, and attachments
- Client portals: revocable, expiring, read-only public share links with view counts
- Free / Pro / Team plans with Stripe billing and usage limits
- A public REST API, a read-only bot API, outgoing webhooks, and Slack notifications
- In-app and email notifications, activity logging, and data export
- Public marketing, pricing, changelog, status, security, privacy, and terms pages

Roadmap features ship behind Laravel Pennant feature flags (see §2.15).

## 2. Features

### 2.1 Authentication and Account

- Register (5/min per IP), verify email, log in (with "remember me"), log out
- Sign in or sign up with Google or GitHub (Socialite)
- Forgot password / reset password; confirm password before sensitive actions
- Profile page: name, email, password, avatar upload/remove
- Two-factor authentication (TOTP): enable, confirm, disable, view and regenerate recovery codes; log in with a code or a recovery code
- API tokens: create a named token (shown once) as **full API access** or **read-only bot**, list with last-used time, revoke (takes effect on the next request)
- Email notification preferences: switch due-today and overdue reminders on or off (M0 flag)
- Export my data (M0 flag): a zip with `skylight-export.json` (account, memberships, token names, full content of boards in owned workspaces including attachment metadata, and the user's own comments/notes elsewhere) and `cards.csv`. Over 500 cards, the export is queued and emailed as a signed link valid for 24 hours that only works for the signed-in owner; archives are pruned hourly once expired
- Delete account (M0 flag): requires the password plus a 2FA or recovery code when 2FA is on (5 attempts/min); blocked while an owned workspace has other members or a paid plan. Deletion is scheduled 7 days out with a "Keep my account" email link (signed, works signed out) and a cancel button on the profile. After the grace period an hourly job permanently removes the user, the workspaces they own (boards, files), their comments/notes and uploads elsewhere, tokens, sessions, notifications, pending invites, and flag values; boards they created in other workspaces are handed to that workspace's owner, and their remaining activity log entries are anonymised
- New users get a personal workspace and a seeded demo board

### 2.2 Workspaces and Teams

- Every user has a personal workspace; boards belong to a workspace
- Roles: **owner** (one per workspace, permanent), **admin**, **member**, **viewer**
    - Owners and admins manage members and invites
    - Owners, admins, and members edit content; viewers are read-only
- Invite by email with a token link (expiring, single-use); existing users accept directly
- Team page: change role, remove member, resend or cancel invites
- Switch between workspaces you belong to

### 2.3 Boards

- Dashboard listing the current workspace's boards
- Create a board from a template: blank, sprint, content calendar, or client onboarding
- Delete a board
- Board limit per plan (see §2.10)
- Boards are addressed by UUID in URLs
- Export a board as JSON or CSV

### 2.4 Columns and Cards

- Add, delete, and drag-to-reorder columns
- Add, delete, reorder, and move cards between columns by drag-and-drop (drag handle)
- Inline card editing: title, markdown description, start and due dates, colour (yellow, pink, blue, green)
- Filters: by label, by column, and by due status (overdue, due today, no due date)
- Empty-state prompts for boards, columns, and cards

### 2.5 Labels

- Board-level labels (name + hex colour); create, delete, and toggle on cards

### 2.6 Card Detail Modal

- Start and due dates (due on or after start)
- **Checklists:** create/delete checklists; add, tick, and delete items; progress per checklist
- **Comments:** up to 2,000 characters, @mentions of workspace members (autocomplete, notification), delete your own
- **Markdown notes:** create, edit, and delete your own named notes
- **Attachments:** images (jpg, jpeg, png, gif, webp, svg, bmp) or PDFs up to 10 MB, or URL links; on the card or on a checklist, comment, or note; thumbnails and lightbox preview; ~5 minute temporary URLs; S3-compatible storage

### 2.7 Search, Navigation, and Shortcuts

- Global search (from 2 characters) across board names (5 results) and card titles (8 results)
- Keyboard shortcuts: `/` search, `c` create, `?` help overlay
- Onboarding checklist widget: create a board, invite a teammate, add a profile photo
- Light/dark theme toggle

### 2.8 Client Portals (Share Links)

- Generate share links with optional comments and attachments visibility
- Expiry options: never, 7, 30, or 90 days
- Token shown once; only a SHA-256 hash is stored
- Revoke links; view count per link
- Public viewer at `/view/{token}`: read-only columns and cards, card dialog with markdown, dates, checklists, and (if allowed) comments and attachments
- 30 requests/minute per IP; `X-Robots-Tag: noindex, nofollow`; each visit recorded with a hashed IP

### 2.9 Activity and Notifications

- Per-board activity feed
- Activity log events: board, card (created/updated/moved/deleted), login success/failure, 2FA, share links; IPs stored as hashes
- In-app notification bell with unread count and mark-as-read
- Emails: welcome, password changed, new-IP login, suspicious logins, recovery code used, share link created/revoked, card due today/overdue, @mention, workspace invitation
- Daily due/overdue reminders at 08:00, respecting per-user `due_today` / `overdue` preferences

### 2.10 Plans and Billing

| Plan | Price/month | Boards | Members |
| --- | --- | --- | --- |
| Free | $0 | 3 | 3 |
| Pro | $12 | 25 | 10 |
| Team | $29 | Unlimited | Unlimited |

- Stripe Checkout subscriptions per workspace (Cashier) and Stripe billing portal
- Limits enforced server-side by `PlanLimiter`

### 2.11 Integrations

- **REST API** (`/api/v1`, full-access Sanctum tokens only, 60/min): boards, columns, cards, comments
- **Bot API** (`/api/bot`, read-only bot or full tokens, 60/min per token): due cards and board summaries for the GraceSoft Assistant Telegram bot
- **Outgoing webhooks** per workspace: `card.created`, `card.moved`, `card.completed`, `card.deleted`; signed payloads, delivery log, enable/disable
- **Slack:** incoming-webhook notifications for card events, with a test message
- Zapier / Make.com documentation page

### 2.12 Public Pages

- Landing, pricing, changelog/roadmap, feedback (GraceSoft Capture embed)
- Status page (database, cache, storage, queue checks; 30/min per IP)
- Security, privacy policy, terms of service

### 2.13 Operations

- Nightly backups (database, storage, `.env`) at 01:00, cleanup at 01:30, health monitor at 02:00
- Sentry error tracking (no-op without a DSN)

### 2.14 Rate Limits

| Area | Limit |
| --- | --- |
| Login | 5/min per email + IP |
| Two-factor challenge | 5/min per login session |
| Registration | 5/min per IP |
| Public viewer | 30/min per IP |
| Status page | 30/min per IP |
| REST API | 60/min per user |
| Bot API | 60/min per token (429 with `Retry-After`) |

### 2.15 Feature Flags

- Laravel Pennant, database store
- `App\Features\M0Foundations`: on for everyone when `FEATURE_M0_ENABLED=true`, otherwise only for `FEATURE_EARLY_ACCESS_EMAILS`; run `php artisan pennant:purge` after changing either

## 3. Routes

### 3.1 Web

| Method | Path | Access | Purpose |
| --- | --- | --- | --- |
| GET | `/` | Public | Landing page (redirects to `/home` when logged in) |
| GET | `/pricing`, `/changelog`, `/feedback`, `/security`, `/privacy`, `/terms` | Public | Marketing and legal pages |
| GET | `/status` | Public, 30/min | System status |
| GET | `/auth/{google\|github}/redirect`, `/auth/{provider}/callback` | Guest | OAuth sign-in |
| GET | `/profile` | Logged in | Profile, security, avatar, API tokens |
| POST | `/account/export` | Logged in, M0 flag, 3 per 10 min | Download or queue the account export |
| GET | `/account/exports/{file}` | Logged in, signed URL, owner only | Download a queued export |
| GET | `/home` | Verified | Board dashboard |
| GET | `/boards/{board}` | Verified, workspace member | Board view |
| GET | `/boards/{board}/export?format=csv` | Verified, workspace member | JSON (default) or CSV export |
| GET | `/team`, `/team/{workspace}` | Verified, member | Team management |
| GET | `/billing`, `/webhooks`, `/integrations` | Verified | Workspace settings |
| GET | `/account/deletion/cancel/{user}` | Signed URL, 6/min | Cancel a scheduled account deletion |
| GET | `/invites/{token}` | Public | Invite landing page |
| POST | `/invites/{token}/accept` | Logged in | Accept invite |
| GET | `/view/{token}` | Public, 30/min | Client portal viewer |

Fortify provides login, registration, logout, password reset, password confirmation, email verification, and two-factor routes.

### 3.2 API

| Method | Path | Notes |
| --- | --- | --- |
| GET | `/api/user` | Token owner |
| GET/POST/GET/PATCH/DELETE | `/api/v1/boards[/{board}]` | Board CRUD |
| GET/POST | `/api/v1/boards/{board}/columns` | List/create columns |
| PATCH/DELETE | `/api/v1/columns/{column}` | Update/delete column |
| GET/POST | `/api/v1/columns/{column}/cards` | List/create cards |
| GET/PATCH/DELETE | `/api/v1/cards/{card}` | Card |
| GET/POST | `/api/v1/cards/{card}/comments` | Comments |
| DELETE | `/api/v1/comments/{comment}` | Delete comment |
| GET | `/api/bot/cards/due` | `{ date, due_today, overdue }` |
| GET | `/api/bot/boards/summary` | `{ boards: [{ board, columns: [{ column, card_count }] }] }` |

## 4. Console Commands and Scheduler

| Command | Description |
| --- | --- |
| `app:create-user` | Create a user (`--name`, `--email`, `--password`) |
| `app:test-mail` | Send a sample notification (`--type`, `--to`) |
| `app:send-card-due-reminders` | Send due-today and overdue reminders |
| `app:prune-account-exports` | Delete queued export archives after their link expires |
| `app:purge-deleted-accounts` | Permanently delete accounts whose grace period ended (skips any still blocked) |

Scheduled: export pruning and account purging hourly; reminders 08:00; `backup:run` 01:00, `backup:clean` 01:30, `backup:monitor` 02:00.

## 5. Data Model

| Entity | Purpose |
| --- | --- |
| User | Account, 2FA, OAuth ids, avatar, notification preferences, API tokens, `deletion_scheduled_at` |
| Workspace | Team/tenant with plan, Stripe customer, Slack settings |
| `workspace_user` | Membership with role |
| WorkspaceInvite | Hashed invite token, role, expiry |
| Board | Belongs to a workspace (and creator); UUID route key |
| Column, Card, Label, Checklist, ChecklistItem, Comment, MarkdownNote | Board content |
| Attachment | Image, PDF, or link; polymorphic on Card, Checklist, Comment, MarkdownNote |
| BoardShareLink, ShareLinkAccess | Client portal links (hashed token, expiry) and visits |
| ActivityLog | Audit events (hashed IP) |
| Webhook, WebhookDelivery | Outgoing webhooks and delivery log |
| Subscription, SubscriptionItem | Cashier billing |
| PersonalAccessToken | Sanctum tokens |
| `features` | Pennant flag values |

## 6. Function Reference

### 6.1 Actions, Concerns, Commands, Features

- `Actions/Fortify`: `CreateNewUser::create`, `PasswordValidationRules::passwordRules`, `ResetUserPassword::reset`, `UpdateUserPassword::update`, `UpdateUserProfileInformation::update`, `updateVerifiedUser`
- `Concerns/AuthorizesWorkspaceEditing`: `authorizeEdit`, `authorizeView`, `workspaceFor`
- `Console/Commands`: `CreateUser::handle`, `askValid`; `PruneAccountExports::handle`; `PurgeDeletedAccounts::handle`; `SendCardDueReminders::handle`; `TestMail::handle`, `buildNotification`, `fakeCards`
- `Features/M0Foundations::resolve(?User $user): bool`

### 6.2 Controllers and Resources

- `Api/Bot/BoardsController::summary`; `Api/Bot/CardsController::due`, `formatCard`
- `Api/V1/BoardController`: `index`, `show`, `store`, `update`, `destroy`
- `Api/V1/ColumnController`: `index`, `store`, `update`, `destroy`
- `Api/V1/CardController`: `index`, `show`, `store`, `update`, `destroy`
- `Api/V1/CommentController`: `index`, `store`, `destroy`
- `Auth/SocialiteController`: `redirect`, `callback`, `findOrCreateUser`
- `AccountExportController`: `store`, `download`
- `BoardExportController::__invoke`, `csv`
- `WorkspaceInviteController`: `show`, `accept`
- `Resources`: `BoardResource`, `ColumnResource`, `CardResource`, `CommentResource`, `LabelResource` (`toArray`)

### 6.3 Jobs and Services

- `Jobs/ExportAccountData::handle`, `Jobs/SendSlackMessage::handle`, `Jobs/SendWebhookRequest::handle`
- `AccountExporter`: `shouldQueue`, `toArray`, `cardRows`, `writeArchive`, `toCsv`, `boardToArray`, `attachmentsToArray`, `ownedWorkspaces`, `ownedCards`
- `AccountDeletion`: `GRACE_PERIOD_DAYS`, `blockers`, `schedule`, `cancel`, `due`, `purge`, `storedFilePaths`, `attachmentsOnOwnContributions`, `attachmentsOnCards`
- `ActivityLogger`: `log`, `hashIp`, `diff`
- `BoardExporter`: `toArray`, `toJson`, `toCsvRows`
- `BoardTemplates`: `isValid`, `apply`
- `DemoBoardSeeder::seed`
- `PlanLimiter`: `boardLimit`, `memberLimit`, `canCreateBoard`, `canInviteMember`
- `SlackNotifier::notifyCardEvent`, `message`
- `SystemStatusService`: `checks`, `isHealthy`, `database`, `cache`, `storage`, `queue`
- `WebhookDispatcher::dispatch`

### 6.4 Models

- **ActivityLog:** `user`, `subject`
- **Attachment:** `attachable`, `user`, `isImage`, `isDocument`, `isPdf`, `isLink`, `temporaryUrl`
- **Board:** `booted`, `getRouteKeyName`, `user`, `workspace`, `columns`, `labels`, `shareLinks`
- **BoardShareLink:** `board`, `accesses`, `isRevoked`, `isExpired`, `isActive`, `generateToken`, `findByToken`
- **Card:** `column`, `scopeDueToday`, `scopeOverdue`, `labels`, `checklists`, `comments`, `attachments`, `markdownNotes`
- **Checklist:** `card`, `items`, `attachments`; **ChecklistItem:** `checklist`
- **Column:** `board`, `cards`
- **Comment:** `card`, `user`, `attachments`; **Label:** `board`, `cards`; **MarkdownNote:** `card`, `user`, `attachments`
- **ShareLinkAccess:** `shareLink`
- **User:** `API_TOKEN_TYPES`, `NOTIFICATION_PREFERENCES`, `boards`, `workspaces`, `currentWorkspace`, `wantsNotification`, `avatarUrl`, `mentionHandle`
- **Webhook:** `workspace`, `createdBy`, `deliveries`, `generateSecret`, `subscribesTo`, `sign`; **WebhookDelivery:** `webhook`
- **Workspace:** `createForUser`, `owner`, `users`, `hasMember`, `roleOf`, `isOwner`, `isViewer`, `canManageMembers`, `canEditContent`, `canChangeMember`, `boards`, `invites`, `webhooks`, `slackIsConnected`, `slackNotifiesOn`, `stripeEmail`, `planLimits`
- **WorkspaceInvite:** `workspace`, `inviter`, `isAccepted`, `isExpired`, `isPending`, `accept`, `generateToken`, `findByToken`

### 6.5 Notifications

`Account/AccountDeletionScheduled` (`cancelUrl`), `Account/AccountExportReady` (`downloadUrl`), `Auth/NewIpLogin`, `Auth/PasswordChanged`, `Auth/RecoveryCodeUsed`, `Auth/SuspiciousLogin`, `Auth/Welcome`, `Board/ShareLinkCreated`, `Board/ShareLinkRevoked`, `Card/CardDue`, `Card/CommentMention`, `Workspace/WorkspaceInvitation` (each `via`, `toMail`, and `toArray` where shown in the bell)

### 6.6 Observers and Providers

- `BoardObserver`, `CardObserver` (activity log, webhooks, Slack), `UserObserver` (personal workspace + demo board)
- `AppServiceProvider::boot`: observers, rate limiters, auth event listeners
- `FortifyServiceProvider::boot`, `VoltServiceProvider::boot`

### 6.7 Volt Components (`resources/views/livewire`)

- **boards/index:** `boards`, `atBoardLimit`, `create`, `delete`
- **boards/show:** `mount`, `createColumn`, `deleteColumn`, `createCard`, `deleteCard`, `startEditCard`, `saveCard`, `updateColumnOrder`, `moveCard`, `createLabel`, `deleteLabel`, `toggleCardLabel`, `cardMatchesFilters`, `toggleLabelFilter`, `hasActiveFilters`, `clearFilters`
- **boards/share-links:** `mount`, `shareLinks`, `generate`, `revoke`
- **boards/activity:** `mount`, `activities`, `describe`, `columnName`
- **cards/detail:** `mount`, `checklists`, `saveDates`, `createChecklist`, `deleteChecklist`, `createItem`, `toggleItem`, `deleteItem`, `comments`, `workspaceMembers`, `addComment`, `notifyMentions`, `renderCommentBody`, `deleteComment`, `attachments`, `markdownNotes`, `uploadFile`, `addLink`, `deleteAttachment`, `openAttachmentForm`, `closeAttachmentForm`, `uploadItemFile`, `addItemLink`, `deleteItemAttachment`, `resolveAttachTarget`, `fileUploadRules`, `resolveAttachmentType`, `saveNote`, `editNote`, `deleteNote`
- **notifications/bell:** `notifications`, `unreadCount`, `markAsRead`, `markAllAsRead`
- **onboarding/checklist:** `steps`, `isComplete`, `shouldShow`, `dismiss`
- **profile/notification-preferences:** `mount`, `save`
- **profile/delete-account:** `blockers`, `requiresTwoFactor`, `scheduleDeletion`, `cancelDeletion`, `verifyTwoFactor`
- **profile/api-tokens:** `tokens`, `create`, `revoke`, `dismissToken`
- **profile/avatar:** `avatarUrl`, `upload`, `remove`
- **search/global:** `results`
- **workspaces/billing:** `mount`, `plans`, `subscribe`, `manageBilling`
- **workspaces/integrations:** `mount`, `connectSlack`, `disconnectSlack`, `sendTestMessage`
- **workspaces/team:** `mount`, `otherWorkspaces`, `canManage`, `members`, `pendingInvites`, `invite`, `changeRole`, `removeMember`, `resendInvite`, `cancelInvite`
- **workspaces/webhooks:** `mount`, `webhooks`, `create`, `toggleActive`, `delete`, `dismissSecret`

## 7. Known Gaps

- The bot API scopes by board creator (`user_id`) rather than workspace membership (M1)
- The public viewer only shows card-level attachments, not those on checklists, comments, or notes (M4)
- `composer audit` reports 22 advisories across 6 packages
- Abandoned `livewire-tmp/` uploads on S3 are never cleaned up (needs an S3 lifecycle rule)
