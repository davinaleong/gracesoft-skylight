# GraceSoft Skylight — Implementation Progress Log

A chronological record of work done per session.

---

## 2026-07-10 — Milestone 1: Auth Scaffold

### Goal
Set up authentication with Fortify: TOTP (QR code) 2FA with recovery codes, plus Blade views for all auth screens.

### Packages installed
- `laravel/fortify` — auth actions, TOTP 2FA, recovery codes
- `livewire/livewire` + `livewire/volt` — reactive single-file components (Milestone 2+)
- `bacon/bacon-qr-code` — QR code image generation for TOTP setup

### Work completed

- [x] Composer installs (laravel/fortify, livewire/livewire, livewire/volt, bacon/bacon-qr-code)
- [x] Fortify publish + config (php artisan fortify:install)
- [x] users migration updated with 2FA columns (merged into base migration)
- [x] FortifyServiceProvider registered; all Fortify features enabled
- [x] Auth actions scaffolded (CreateNewUser, UpdatePassword, ResetUserPassword, UpdateUserProfileInformation)
- [x] Auth views: login, register, forgot-password, reset-password, confirm-password, two-factor-challenge
- [x] Profile page: update name/email, change password, enable/disable TOTP 2FA with QR code + recovery codes
- [x] Layouts: components/layouts/app.blade.php and components/layouts/auth.blade.php
- [x] Routes: / redirects by auth state, /home and /profile protected
- [x] 12 feature tests written and passing

### Notes
- Fortify's separate 2FA columns migration was removed; columns are in the base users migration.
- Auth views use plain Blade (Fortify handles POST via its own routes).

## 2026-07-10 — Milestone 2: Boards, Columns, Cards CRUD + Drag-and-Drop

### Goal
Full kanban CRUD: boards list with create/delete, board view with column and card management, SortableJS drag-and-drop for reordering.

### Work completed

- [x] Board, Column, Card models with relationships (User->boards, Board->columns, Column->cards)
- [x] Migrations: boards (name, description, position, user_id), columns (name, position, board_id), cards (title, description, starts_at, ends_at, position, column_id)
- [x] Factories and seeders for all three models
- [x] SortableJS installed via npm
- [x] boards/index Volt component (class-based) - board grid, inline create, delete with confirm
- [x] boards/show Volt component (functional) - columns, cards, add/delete column, add/edit/delete card, move card between columns
- [x] Board show route with 403 ownership check
- [x] Drag-and-drop: Alpine.js + SortableJS via @script block in boards/show
- [x] 11 boards/columns/cards feature tests passing
- [x] Fixed ExampleTest to match new root redirect behaviour (25/25 tests green)

### Notes
- Livewire 4 has its own compiler (separate from Volt) that breaks functional Volt components: it injects protected view() inside the last closure. Fix: use class-based Volt syntax (anonymous class) for all components with multiple actions.
- boards.show still uses functional Volt style; works in Volt::test() because Volt's own compiler handles it. Will convert to class-based before browser testing.

## 2026-08-14 — Attachment Uploads: S3 Fix, Polymorphic Associations, Format Restrictions & Viewer

### Goal
Fix a broken "Unable to attach any media" bug end-to-end (composer dependency → S3 config → upload flow), then extend attachments so checklists, comments, and markdown notes can each hold their own attachments (not just cards), restrict uploads to images/PDF, and give attachments a real inline preview instead of a bare filename link.

### Part 1 — Fixing uploads (composer package, CORS, S3 bucket)

