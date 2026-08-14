# GraceSoft Skylight: Features and Functions Inventory

Last updated: 2026-08-14

## 1. App Overview

GraceSoft Skylight is a Laravel + Livewire Kanban-style board application with:

- Authentication and account security (including 2FA)
- Board, column, and card management
- Card details with checklists, comments, attachments, and markdown notes
- Board sharing through revocable public read-only links
- Global search across boards and cards
- Event/activity logging and security notifications
- Mail notification preview and reminder automation commands

## 2. User-Facing Feature List

### 2.1 Authentication and Account

- User registration
- Login with remember-me option
- Password reset request and password reset flow
- Password confirmation flow for protected actions
- Two-factor challenge using authenticator code
- Two-factor challenge using recovery code
- Profile update (name and email)
- Password update
- Enable 2FA from profile
- Confirm 2FA with TOTP code and setup key
- Regenerate recovery codes
- Disable 2FA

### 2.2 Boards and Kanban

- Home dashboard listing all owned boards
- Create board (name and optional description)
- Delete board
- Open board detail page by board UUID slug
- Create column
- Delete column
- Reorder columns via drag-and-drop
- Create card inside a column
- Delete card
- Inline card edit in board view
- Card color selection
- Reorder cards within a column via drag-and-drop
- Move cards across columns via drag-and-drop
- Card labels shown on board cards

### 2.3 Labels

- Open board label manager
- Create board-level label (name + color)
- Delete label
- Toggle label assignment on cards

### 2.4 Card Detail Modal

- View full card detail in modal
- Save start and due dates with date validation
- Checklist management:
    - Create checklist
    - Delete checklist
    - Create checklist item
    - Toggle checklist item completion
    - Delete checklist item
    - Progress display per checklist
- Comments:
    - Add comment
    - Delete own comment
- Attachments:
    - Upload image or PDF document attachments (restricted by MIME type; other formats rejected)
    - Add URL link attachments
    - Attachments can be associated with a card, a checklist, a comment, or a markdown note (polymorphic), each supporting multiple attachments
    - Image thumbnails and PDF-icon thumbnails shown inline in the attachment list
    - Click thumbnail to open a lightbox preview (image via `<img>`, PDF via inline browser viewer), with an "open in new tab" link
    - Open attachments via short-lived (~5 min) temporary URL
    - Delete own attachments (also removes the underlying file for image/document types)
    - Graceful retry message (instead of a server error) if a file upload does not finish transferring
- Markdown notes:
    - Create note
    - Edit own note
    - Delete own note

### 2.5 Search and Navigation

- Global search in top navigation
- Search boards by name
- Search cards by title
- Dropdown results for boards and cards
- Direct navigation to selected board/card context

### 2.6 Board Sharing and Public Viewer

- Generate secure board share links
- Optional share permissions:
    - Can see comments
    - Can see attachments flag persisted on link
- One-time token display on generation
- Copy share URL to clipboard
- Revoke share links
- Public read-only viewer route with token
- Viewer route rate limit (30 requests/minute per IP)
- Viewer responses include noindex, nofollow directives
- Public viewer shows board columns/cards with card color accents
- Card click opens a read-only detail dialog in shared view
- Shared dialog includes markdown-rendered card body
- Shared dialog includes read-only start/due dates
- Shared dialog includes read-only checklists (accordion)
- Shared dialog includes read-only comments when enabled on share link
- Shared dialog includes read-only attachments when enabled on share link
- Shared dialog includes image lightbox for attachment previews

### 2.7 Security, Logging, and Notifications

- Board and card lifecycle activity logs
- Card move event logs
- Login success/failure logs
- 2FA challenge/enabled/failed logs
- Share-link created/revoked/accessed logs
- IP hash storage for privacy-preserving activity/access logs
- Notify user on first login from new IP
- Notify user on suspicious login failures (every 5 failures in 10-minute window)
- Notify user on password reset
- Notify user on recovery code usage detection
- Notify user on registration welcome
- Notify user when share link is created/revoked
- Due-today and overdue card reminder notifications

### 2.8 Theme and UX

- Light/dark theme toggle in app layout
- Theme preference persisted in local storage
- Kanban drag-and-drop implemented with SortableJS

## 3. HTTP Route Surface

Web routes:

- GET /
    - Redirects to home when authenticated, otherwise login
- GET /home (auth)
    - Home dashboard
- GET /profile (auth)
    - Profile settings and security settings
- GET /boards/{board} (auth, owner-only authorization)
    - Board detail page
- GET /view/{token} (throttle:viewer)
    - Public read-only board viewer from active share token

Fortify-auth routes are enabled for:

- Login, register, logout
- Password reset flows
- Password confirmation
- Two-factor challenge and 2FA management endpoints

## 4. Console Commands and Scheduler

Custom Artisan commands:

- app:create-user
    - Interactive or option-based user creation
    - Options: --name, --email, --password
