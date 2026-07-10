# GraceSoft Skylight — Tech Stack

A privacy-first, security-first personal kanban tool with client-facing read-only "viewer" links.

## Backend

| Layer | Choice | Notes |
|---|---|---|
| Framework | **Laravel** (latest LTS) | Consistent with Beacon, Capture, Board — shared mental model for migrations, policies, queues |
| Auth | **Laravel Fortify** | Ships with email-based 2FA and TOTP (QR code) 2FA out of the box; no hand-rolled OTP crypto |
| Frontend interactivity | **Livewire + Volt** | Reactive drag-and-drop without a separate SPA/API layer — less surface area to secure |
| Drag-and-drop | **SortableJS** (via Alpine/Livewire binding) | Lightweight, no heavy JS framework needed |
| Database | **PostgreSQL** | Already used on Beacon; strong fit for JSONB columns (tags, activity metadata) |
| Queue/jobs | **Laravel Queues** (database or Redis driver) | For signed URL generation, activity log writes, email sending |
| File storage | **S3-compatible object storage** | Same pattern as Desk; short-lived signed URLs for attachment access |

## Frontend

| Layer | Choice | Notes |
|---|---|---|
| Templating | **Blade + Livewire components** | Server-rendered, minimal client JS |
| Styling | **Tailwind CSS** | Fast iteration, consistent with your other projects |
| Optional fallback | **Inertia + Svelte** | If you'd rather decouple frontend/backend fully — keep in back pocket, not needed for v1 |

## Security

| Feature | Approach |
|---|---|
| 2FA | Email OTP + TOTP QR code (Fortify) with recovery codes |
| Share link tokens | 32-byte random, base62-encoded, **hashed** before storage (same pattern as password reset tokens) |
| Attachment access | Short-lived signed S3 URLs (~5 min expiry), never permanent public links |
| Public viewer routes | Rate-limited (~30 req/min/IP), `X-Robots-Tag: noindex` header |
| IP logging | Hashed, not raw — privacy-preserving even in your own audit log |

## Data Model (v1 scope)

```
users
boards
board_tags
columns
column_tags
cards
card_labels
checklists
checklist_items
comments
attachments
markdown_notes
activity_logs

board_user            -- pivot, reserved for future multi-user/enterprise
board_share_links      -- per-board, read-only, revocable client links
share_link_accesses    -- lightweight access log for share links
```

## Deferred / Future (Enterprise path)

- Multi-user collaboration via `board_user` pivot (roles: owner / editor / viewer)
- Per-scope share links (column- or card-level, not just whole-board)
- Real-time collaborative editing (would require a different activity-log granularity — field-level diffs/CRDT-style)

## Build Order (MVP → full)

1. Auth scaffold — Fortify, email + TOTP 2FA, recovery codes
2. Boards → Columns → Cards CRUD + drag-and-drop reordering
3. Tags on boards/columns, labels on cards
4. Checklists + start/end dates
5. Comments
6. Attachment manager — images + links + markdown notes first; embeds last (sanitization risk)
7. Activity log — wired via model observers/events, discrete meaningful actions
8. Board share links (viewer feature)
9. Polish — search/filter, keyboard shortcuts, dark mode