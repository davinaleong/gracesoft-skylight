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

## Milestone 3 — Tags and Labels

- [ ] Board tags (board_tags pivot)
- [ ] Column tags (column_tags pivot)
- [ ] Card labels (card_labels)

## Milestone 4 — Checklists and Dates

- [ ] Checklist model + checklist_items
- [ ] Start / end dates on cards

## Milestone 5 — Comments

- [ ] Comment model, migration, CRUD
- [ ] Comments displayed on card detail view

## Milestone 6 — Attachment Manager

- [ ] Images (S3-compatible upload, signed URL delivery)
- [ ] Links
- [ ] Markdown notes (name, content, date created)
- [ ] Embeds (last — allow-list of trusted sources required)

## Milestone 7 — Activity Log

- [ ] Model observers wired to activity_logs table
- [ ] Discrete events: create / move / update (with field diffs) / delete
- [ ] Auth events: login.success/failed, 2fa.verified/failed
- [ ] IP hashing (never raw)

## Milestone 8 — Board Share Links

- [ ] board_share_links table + share_link_accesses table
- [ ] 32-byte random base62 token, stored hashed
- [ ] Read-only viewer routes (rate-limited, X-Robots-Tag: noindex)
- [ ] Coarse toggles: can_see_comments, can_see_attachments
- [ ] Revoke / regenerate link UI

## Milestone 9 — Polish

- [ ] Search / filter cards and boards
- [ ] Keyboard shortcuts
- [ ] Dark mode