- app:test-mail
    - Sends selected notification template to a user email
    - Options: --type, --to
- app:send-card-due-reminders
    - Sends due-today and overdue reminder notifications

Scheduler:

- app:send-card-due-reminders runs daily at 08:00

## 5. Data/Domain Capability Map

Primary entities:

- User
- Board
- Column
- Card
- Label
- Checklist
- ChecklistItem
- Comment
- Attachment
- MarkdownNote
- Tag
- BoardShareLink
- ShareLinkAccess
- ActivityLog

Notable domain capabilities:

- Board route key is UUID slug
- Board share link token stored as hash, not plaintext
- Attachments support image, PDF document, and external link types
- Attachment is polymorphic (attachable_type/attachable_id): can belong to Card, Checklist, Comment, or MarkdownNote
- Temporary URLs generated for image/document attachments where supported
- User notification preferences support due_today and overdue toggles

## 6. Full Function Inventory by File

This section lists all currently implemented project functions and methods in app code and Volt component classes.

### 6.1 app/Actions/Fortify/CreateNewUser.php

- create(array $input): User

### 6.2 app/Actions/Fortify/PasswordValidationRules.php

- passwordRules(): array

### 6.3 app/Actions/Fortify/ResetUserPassword.php

- reset(User $user, array $input): void

### 6.4 app/Actions/Fortify/UpdateUserPassword.php

- update(User $user, array $input): void

### 6.5 app/Actions/Fortify/UpdateUserProfileInformation.php

- update(User $user, array $input): void
- updateVerifiedUser(User $user, array $input): void

### 6.6 app/Console/Commands/CreateUser.php

- handle(): int
- askValid(string $label, Closure $makeValidator): string

### 6.7 app/Console/Commands/SendCardDueReminders.php

- handle(): int

### 6.8 app/Console/Commands/TestMail.php

- handle(): int
- buildNotification(string $type, User $user): Notification
- fakeCards(User $user, int $daysAgo = 0): Collection

### 6.9 app/Models/ActivityLog.php

- user(): BelongsTo
- subject(): MorphTo
- casts(): array

### 6.10 app/Models/Attachment.php

- attachable(): MorphTo
- user(): BelongsTo
- isImage(): bool
- isDocument(): bool
- isPdf(): bool
- isLink(): bool
- temporaryUrl(int $expiryMinutes = 5): string
- casts(): array

### 6.11 app/Models/Board.php

- booted(): void
- getRouteKeyName(): string
- user(): BelongsTo
- columns(): HasMany
- tags(): BelongsToMany
- labels(): HasMany
- shareLinks(): HasMany
- casts(): array

### 6.12 app/Models/BoardShareLink.php

- board(): BelongsTo
- accesses(): HasMany
- isRevoked(): bool
- isActive(): bool
- generateToken(): array
- findByToken(string $token): ?self
- casts(): array

### 6.13 app/Models/Card.php

- column(): BelongsTo
- labels(): BelongsToMany
- checklists(): HasMany
- comments(): HasMany
- attachments(): MorphMany
- markdownNotes(): HasMany
- casts(): array

### 6.14 app/Models/Checklist.php

- card(): BelongsTo
- items(): HasMany
- attachments(): MorphMany

### 6.15 app/Models/ChecklistItem.php

- checklist(): BelongsTo
- casts(): array

### 6.16 app/Models/Column.php

- board(): BelongsTo
- cards(): HasMany
- tags(): BelongsToMany
- casts(): array

### 6.17 app/Models/Comment.php

- card(): BelongsTo
- user(): BelongsTo
- attachments(): MorphMany

### 6.18 app/Models/Label.php

- board(): BelongsTo
- cards(): BelongsToMany

### 6.19 app/Models/MarkdownNote.php

- card(): BelongsTo
- user(): BelongsTo
- attachments(): MorphMany

### 6.20 app/Models/ShareLinkAccess.php

- shareLink(): BelongsTo
- casts(): array

### 6.21 app/Models/Tag.php

- user(): BelongsTo
- boards(): BelongsToMany
- columns(): BelongsToMany

### 6.22 app/Models/User.php

- boards(): HasMany
- tags(): HasMany
- casts(): array
- wantsNotification(string $key): bool

### 6.23 app/Notifications/Auth/NewIpLoginNotification.php

- via(object $notifiable): array
- toMail(object $notifiable): MailMessage

### 6.24 app/Notifications/Auth/PasswordChangedNotification.php

- via(object $notifiable): array
- toMail(object $notifiable): MailMessage

### 6.25 app/Notifications/Auth/RecoveryCodeUsedNotification.php

- via(object $notifiable): array
- toMail(object $notifiable): MailMessage

### 6.26 app/Notifications/Auth/SuspiciousLoginNotification.php

- \_\_construct(...)
- via(object $notifiable): array
- toMail(object $notifiable): MailMessage

### 6.27 app/Notifications/Auth/WelcomeNotification.php