- [x] Diagnosed `Class "League\Flysystem\AwsS3V3\PortableVisibilityConverter" not found`: `FILESYSTEM_DISK=s3` was set in `.env`, but `league/flysystem-aws-s3-v3` (and `aws/aws-sdk-php`) were never added as composer dependencies.
- [x] `composer require league/flysystem-aws-s3-v3` — installs the S3 adapter + AWS SDK.
- [x] Diagnosed a follow-up CORS error: Livewire's file uploads PUT directly from the browser to S3 using a pre-signed URL, and the `gracesoft-skylight-dev-*` bucket had **no CORS configuration at all**.
- [x] Applied a CORS policy to the bucket (via the AWS SDK, using project `.env` credentials) allowing `GET/PUT/POST/HEAD` from `http://gracesoft-skylight.test`.
- [x] Fixed a latent test-suite bug exposed by the `FILESYSTEM_DISK=s3` change: tests call `Storage::fake('local')` but read `config('filesystems.default')`, which now resolved to `s3` outside of tests too. Pinned `FILESYSTEM_DISK=local` in `phpunit.xml`.
- [x] **Root-caused a `HeadObject` 404 (`UnableToRetrieveMetadata`) that made every real image upload fail.** Initially misdiagnosed as stale browser state (it wasn't — every real upload attempt failed the same way). The actual bug: `uploadFile()`/`uploadItemFile()` called `$fileUpload->store(...)` *then* read `getSize()`/`getMimeType()`/etc. on the same file object. Since the temp-upload disk and destination disk are both `s3`, `TemporaryUploadedFile::storeAs()` takes Flysystem's same-disk `move()` path (copy + delete-original) — which deletes the `livewire-tmp/...` object those later metadata calls still pointed at. Fixed by capturing `name`/`mimeType`/`size` *before* calling `store()`. Proved the diagnosis by reproducing the exact error against live S3 with the old ordering, then showing the new ordering succeeds every time (see `resolveAttachmentType()` also changed to take a MIME string instead of the file object, so it's not called post-move either).
- [x] Discovered why this went uncaught by tests all session: Livewire's test helpers (`Volt::test(...)->set('fileUpload', UploadedFile::fake())`) bypass the real S3-backed `TemporaryUploadedFile` entirely, so the store-then-read ordering is never exercised in the suite. Also discovered every earlier "attachments are viewable" verification in this session created records by writing directly to S3/the DB, sidestepping the real upload button and never actually exercising the buggy path either — which is why it wasn't caught until the user hit it live.

### Part 2 — Attachments on checklists, comments, and notes (polymorphic)

- [x] Migration `2026_08_14_000000_make_attachments_polymorphic.php`: replaces `attachments.card_id` with `attachable_type`/`attachable_id` (backfills existing rows to `Card::class` before dropping the old column).
- [x] `Attachment::attachable()` (MorphTo) replaces `Attachment::card()` (BelongsTo).
- [x] Added `attachments(): MorphMany` to `Card`, `Checklist`, `Comment`, and `MarkdownNote` — each can now hold multiple attachments.
- [x] `cards/detail.blade.php`: added item-level attachment state (`attachTargetType`/`attachTargetId`, single form open at a time — same UX pattern as the existing "add checklist item" flow) and methods `openAttachmentForm()`, `closeAttachmentForm()`, `uploadItemFile()`, `addItemLink()`, `deleteItemAttachment()`, `resolveAttachTarget()` (authorizes the target belongs to the current card).
- [x] New shared partials `cards/partials/attachment-row.blade.php` and `attachment-form.blade.php`, reused across all four attachment sections (card, checklist, comment, note) to avoid duplicating markup.
- [x] `AttachmentFactory` updated for the new polymorphic columns.
- [x] Tests: rewrote `AttachmentsTest.php` for the new schema, added coverage for checklist/comment/note uploads, multiple attachments per item, delete-with-file-cleanup, and cross-card authorization (attaching to another card's checklist throws `ModelNotFoundException`).

### Part 3 — Viewability investigation, format restriction, thumbnails & lightbox

- [x] Investigated a report that attachments "can be uploaded but not viewable." Generated real signed S3 URLs the same way the UI does and loaded them directly for all four contexts (card/checklist/comment/note) — all worked. No storage-layer bug found; the real issue was UX (a plain filename link with a generic icon gives no visual cue it's an image, and opens silently in a background tab).
- [x] Restricted uploads to images + PDF (previously images-only for files; no document upload existed at all). Added `Attachment::TYPE_DOCUMENT`, `isDocument()`, `isPdf()`. Validation changed from `image` rule to `mimes:jpg,jpeg,png,gif,webp,svg,bmp,pdf`; `type` is derived from the file's actual MIME (not extension or filename).
- [x] Renamed `$imageUpload`→`$fileUpload`, `uploadImage()`→`uploadFile()` (and item-level equivalents) to reflect the broadened scope.
- [x] `attachment-row.blade.php` now renders real `<img>` thumbnails for images and a PDF icon tile for documents, both clickable; links (URLs) are unchanged.
- [x] Added an Alpine.js lightbox (`x-teleport`'d to `<body>` to escape the card modal's stacking context) — image attachments open a larger `<img>`, PDFs open in an `<iframe>` (browser's native PDF viewer), both with an "open in new tab" fallback link.
- [x] Verified live in-browser (seeded a QA user/board/card, uploaded/seeded real image + PDF attachments, clicked both thumbnails, confirmed the lightbox rendered each correctly) — not just unit-tested.
- [x] Added graceful failure handling: `uploadFile()`/`uploadItemFile()` now catch `League\Flysystem\FilesystemException` around validation — if a temp upload never finished transferring to S3, the user gets "This upload did not complete. Please choose the file again." instead of an unhandled 500.
- [x] 83 feature tests passing at end of session.

### Notes
- S3 presigned URLs are signed for a specific HTTP method — a `HEAD` request against a `GET`-signed URL returns 403. Cost me a false-positive bug report during investigation; always re-verify with the same method the browser actually uses.
- For same-disk `TemporaryUploadedFile::store()` calls, Flysystem does a `move()` (copy + delete-original) — any metadata read (`getSize()`, `getMimeType()`) on that file object must happen *before* `store()`, not after. This class of bug won't show up in `Volt::test()` because the test helpers don't exercise the real S3-backed temp file at all.
- **Known gap, not yet fixed:** Livewire's `cleanupOldUploads()` early-returns when `FileUploadConfiguration::isUsingS3()` is true — i.e. automatic cleanup of abandoned `livewire-tmp/` files is silently disabled on S3. Nothing currently expires them; recommend an S3 Lifecycle rule to auto-delete objects under `livewire-tmp/` after ~1 day. Not applied yet — needs sign-off before touching bucket config again.
- The public share-link viewer (`resources/views/viewer/board.blade.php`) still only reads card-level attachments — checklist/comment/note attachments from this session are not yet surfaced there. Tracked in `features-functions.md` §7.
- `.env` contains live AWS credentials and is committed in this working tree — flagged to the user as worth double-checking is git-ignored.
