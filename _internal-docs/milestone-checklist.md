# GraceSoft Skylight — Milestone Checklist

A running checklist tracking implementation progress per the defined build order.

---

## Milestone 1 — Auth Scaffold (DONE)

- [x] Install Fortify, Livewire, Volt, BaconQrCode
- [x] Publish and configure Fortify
- [x] Add 2FA columns to users migration
- [x] Configure TOTP (QR code) 2FA feature with recovery codes
- [x] Create auth views (login, register, 2FA challenge, forgot/reset password, confirm password)
- [x] Profile page with 2FA enable/disable UI (QR code + recovery codes)
- [x] Protect routes behind auth middleware
- [x] Write feature tests for auth flows (12 tests passing)

## Milestone 2 — Boards -> Columns -> Cards (DONE)

- [x] Board model, migration, factory, seeder, CRUD
- [x] Column model, migration, factory, seeder, CRUD
- [x] Card model, migration, factory, seeder, CRUD
- [x] Drag-and-drop reordering via SortableJS + Livewire

## Milestone 3 — Tags and Labels (DONE)

- [x] Board tags (board_tags pivot)
- [x] Column tags (column_tags pivot)
- [x] Card labels (card_labels pivot)
- [x] Label manager UI on board view (create, delete, color picker)
- [x] Label badges on cards with hover-to-toggle
- [x] 8 tags/labels feature tests passing

## Milestone 4 — Checklists and Dates (DONE)

- [x] Checklist model + checklist_items (with progress tracking)
- [x] Start / end dates on cards (with overdue highlighting)
- [x] Card detail slide-over panel (cards.detail Volt component)
- [x] 7 checklists/dates feature tests passing

## Milestone 5 — Comments (DONE)

- [x] Comment model, migration, factory
- [x] Comments CRUD in card detail slide-over (post, delete own)
- [x] Auth-scoped delete (cannot delete other user's comments)
- [x] 4 comments feature tests passing

## Milestone 6 — Attachment Manager (DONE)

- [x] images (disk-agnostic upload, temporary URL delivery, file deletion)
- [x] Links (URL + optional label)
- [x] Markdown notes (name, content, create/edit/delete)
- [x] Embeds deferred (allow-list sanitization risk)
- [x] 8 attachment manager feature tests passing

## Milestone 7 — Activity Log (DONE)

- [x] ActivityLog model (polymorphic subject, no updated_at, hashed IP)
- [x] ActivityLogger service (log(), hashIp(), diff())
- [x] BoardObserver: board.created, board.updated (field diffs), board.deleted
- [x] CardObserver: card.created, card.moved, card.updated (field diffs), card.deleted
- [x] Login event listeners: login.success, login.failed, 2fa.challenged, 2fa.enabled, 2fa.failed
- [x] 10 activity log feature tests passing

## Milestone 8 — Board Share Links (DONE)

- [x] board_share_links table + share_link_accesses table
- [x] 32-byte random base62 token, stored SHA-256 hashed (never raw)
- [x] BoardShareLink::generateToken() + findByToken() helpers
- [x] Read-only viewer route (rate-limited 30/min, X-Robots-Tag: noindex)
- [x] Coarse toggles: can_see_comments, can_see_attachments
- [x] Revoke / regenerate link UI (boards.share-links Volt component)
- [x] Share link lifecycle events logged to activity_logs
- [x] 9 share links feature tests passing

## Milestone 9 — Polish (DONE)

- [x] Global search (boards + cards, debounced, scoped to user, 2+ char min)
- [x] Search dropdown in app nav with keyboard dismiss (Escape)
- [x] Dark mode (Tailwind dark: classes throughout all views)
- [x] 4 search feature tests passing
