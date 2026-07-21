# Launch Post Drafts

Draft copy only — nothing here has been posted anywhere. Publishing to any of
these platforms requires a real account on that platform and is a deliberate,
one-time action for whoever owns this launch, not something to automate.
Replace `[link]` with the real production URL before posting anywhere.

---

## Show HN

**Title:** Show HN: A kanban board with read-only client links, no seats required

**Body:**

I built GraceSoft Skylight because every kanban tool I used for client work
had the same gap: sharing progress with a client meant either giving them a
full account (a seat, an invite, a login they'll forget) or exporting a
screenshot by hand.

It's a fairly standard kanban board — boards, columns, cards, checklists,
comments, drag-and-drop — with one thing built around from the start: a
**Client Portal** link. It's a read-only, revocable, expiring link to a board
that anyone can open with no account. You control whether they can see
comments or attachments, and you can see a view count without tracking who
specifically looked (IPs are hashed, not stored).

Also has: real workspace roles enforced server-side (not just hidden UI),
2FA, a REST API + webhooks + a Slack integration, CSV/JSON export, and
backups that are actually restore-tested, not just a cron job nobody's
verified.

Free plan is 3 boards / 3 teammates, forever, no card required: [link]

Happy to answer questions about any of the technical decisions — it's a
Laravel + Livewire app, self-hostable, MIT-ish licensing TBD.

---

## r/selfhosted

**Title:** Self-hostable kanban board with client-facing share links (Laravel/Livewire)

**Body:**

Sharing this here since a few "kanban but I can self-host it" threads have
come up recently. GraceSoft Skylight is a Laravel + Livewire kanban app —
boards/columns/cards, checklists, comments, team roles, 2FA, the usual — with
a couple of things I haven't seen bundled together elsewhere:

- **Client Portals**: revocable, expiring, read-only share links to a board,
  no account needed on the other end. Built for freelancers/agencies who need
  to show a client progress without onboarding them.
- **REST API + webhooks + Slack integration**, so it plugs into other tools
  instead of being a dead end.
- File storage works against S3-compatible object storage (not just local
  disk), and backups are actually restore-tested — extracted the archive,
  restored into a scratch database, verified the data came back, not just
  "the backup command exited 0."

Stack: Laravel, Livewire/Volt, MySQL, Tailwind. [link to repo/docs if
open-sourcing, otherwise the app URL]

Would genuinely like feedback from this community specifically on the
self-hosting story — what's missing for you to trust running this on your
own box (env var docs, Docker, etc.)?

---

## Product Hunt

**Tagline:** Kanban boards your team runs on — and your clients can actually see

**Description:**

GraceSoft Skylight is a kanban board built for teams that work with outside
clients. Everything you'd expect — fast drag-and-drop, checklists, comments,
@mentions, file attachments — plus **Client Portals**: a branded, read-only
link to any board that anyone can open without an account. Toggle what's
visible, set an expiry, revoke it instantly.

Also ships with real team permissions (Owner/Admin/Member/Viewer, enforced
server-side), two-factor authentication, a REST API + webhooks + Slack
integration for connecting your other tools, and CSV/JSON export whenever you
want your data out.

Free forever for small teams (3 boards, 3 members). Paid plans start at
$12/mo.

**First comment (from the maker):**

Hey PH! Built this after one too many times exporting a board to a
screenshot to send a client an update. Would love feedback on the Client
Portal flow specifically — that's the feature I think is genuinely different
from the kanban tools you already know, and I want to know if it lands.

---

## Notes for whoever posts these

- Show HN and r/selfhosted both reward technical specificity over marketing
  language — the drafts above lean into architecture/self-hosting details on
  purpose. Product Hunt's audience responds better to the outcome (screenshots
  will matter a lot more there than on HN — see the still-open "Screenshots/
  demo video" item in `sellable-milestone-progress.md`).
- Don't post from a workspace/user perspective that only exists in dev/test
  data — use the real production account.
- r/selfhosted specifically expects an honest answer about self-hosting
  friction if asked (Docker setup isn't built yet as of this checklist —
  don't overpromise).
