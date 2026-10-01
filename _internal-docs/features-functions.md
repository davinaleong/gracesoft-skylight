# GraceSoft Skylight: Features and Functions

Last updated: 2026-10-01

## 1. Overview

GraceSoft Skylight is a personal Kanban board application built on Laravel 13, Livewire 4 + Volt, Fortify, and Tailwind CSS 4. It provides:

- Account management with two-factor authentication
- Boards, columns, and cards with drag-and-drop ordering
- Rich card details: dates, colours, labels, checklists, comments, markdown notes, and attachments
- Revocable, read-only public share links for boards
- Global search across boards and cards
- Activity logging, security alert emails, and due-date reminder emails
- A token-authenticated, read-only API for the GraceSoft Assistant Telegram bot

## 2. Features

### 2.1 Authentication and Account

- Register, log in (with "remember me"), and log out
- Forgot password / reset password
- Confirm password before sensitive actions
- Profile page: update name and email, change password
- Two-factor authentication (TOTP):
    - Enable, confirm with a code (QR code and setup key shown), and disable
    - View and regenerate recovery codes
    - Log in with an authenticator code or a recovery code

### 2.2 Boards

- Home dashboard listing the user's own boards
- Create a board (name required, description optional) and delete a board
- Boards are addressed by UUID in URLs; internal IDs are never exposed
- Only the board owner can open a board

### 2.3 Columns and Cards

- Add and delete columns; reorder columns by drag-and-drop
- Add and delete cards in a column
- Reorder cards within a column and move cards between columns by drag-and-drop (SortableJS, via a drag handle so buttons and inputs stay clickable)
- Inline card editing on the board: title, description (markdown), start date, due date, colour
- Card colours: yellow, pink, blue, green (each with light and dark theme variants)
- Labels shown on cards in the board view

### 2.4 Labels

- Board-level labels with a name and a hex colour
- Create and delete labels from the board's label manager
- Toggle labels on and off for each card

### 2.5 Card Detail Modal

