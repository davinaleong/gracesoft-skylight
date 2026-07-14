# GraceSoft Skylight — Product Roadmap Checklist

Tracking milestones to turn the personal kanban app into a usable, sellable product.

---

## Milestone 1 — Self-Serve Foundation

_Goal: someone can sign up and use it without you creating their account._

- [x] Public signup flow (email/password)
- [x] OAuth signup (Google and/or GitHub) — code scaffolded end-to-end and tested against placeholder credentials; real client ID/secret from Google Cloud Console & GitHub Developer Settings still needed before production use
- [x] Email verification flow
- [x] Password reset flow (confirm works end-to-end for self-serve users)
- [x] Workspace/tenant model — each account gets an isolated workspace
- [x] Migrate existing single-user board data model to `workspace_id` scoping
- [x] Basic account settings page (name, email, password, avatar)

## Milestone 2 — Teams & Collaboration

_Goal: more than one person can work in a workspace._

- [x] Team invite by email
- [x] Roles: Admin / Member / Viewer
- [x] Permission checks on board/card actions per role
- [x] Member management UI (remove/re-invite/change role)
- [x] @mentions in comments
- [x] In-app notifications (bell/dropdown), not just email
- [x] Activity feed per board

## Milestone 3 — Onboarding & First Impressions

_Goal: first 60 seconds convinces someone to stay._

- [x] Sample/demo board auto-created on signup
- [x] Guided setup wizard (create first board, invite team, optional)
- [ ] Empty states with clear CTAs (no boards, no cards, no comments yet)
- [ ] Board templates (sprint board, content calendar, client onboarding, etc.)
- [x] Onboarding checklist widget inside the app ("connect Slack," "invite a teammate," etc.)

## Milestone 4 — Core UX Polish

_Goal: feels good to use daily, not just functional._

- [ ] Fast, smooth drag-and-drop (test Livewire perf; consider Alpine.js for drag interactions)
- [ ] Keyboard shortcuts (quick-add card, navigate columns, etc.)
- [ ] Global search across boards
- [ ] Filters (label, assignee, due date, status)
- [ ] Mobile-responsive layout audit (test on actual phone viewport)
- [ ] Loading/skeleton states for slow actions

## Milestone 5 — Differentiator: Client Portals

_Goal: polish the public share-link feature into a marketed, named feature._

- [ ] Rename/brand the feature (e.g. "Client Portals")
- [ ] Customizable share-link permissions (comments on/off, attachments on/off — already partial, confirm full coverage)
- [ ] Branded/white-labeled share view (logo, workspace name)
- [ ] Share-link expiration and revocation controls
- [ ] Analytics on share-link views (who accessed, when)

## Milestone 6 — Monetization Infrastructure

_Goal: able to charge money._

- [ ] Stripe (or Paddle) integration
- [ ] Define pricing tiers (Free / Pro / Team) with seat or board limits
- [ ] Subscription management UI (upgrade/downgrade/cancel)
- [ ] Usage limit enforcement (boards, members, storage per tier)
- [ ] Invoice/receipt emails
- [ ] Trial period logic (if applicable)

## Milestone 7 — Integrations & Extensibility

_Goal: reduce churn once teams depend on the tool._

- [ ] Public REST API (authenticated via API tokens)
- [ ] Webhooks (card created/moved/completed events)
- [ ] Slack integration (post updates to a channel)
- [ ] Zapier or Make.com integration (or generic webhook docs for DIY)
- [ ] Data export (CSV/JSON) for boards and cards

## Milestone 8 — Trust & Ops

_Goal: credible enough for a stranger to trust with their data._

- [ ] Move file attachments to S3-compatible storage (not local disk)
- [ ] Backups configured and tested (restore drill, not just backup job)
- [ ] Uptime monitoring + status page
- [ ] Security page (mention 2FA, encryption at rest/in transit)
- [ ] Privacy policy + Terms of Service
- [ ] Production deployment hardened (queue workers, scheduler, error tracking e.g. Sentry)

## Milestone 9 — Go-to-Market Assets

_Goal: get the first real users in the door._

- [ ] Marketing landing page (separate from the app) with clear positioning
- [ ] Screenshots/demo video of core workflow
- [ ] Pricing page
- [ ] Public changelog/roadmap page
- [ ] Launch posts drafted (Show HN, r/selfhosted, Product Hunt)
- [ ] Feedback channel set up (email, Discord, or simple form) for early users

---

## Suggested sequencing

1. Milestones 1–2 (must-have to let anyone use it at all)
2. Milestones 3–4 (make it good, not just functional)
3. Milestone 5 (lean into your actual differentiator before copying competitors)
4. Milestone 9, partially — get 10–20 real users on the free/beta version
5. Milestones 6–8 (billing, integrations, ops) only once real user feedback validates demand
