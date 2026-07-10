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