- Full card view in a modal
- Start and due dates (due date must be on or after start date)
- **Checklists:** create and delete checklists; add, tick off, and delete items; progress shown per checklist
- **Comments:** add comments (up to 2,000 characters); delete your own
- **Markdown notes:** create, edit, and delete your own named notes
- **Attachments:**
    - Upload images (jpg, jpeg, png, gif, webp, svg, bmp) or PDFs, up to 10 MB
    - Add URL links with an optional display name
    - Attach to the card itself, or to a specific checklist, comment, or markdown note
    - Thumbnails for images and a PDF icon for documents
    - Lightbox preview (images inline, PDFs in the browser's viewer) with an "open in new tab" link
    - Files are served through short-lived (~5 minute) temporary URLs
    - Delete your own attachments; the stored file is removed too
    - If an upload doesn't finish transferring, a retry message is shown instead of a server error

### 2.6 Search

- Search box in the top navigation (starts after 2 characters)
- Matches board names (up to 5 results) and card titles (up to 8 results)
- Only searches the user's own boards
- Results open the matching board or card

### 2.7 Board Sharing (Public Read-Only Links)

- Generate share links for a board, with optional permissions:
    - Show comments
    - Show attachments
- The link is shown once at creation and can be copied to the clipboard; only a SHA-256 hash of the token is stored
- Revoke share links at any time
- Public viewer at `/view/{token}`:
    - Shows columns and cards with colour accents
    - Clicking a card opens a read-only dialog with the markdown description, dates, checklists (accordion), and—if permitted—comments and attachments with an image lightbox
    - Limited to 30 requests per minute per IP
    - Sends `X-Robots-Tag: noindex, nofollow` so search engines don't index it
    - Each visit is recorded (hashed IP and user agent)

### 2.8 Email Notifications

| Notification | Trigger |
| --- | --- |
| Welcome | Account registered |
| Password changed | Password reset completed |
| New IP login | First successful login from an IP address not seen before |
| Suspicious login | Every 5th failed login within 10 minutes |
| Recovery code used | A 2FA recovery code is consumed |
| Share link created | User creates a share link |
| Share link revoked | User revokes a share link |
| Card due today | Daily reminder for cards ending today |
| Card overdue | Daily reminder for cards past their due date |

Due-today and overdue reminders respect per-user preferences (`due_today`, `overdue`), which default to on.

### 2.9 Activity Logging

Events are recorded in the activity log, with IP addresses stored only as hashes:

- `board.created`, `board.updated`, `board.deleted`
- `card.created`, `card.updated`, `card.moved`, `card.deleted`
- `login.success`, `login.failed`
- `2fa.challenged`, `2fa.enabled`, `2fa.failed`
- `share_link.created`, `share_link.revoked`, `share_link.accessed`

### 2.10 Internal Bot API (GraceSoft Assistant)

- Read-only JSON endpoints for the GraceSoft Assistant Telegram bot
- Authenticated with Sanctum personal access tokens (bearer token)
- Returns only the token owner's data
- Bot tokens cannot be used to access the website's logged-in pages

### 2.11 Interface

- Light/dark theme toggle, remembered in the browser (local storage)
- Branded HTML email templates with dark styling

### 2.12 Rate Limits

| Area | Limit |
| --- | --- |
| Login | 5 attempts/minute per email + IP |
| Two-factor challenge | 5 attempts/minute per login session |
| Public board viewer | 30 requests/minute per IP |

## 3. Routes

### 3.1 Web

| Method | Path | Access | Purpose |
| --- | --- | --- | --- |
| GET | `/` | Public | Redirects to `/home` if logged in, otherwise to login |
| GET | `/home` | Logged in | Board dashboard |
| GET | `/profile` | Logged in | Profile and security settings |
| GET | `/boards/{board}` | Logged in, board owner | Board view |
| GET | `/view/{token}` | Public, rate-limited | Read-only shared board |

Fortify also provides routes for login, registration, logout, password reset, password confirmation, the two-factor challenge, and two-factor management.

### 3.2 API (`/api`, Sanctum token required)

| Method | Path | Response |
| --- | --- | --- |
| GET | `/api/user` | The token owner (Laravel default route) |
| GET | `/api/bot/cards/due` | `{ date, due_today: [...], overdue: [...] }`. Each card has `title`, `board`, `column`, and `ends_at` |
| GET | `/api/bot/boards/summary` | `{ boards: [{ board, columns: [{ column, card_count }] }] }` |

## 4. Console Commands and Scheduler

| Command | Description | Options |
| --- | --- | --- |
| `app:create-user` | Create a user account (interactive prompts with validation) | `--name`, `--email`, `--password` |
| `app:test-mail` | Send a sample notification email to preview the template | `--type` (`welcome`, `password-changed`, `new-ip-login`, `suspicious-login`, `recovery-code-used`, `share-link-created`, `share-link-revoked`, `card-due-today`, `card-overdue`), `--to` (defaults to the first user) |
| `app:send-card-due-reminders` | Email due-today and overdue reminders to each user | — |

Scheduled task: `app:send-card-due-reminders` runs daily at 08:00.

## 5. Data Model

| Entity | Purpose | Key relationships |
| --- | --- | --- |
| User | Account; 2FA; notification preferences; API tokens | has many Boards, Tags |
| Board | A Kanban board (UUID route key) | belongs to User; has many Columns, Labels, ShareLinks; many-to-many Tags |
| Column | A list on a board, ordered by `position` | belongs to Board; has many Cards; many-to-many Tags |
| Card | A task with dates, colour, and description | belongs to Column; many-to-many Labels; has many Checklists, Comments, MarkdownNotes; has many Attachments (polymorphic) |
| Label | Board-level coloured label | belongs to Board; many-to-many Cards |
| Checklist / ChecklistItem | Checklists and their items | Checklist belongs to Card and has Attachments |
| Comment | User comment on a card | belongs to Card, User; has Attachments |
| MarkdownNote | Named markdown note on a card | belongs to Card, User; has Attachments |
| Attachment | Image, PDF document, or link | polymorphic `attachable` (Card, Checklist, Comment, MarkdownNote); belongs to User |
| BoardShareLink | Public share token (hashed) and permissions | belongs to Board; has many ShareLinkAccesses |
| ShareLinkAccess | A recorded visit to a share link | belongs to BoardShareLink |
| ActivityLog | Audit event | belongs to User; polymorphic `subject` |
| Tag | User-level tag | belongs to User; many-to-many Boards, Columns |
| PersonalAccessToken | Sanctum API token | belongs to User |

## 6. Function Reference

### 6.1 Fortify Actions (`app/Actions/Fortify`)

- `CreateNewUser::create(array $input): User`
- `PasswordValidationRules::passwordRules(): array`
- `ResetUserPassword::reset(User $user, array $input): void`
- `UpdateUserPassword::update(User $user, array $input): void`
- `UpdateUserProfileInformation::update(User $user, array $input): void`
- `UpdateUserProfileInformation::updateVerifiedUser(User $user, array $input): void`

### 6.2 Console Commands (`app/Console/Commands`)

- `CreateUser::handle(): int`
- `CreateUser::askValid(string $label, Closure $makeValidator): string` (private)
- `SendCardDueReminders::handle(): int`
- `TestMail::handle(): int`
- `TestMail::buildNotification(string $type, User $user): Notification` (private)
- `TestMail::fakeCards(User $user, int $daysAgo = 0): Collection` (private)

### 6.3 API Controllers (`app/Http/Controllers/Api/Bot`)

- `BoardsController::summary(Request $request): JsonResponse`
- `CardsController::due(Request $request): JsonResponse`
- `CardsController::formatCard(Card $card): array` (private)

### 6.4 Models (`app/Models`)

**ActivityLog:** `user()`, `subject()`, `casts()`

**Attachment:** `attachable()`, `user()`, `isImage()`, `isDocument()`, `isPdf()`, `isLink()`, `temporaryUrl(int $expiryMinutes = 5): string`, `casts()`

**Board:** `booted()` (auto-generates UUID), `getRouteKeyName()`, `user()`, `columns()`, `tags()`, `labels()`, `shareLinks()`, `casts()`

**BoardShareLink:** `board()`, `accesses()`, `isRevoked()`, `isActive()`, `generateToken(): array{token, hash}`, `findByToken(string $token): ?self`, `casts()`

**Card:** `column()`, `scopeDueToday(Builder $query, User $user)`, `scopeOverdue(Builder $query, User $user)`, `labels()`, `checklists()`, `comments()`, `attachments()`, `markdownNotes()`, `casts()`; constant `COLORS`

**Checklist:** `card()`, `items()`, `attachments()`

**ChecklistItem:** `checklist()`, `casts()`

**Column:** `board()`, `cards()`, `tags()`, `casts()`

**Comment:** `card()`, `user()`, `attachments()`

**Label:** `board()`, `cards()`

**MarkdownNote:** `card()`, `user()`, `attachments()`

**ShareLinkAccess:** `shareLink()`, `casts()`

**Tag:** `user()`, `boards()`, `columns()`

**User:** `boards()`, `tags()`, `casts()`, `wantsNotification(string $key): bool`

### 6.5 Notifications (`app/Notifications`)

Each notification implements `via(object $notifiable): array` and `toMail(object $notifiable): MailMessage`.

- `Auth/NewIpLoginNotification`
- `Auth/PasswordChangedNotification`
- `Auth/RecoveryCodeUsedNotification`
- `Auth/SuspiciousLoginNotification` (constructor takes failure count)
- `Auth/WelcomeNotification`
- `Board/ShareLinkCreatedNotification` (constructor takes board, raw token, and comment/attachment permissions)
- `Board/ShareLinkRevokedNotification` (constructor takes board)
- `Card/CardDueNotification` (constructor takes cards and type: due today / overdue)

### 6.6 Observers, Services, and Providers

- `BoardObserver`: `created()`, `updated()`, `deleted()`, which log board events
- `CardObserver`: `created()`, `updated()` (logs `card.moved` when the column changes, and `card.updated` when other fields change; position-only changes aren't logged), `deleted()`
- `ActivityLogger::log(string $event, ?Model $subject = null, ?array $properties = null, ?int $userId = null): void`
- `ActivityLogger::hashIp(?string $ip): ?string`
- `ActivityLogger::diff(array $dirty, array $original): array`
- `AppServiceProvider::boot()`: registers observers, the `viewer` rate limiter, auth event listeners for logging and security notifications
- `FortifyServiceProvider::boot()`: Fortify actions, auth views, `login` and `two-factor` rate limiters
- `VoltServiceProvider::boot()`: mounts Volt component paths

### 6.7 Livewire Volt Components (`resources/views/livewire`)

**boards/index:** `boards()`, `create()`, `delete(int $boardId)`

**boards/show:** `mount(Board $board)`, `createColumn()`, `deleteColumn(int $columnId)`, `createCard(int $columnId)`, `deleteCard(int $cardId)`, `startEditCard(int $cardId)`, `saveCard()`, `updateColumnOrder(array $orderedIds)`, `updateCardOrder(int $columnId, array $orderedIds)`, `moveCard(int $cardId, int $toColumnId, int $position)`, `createLabel()`, `deleteLabel(int $labelId)`, `toggleCardLabel(int $cardId, int $labelId)`

**boards/share-links:** `mount(Board $board)`, `shareLinks()`, `generate()`, `revoke(int $linkId)`

**cards/detail:**

- Dates: `mount(Card $card)`, `saveDates()`
- Checklists: `checklists()`, `createChecklist()`, `deleteChecklist(int $checklistId)`, `createItem(int $checklistId)`, `toggleItem(int $itemId)`, `deleteItem(int $itemId)`
- Comments: `comments()`, `addComment()`, `deleteComment(int $commentId)`
- Card attachments: `attachments()`, `uploadFile()`, `addLink()`, `deleteAttachment(int $attachmentId)`
- Item attachments (checklist/comment/note): `openAttachmentForm(string $type, int $id)`, `closeAttachmentForm()`, `uploadItemFile()`, `addItemLink()`, `deleteItemAttachment(int $attachmentId)`
- Private helpers: `resolveAttachTarget(string $type, int $id)`, `fileUploadRules(): array`, `resolveAttachmentType(string $mimeType): string`
- Notes: `markdownNotes()`, `saveNote()`, `editNote(int $noteId)`, `deleteNote(int $noteId)`

**search/global:** `results()`

## 7. Known Gaps

- **Notification preferences:** `due_today` / `overdue` preferences exist on the user, but there's no screen to change them.
- **Tags:** the Tag model and its pivot tables exist, but tags aren't used anywhere in the interface.
- **Bot tokens:** there's no screen or command for issuing bot API tokens, so they have to be created manually (e.g. `$user->createToken(...)`).
- **Bot API rate limiting:** the bot API endpoints have no dedicated rate limiter.
- **Shared viewer attachments:** the public viewer only shows card-level attachments, not those on checklists, comments, or notes.
- **Placeholder component:** `livewire/boards/create-board-form.blade.php` is a placeholder and isn't used.
- **Shared attachment partials:** `cards/partials/attachment-row` and `attachment-form` are used in four places in `cards/detail`. Any markup change has to work in all four.