- via(object $notifiable): array
- toMail(object $notifiable): MailMessage

### 6.28 app/Notifications/Board/ShareLinkCreatedNotification.php

- \_\_construct(...)
- via(object $notifiable): array
- toMail(object $notifiable): MailMessage

### 6.29 app/Notifications/Board/ShareLinkRevokedNotification.php

- \_\_construct(...)
- via(object $notifiable): array
- toMail(object $notifiable): MailMessage

### 6.30 app/Notifications/Card/CardDueNotification.php

- \_\_construct(...)
- via(object $notifiable): array
- toMail(object $notifiable): MailMessage

### 6.31 app/Observers/BoardObserver.php

- created(Board $board): void
- updated(Board $board): void
- deleted(Board $board): void

### 6.32 app/Observers/CardObserver.php

- created(Card $card): void
- updated(Card $card): void
- deleted(Card $card): void

### 6.33 app/Providers/AppServiceProvider.php

- register(): void
- boot(): void

### 6.34 app/Providers/FortifyServiceProvider.php

- register(): void
- boot(): void

### 6.35 app/Providers/VoltServiceProvider.php

- register(): void
- boot(): void

### 6.36 app/Services/ActivityLogger.php

- log(string $event, ?Model $subject = null, ?array $properties = null, ?int $userId = null): void
- hashIp(?string $ip): ?string
- diff(array $dirty, array $original): array

### 6.37 resources/views/livewire/boards/index.blade.php (Volt component class)

- boards()
- create(): void
- delete(int $boardId): void

### 6.38 resources/views/livewire/boards/show.blade.php (Volt component class)

- mount(Board $board): void
- createColumn(): void
- deleteColumn(int $columnId): void
- createCard(int $columnId): void
- deleteCard(int $cardId): void
- startEditCard(int $cardId): void
- saveCard(): void
- updateColumnOrder(array $orderedIds): void
- updateCardOrder(int $columnId, array $orderedIds): void
- moveCard(int $cardId, int $toColumnId, int $position): void
- createLabel(): void
- deleteLabel(int $labelId): void
- toggleCardLabel(int $cardId, int $labelId): void

### 6.39 resources/views/livewire/boards/share-links.blade.php (Volt component class)

- mount(Board $board): void
- shareLinks()
- generate(): void
- revoke(int $linkId): void

### 6.40 resources/views/livewire/cards/detail.blade.php (Volt component class)

- mount(Card $card): void
- checklists()
- saveDates(): void
- createChecklist(): void
- deleteChecklist(int $checklistId): void
- createItem(int $checklistId): void
- toggleItem(int $itemId): void
- deleteItem(int $itemId): void
- comments()
- addComment(): void
- deleteComment(int $commentId): void
- attachments()
- markdownNotes()
- uploadFile(): void — uploads image or PDF as a card-level attachment; catches FilesystemException from an incomplete temp upload and shows a retryable error instead of a 500
- addLink(): void
- deleteAttachment(int $attachmentId): void
- openAttachmentForm(string $type, int $id): void — opens the item-level attachment form for a checklist/comment/note (only one open at a time)
- closeAttachmentForm(): void
- uploadItemFile(): void — uploads image or PDF as a checklist/comment/note-level attachment for whichever target is currently open; same FilesystemException handling as uploadFile()
- addItemLink(): void — adds a link attachment to the currently open item-level target
- deleteItemAttachment(int $attachmentId): void — scoped to checklist/comment/note attachments belonging to the current card
- resolveAttachTarget(string $type, int $id): Checklist|Comment|MarkdownNote (private) — authorizes the item-level attachment target against the current card
- fileUploadRules(): array (private) — shared validation rules (image or PDF, max 10MB) for both uploadFile() and uploadItemFile()
- resolveAttachmentType($file): string (private) — derives TYPE_IMAGE vs TYPE_DOCUMENT from the uploaded file's actual MIME type
- saveNote(): void
- editNote(int $noteId): void
- deleteNote(int $noteId): void

### 6.41 resources/views/livewire/search/global.blade.php (Volt component class)

- results()

## 7. Known Notes and Gaps

- resources/views/livewire/boards/create-board-form.blade.php currently appears to be a placeholder and not an active feature surface.
- The public share-link viewer (resources/views/viewer/board.blade.php) only reads card-level attachments; it does not yet surface checklist/comment/note-level attachments added via the new polymorphic association (2026-08-14).
- resources/views/livewire/cards/partials/attachment-row.blade.php and attachment-form.blade.php (added 2026-08-14) are shared partials reused across the card, checklist, comment, and note attachment sections in cards/detail.blade.php — update all four call sites if the row/form markup changes.

## 8. Suggested Maintenance Process

When features are added or changed, update this document by:

- Updating section 2 for user-facing capability changes
- Updating section 3 when route surface changes
- Updating section 4 for command/schedule changes
- Updating section 6 whenever new methods are introduced or removed
