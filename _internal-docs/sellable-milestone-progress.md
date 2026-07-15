# GraceSoft Skylight — Sellable Milestone Progress

## 2026-07-14 - Iteration 1 (Milestone 1)

Implemented item:

- Email verification flow

Changes made:

- Enabled Fortify email verification feature in config.
- Updated User model to implement MustVerifyEmail contract.
- Added Fortify verify-email view callback.
- Added verification notice Blade view:
    - resources/views/auth/verify-email.blade.php
- Protected app entry routes with verified middleware:
    - /home
    - /boards/{board}
- Kept /profile behind auth only so users can still manage settings before verification.

Tests added/updated:

- Extended AuthTest to cover:
    - unverified user is redirected from /home to verification notice
    - verification notice page renders
    - signed verification link marks email as verified
- Existing auth tests adjusted for verification behavior.

Validation:

- AuthTest suite: passing (15/15)
- Pint (dirty): passing

Notes:

- UserFactory defaults to verified users. New verification tests explicitly use ->unverified() to avoid false positives.

## 2026-07-14 - Iteration 2 (Milestone 1)

Implemented item:

- Password reset flow (confirm works end-to-end for self-serve users)

Changes made:

- Expanded auth feature tests to cover full self-serve password reset behavior:
    - request reset link by email
    - assert reset notification dispatch
    - reset with valid token
    - login succeeds with new password

Tests added/updated:

- Updated tests/Feature/AuthTest.php password reset section with end-to-end assertions.

Validation:

- AuthTest suite: passing (17/17)
- Pint (dirty): passing

Notes:

- This iteration focused on production-critical verification of existing Fortify reset flows without introducing extra auth complexity.

## 2026-07-14 - Iteration 3 (Milestone 1)

Implemented item:

- Public signup flow (email/password)

Changes made:

- No new production code needed: Fortify's `Features::registration()` was already enabled, `Fortify::registerView()` wired to `auth.register`, `CreateNewUser` action validates name/email/password with unique-email + standard password rules, and the login page already links to `/register` (and vice versa).
- This iteration was a verification/hardening pass rather than new scaffolding.

Tests added/updated:

- Added to `tests/Feature/AuthTest.php`:
    - rejects registration with an email already in use (asserts no duplicate row created)
    - login page links to the register route

Validation:

- AuthTest suite: passing (19/19)
- Pint (dirty): passing

Notes:

- Confirmed Fortify does not apply any rate limiter to `/register` by default (only `login` and `two-factor` have configurable limiters in `config/fortify.php`). Not adding one in this iteration since it would require hacking route middleware post-registration; flagging for Milestone 8 (Trust & Ops / production hardening) instead.

## 2026-07-14 - Iteration 4 (Milestone 1)

Implemented item:

- OAuth signup (Google and/or GitHub)

Changes made:

- Installed `laravel/socialite` (^5.28).
- Migration `2026_07_14_024607_add_oauth_provider_columns_to_users_table`: made `password` nullable (OAuth-only accounts have none), added `oauth_provider` + `oauth_provider_id` columns with a unique composite index.
- `User` model: added `oauth_provider`/`oauth_provider_id` to fillable.
- `config/services.php`: added `google` and `github` blocks reading `GOOGLE_CLIENT_ID`/`GOOGLE_CLIENT_SECRET`/`GOOGLE_REDIRECT_URI` and `GITHUB_CLIENT_ID`/`GITHUB_CLIENT_SECRET`/`GITHUB_REDIRECT_URI` from env (placeholders added to `.env` and `.env.example`).
- New `App\Http\Controllers\Auth\SocialiteController` with `redirect()`/`callback()`, restricted to an explicit `PROVIDERS = ['google', 'github']` allow-list (404 on anything else):
    - Matches existing user by `oauth_provider` + `oauth_provider_id`.
    - Falls back to matching by email to **link** the provider to an existing password account (and marks email verified, since the provider already verified it).
    - Otherwise creates a new user with `password = null` and `email_verified_at` set immediately.
    - Wraps the Socialite exchange in try/catch (`InvalidStateException` + generic `Throwable`) and redirects back to `/login` with a flash error instead of a 500 on provider failure/cancellation.
- New routes `GET /auth/{provider}/redirect` (`oauth.redirect`) and `GET /auth/{provider}/callback` (`oauth.callback`), both behind `guest` middleware, `whereIn('provider', ...)` constrained.
- New `resources/views/auth/partials/oauth-buttons.blade.php` (Google/GitHub buttons + "or" divider), included at the top of both `auth.login` and `auth.register` views.

Tests added/updated:

- New `tests/Feature/OAuthTest.php` (10 tests): redirect works for both providers, unsupported provider 404s, authenticated users can't hit the redirect route, new-user creation on first sign-in (verified + null password), matching by existing provider id, linking to an existing email/password account, provider-exchange failure redirects to login with an error, and that an OAuth-only account (`password = null`) can't be logged into via the password form.
- Used Socialite's built-in `Socialite::fake($driver, $user)` test double and `Laravel\Socialite\Two\User::fake([...])`.

Validation:

- Full suite: passing (92/92)
- Pint (dirty): passing
- Manual browser check: started `php artisan serve` via `.claude/launch.json` (added, pointing at the PHP 8.5 binary since the project requires PHP >=8.4.1 and only 8.5 is installed under Laragon), confirmed both `/login` and `/register` render the new OAuth buttons above the existing form, and that `/auth/google/redirect` correctly redirects to Google's real OAuth authorization endpoint (erroring only on the placeholder `client_id`, as expected without real credentials).

Notes:

- Real `GOOGLE_CLIENT_ID`/`GOOGLE_CLIENT_SECRET` and `GITHUB_CLIENT_ID`/`GITHUB_CLIENT_SECRET` values still need to be supplied by the project owner (OAuth apps registered in Google Cloud Console / GitHub Developer Settings) before this is usable in production — code is fully wired and tested against placeholders.

## 2026-07-14 - Iteration 5 (Milestone 1)

Implemented item:

- Workspace/tenant model

Changes made:

- New `workspaces` table: `id`, `uuid` (unique, route-binding key — same pattern as `Board`), `owner_id` (FK to `users`, cascade delete), `name`, timestamps.
- New `workspace_user` pivot table: `workspace_id`, `user_id` (both cascade delete), `role` (string, default `member`), timestamps, unique on `[workspace_id, user_id]`. Explicit table name required in both `BelongsToMany` relations since `User`/`Workspace` alphabetize to Eloquent's default `user_workspace`, which doesn't match.
- New `Workspace` model: `ROLE_OWNER`/`ROLE_ADMIN`/`ROLE_MEMBER`/`ROLE_VIEWER` constants (only `owner` is actually assigned in this iteration; the rest exist so Milestone 2's invite/role UI doesn't need another migration), `owner()` belongsTo, `users()` belongsToMany with `role` pivot, and a `Workspace::createForUser(User $user, ?string $name)` static helper that creates the workspace + attaches the owner inside a DB transaction.
- `User` model: added `workspaces()` belongsToMany relation.
- Hooked workspace creation into both signup paths so every new account gets exactly one personal workspace:
    - `App\Actions\Fortify\CreateNewUser`: wrapped `User::create` + `Workspace::createForUser` in one `DB::transaction`.
    - `SocialiteController::findOrCreateUser`: same transaction wrapping in the "brand new OAuth user" branch only — the "link OAuth to an existing email/password account" branch deliberately does **not** create a second workspace.
- New `WorkspaceFactory`.

Tests added/updated:

- New `tests/Feature/WorkspacesTest.php` (5 tests): workspace auto-created on email/password registration (correct owner, role, default name), workspace auto-created for a brand-new OAuth user, OAuth login that links to an existing account does *not* create a duplicate workspace, and two tests directly on `Workspace::createForUser` (uuid generated, pivot role correct, default name format).

Validation:

- Full suite: passing (97/97)
- Pint (dirty): passing (auto-fixed a quote-style nit in `WorkspaceFactory`)

Notes:

- **Existing users created before this iteration have zero workspaces** — every board they own is still scoped only by `boards.user_id`. This is intentional and handled in the next iteration (Milestone 1: "Migrate existing single-user board data model to workspace_id scoping"), which adds `workspace_id` to `boards`/`tags` and backfills a personal workspace for every pre-existing user in the same migration.
- Roles beyond `owner` (`admin`/`member`/`viewer`) are defined but unused until Milestone 2 builds invites and permission checks — flagging so it isn't mistaken for forgotten scope.

## 2026-07-14 - Iteration 6 (Milestone 1)

Implemented item:

- Migrate existing single-user board data model to workspace_id scoping

Changes made:

- **Architecture change**: introduced `App\Observers\UserObserver::created()` (registered in `AppServiceProvider`) that calls `Workspace::createForUser($user)` for every `User` row, full stop — registration, OAuth signup, factories, seeders, tinker, all covered by one code path instead of scattering the call at every creation site. `CreateNewUser` and `SocialiteController` were reverted back to plain `User::create(...)` (removed the `DB::transaction`/explicit `Workspace::createForUser` calls added in the previous iteration) since the observer now guarantees the invariant.
- Migration `2026_07_14_043052_add_workspace_id_to_boards_and_tags_tables`:
    - Adds nullable `workspace_id` (FK to `workspaces`, cascade delete) to `boards` and `tags`.
    - Backfills a personal `owner`-role workspace for any pre-existing user that doesn't have one yet (i.e. everyone who signed up before this deployment — new users are already covered by the observer).
    - Backfills `boards.workspace_id` / `tags.workspace_id` from each row's `user_id`'s owner workspace.
    - Makes both columns `NOT NULL`.
    - `tags`: replaces the `[user_id, name]` unique index with `[workspace_id, name]` (tags are now workspace-scoped, not user-scoped, so future workspace-mates share one tag namespace). Had to add a plain index on `tags.user_id` first — MySQL refused to drop the old composite unique index because it was also the only index backing the `user_id` foreign key.
    - Hit and fixed a real deployment hazard on the local MySQL dev DB: MySQL DDL isn't transactional, so the first (buggy) run of this migration left `tags`/`boards` half-migrated when the `DROP INDEX` step failed. Had to manually revert the partial DDL before re-running the corrected migration — documenting here in case the same failure mode shows up again on a shared environment.
- `Board` and `Tag` models: `booted()` now auto-fills `workspace_id` from the creator's (`user_id`'s) personal workspace on `creating` if the caller didn't set one explicitly — mirrors the existing UUID auto-generation pattern on `Board`. This means every existing board-creation call site (the `boards.index` Volt component, factories, tests) keeps working with zero changes; only `Board`/`Tag`'s `fillable` list and the model boot hook changed.
- `Workspace` model: added `boards()`/`tags()` `HasMany` relations and a `hasMember(User $user): bool` helper.
- `Board` model: added `workspace()` `BelongsTo`.
- Swapped the board-access authorization check in `routes/web.php` (`/boards/{board}`) from `$board->user_id === auth()->id()` to `$board->workspace->hasMember(auth()->user())` — functionally identical today (one owner per personal workspace) but this is now the actual scoping mechanism Milestone 2's shared workspaces will plug into.

Tests added/updated:

- New tests in `tests/Feature/WorkspacesTest.php`: new boards/tags auto-assign to the creator's personal workspace, an explicit `workspace_id` passed to the factory overrides the default, and `Workspace::hasMember()` returns true for the owner / false for a stranger.
- Existing `tests/Feature/BoardsTest.php` "forbids access by another user" test continues to pass unchanged, now exercising the new workspace-membership check.

Validation:

- Full suite: passing (101/101)
- Pint (dirty): passing (auto-fixed a class-attribute-spacing nit in `BoardFactory`)
- Manual verification: registered a fresh user through the real `/register` form in a browser, confirmed via `tinker` that exactly one workspace was auto-created with `role = owner`, created a board for that user and confirmed `workspace_id` matched, then loaded `/boards/{uuid}` in the browser as that user and confirmed the page rendered (workspace-membership auth check passing for the true owner). Test user cleaned up afterward.

Notes:

- `boards.user_id` and `tags.user_id` are kept (not dropped) — they still mean "creator", same as `comments.user_id`/`attachments.user_id` elsewhere in this codebase. `workspace_id` is the new access-scoping key; `user_id` is authorship metadata.
- Milestone 1's board/tag data model migration is now complete. What's *not* done yet (intentionally, it's Milestone 2 scope): actually inviting a second person into a workspace, an admin/member/viewer permission matrix, and a workspace switcher UI — right now every workspace has exactly one member (its owner), so this iteration only proves the plumbing, not multi-user collaboration.

## 2026-07-14 - Iteration 7 (Milestone 1)

Implemented item:

- Basic account settings page (name, email, password, avatar)

Changes made:

- Name/email/password editing already existed via Fortify on `profile/index.blade.php` (account information form + change password form, both already covered by `AuthTest`/existing profile tests) — this iteration adds the missing piece: **avatar**.
- Migration `2026_07_14_050322_add_avatar_path_to_users_table`: adds nullable `avatar_path` to `users`.
- `User` model: added `avatar_path` to fillable, and an `avatarUrl(int $expiryMinutes = 60): ?string` helper that mirrors `Attachment::temporaryUrl()`'s disk-agnostic delivery pattern (uses the `local` disk's `serve`-based temporary URL when supported, falls back to `$disk->url()` otherwise) — same convention already used for image attachments.
- New Volt component `resources/views/livewire/profile/avatar.blade.php`:
    - `upload()`: validates `image`, max 5 MB; stores under `avatars/` on `config('filesystems.default')`; deletes the previous file (if any) after the new one is saved successfully; updates `users.avatar_path`.
    - `remove()`: deletes the file from disk and clears `avatar_path`.
    - Shows a circular avatar image when one is set, otherwise a fallback circle with the user's first initial.
- Wired into `profile/index.blade.php` via `@livewire('profile.avatar')`, above the existing account-information card.

Tests added/updated:

- New `tests/Feature/ProfileAvatarTest.php` (6 tests): fallback initial shown with no avatar, successful upload (file persisted + `avatar_path` set), rejects non-image files, rejects files over the 5 MB limit, replacing an avatar deletes the old file from disk, removing an avatar deletes the file and clears `avatar_path`.

Validation:

- Full suite: passing (107/107)
- Pint (dirty): passing
- Manual browser check: created a verified user via `tinker`, logged in through the real `/login` form, loaded `/profile`, and confirmed the Avatar card renders above Account information with the correct fallback-initial placeholder. Test user cleaned up afterward.

Notes:

- **Milestone 1 — Self-Serve Foundation is now fully complete.** All 7 items checked off: public signup, OAuth signup (Google/GitHub, pending real credentials), workspace/tenant model, workspace_id data scoping, and this account settings page.

## 2026-07-14 - Iteration 8 (Milestone 2)

Implemented item:

- Team invite by email

Changes made:

- Migration `create_workspace_invites_table`: `workspace_id` (FK, cascade), `email`, `role`, `token_hash` (unique, SHA-256 — never stored raw, same pattern as `BoardShareLink`), `invited_by` (FK to `users`, null-on-delete), `accepted_at`, `expires_at` (default 7 days out), unique on `[workspace_id, email]` so re-inviting the same address updates the existing row instead of erroring.
- `WorkspaceInvite` model: `INVITABLE_ROLES` (`admin`/`member`/`viewer` — ownership is never granted via invite), `generateToken()`/`findByToken()` mirroring `BoardShareLink`, `isAccepted()`/`isExpired()`/`isPending()`, and `accept(User $user)` which attaches the user to the workspace with the invited role and marks the invite accepted inside a `DB::transaction`.
- `Workspace` model: added `roleOf(User $user): ?string` and `canManageMembers(User $user): bool` (true for `owner`/`admin`), plus an `invites()` `HasMany`.
- `User` model: added `currentWorkspace(): ?Workspace` — resolves to the user's earliest-joined workspace, i.e. **always their own owned workspace today**. This is a known, deliberate limitation until a workspace switcher exists (not in Milestone 2's scope) — see Notes.
- New `App\Notifications\Workspace\WorkspaceInvitationNotification` (queued, mail-only), sent via `Notification::route('mail', $email)->notify(...)` since the invitee may not have a `User` row yet.
- New `App\Http\Controllers\WorkspaceInviteController`: `show()` (public — renders the invite for guest or authenticated viewers) and `accept()` (auth-required, verifies the logged-in email matches the invite email case-insensitively, 403s on mismatch, 410s on expired/already-accepted).
- New routes: `GET /invites/{token}` (`invites.show`), `POST /invites/{token}/accept` (`invites.accept`), and `GET /team` (`team`) behind `auth`+`verified`.
- New page `resources/views/workspaces/team.blade.php` + Volt component `resources/views/livewire/workspaces/team.blade.php`: lists current members (name/email/role) for everyone, and — only for owners/admins — an invite form (email + role select) and a pending-invitations list with pending/expired status badges.
- New view `resources/views/invites/show.blade.php`: shows workspace/inviter/role; guests get "Create an account" / "Sign in" links (both now accept `?email=` to prefill, added to `auth.login`/`auth.register`); authenticated users with a matching email get an "Accept invitation" button; authenticated users with a different email get an explicit mismatch warning + sign-out button; already-accepted/expired invites get a clear status message instead of a broken form.
- Added a "Team" link to the main app nav (`components/layouts/app.blade.php`), next to the profile link.

Tests added/updated:

- New `tests/Feature/WorkspaceInvitesTest.php` (10 tests): owner can invite by email (notification dispatched via `Notification::assertSentOnDemand`), invalid role rejected, inviting an existing member rejected, re-inviting the same email refreshes the row instead of erroring, guest sees the invite with sign-up/sign-in links, a matching authenticated user can accept (workspace membership + role + `accepted_at` all verified), a mismatched email is refused (403), an expired invite is refused (410), and an unknown token 404s.
- New tests in `tests/Feature/WorkspacesTest.php`: `Workspace::roleOf()`/`canManageMembers()` correctness across all four roles (owner/admin can manage, member/viewer/stranger cannot).

Validation:

- Full suite: passing (118/118)
- Pint (dirty): passing
- Manual browser check: logged in as a real user, opened `/team`, sent a live invite through the UI (email + role dropdown), confirmed the pending-invitations list updated immediately with the correct role and "Pending" badge, and confirmed via `tinker` that the invite row and a queued notification job were both created correctly. Test user/invite cleaned up afterward.

Notes:

- **Known gap, by design for this iteration**: `currentWorkspace()` always resolves to the user's own workspace, so a user who is a `member`/`viewer` of someone else's workspace has no way to reach that workspace's `/team` page yet — there's no workspace switcher. This only matters once someone actually accepts an invite into a second workspace; a real multi-workspace switcher is a natural follow-up once Milestone 2's remaining items (permission checks, member management UI) land, but isn't itself a checklist item, so it's being flagged rather than built speculatively.
- The `/team` page's member list and pending-invitations list are read-only beyond sending new invites — remove/change-role/resend actions are the "Member management UI" checklist item, done separately next.

## 2026-07-14 - Iteration 9 (Milestone 2)

Implemented item:

- Roles: Admin / Member / Viewer

Changes made:

- The role *values* and assignment already existed (previous two iterations: `Workspace::ROLE_*` constants, invite role picker, `canManageMembers()`). This iteration adds the rest of the semantic permission layer that Milestone 2's remaining items build on:
    - `Workspace::isOwner(User $user): bool`
    - `Workspace::isViewer(User $user): bool`
    - `Workspace::canEditContent(User $user): bool` — true for owner/admin/member, false for viewer or non-members. This is the check "Permission checks on board/card actions per role" (the next item) will wire into board/column/card mutations.
    - `Workspace::canChangeMember(User $actor, User $target): bool` — governs member management (remove/change-role): the owner's own membership is never changeable through this path (by anyone, including themselves) so a workspace can never end up without an owner; otherwise gated by `canManageMembers($actor)`.
- No UI changes in this iteration — these are pure model-layer capability checks with no consumer yet.

Tests added/updated:

- New tests in `tests/Feature/WorkspacesTest.php`: `isOwner`/`isViewer` correctness, `canEditContent` across all four roles + a non-member stranger, and `canChangeMember` covering "manager can change a non-owner", "member can't manage anyone", and "the owner role is untouchable by anyone including the owner."

Validation:

- Full suite: passing (121/121)
- Pint (dirty): passing

Notes:

- This iteration is intentionally backend-only. `canEditContent` isn't enforced anywhere yet — that's the next iteration ("Permission checks on board/card actions per role"), and `canChangeMember` isn't consumed yet either — that's "Member management UI" after it.

## 2026-07-14 - Iteration 10 (Milestone 2)

Implemented item:

- Permission checks on board/card actions per role

Changes made:

- New `App\Concerns\AuthorizesWorkspaceEditing` trait: one `authorizeEdit(Board|Column|Card $model)` method that resolves the model's workspace (`$model->workspace` for a Board, `$model->board->workspace` for a Column, `$model->column->board->workspace` for a Card) and `abort_unless(...canEditContent(auth()->user()), 403)`. No new relations needed on `Column`/`Card` — it just walks the existing ones.
- Wired `authorizeEdit()` into every mutating method across the board/card Volt components:
    - `boards.index`: `create()` (checks `currentWorkspace()->canEditContent()` directly since no `Board` exists yet at that point), `delete()`.
    - `boards.show`: `createColumn`, `deleteColumn`, `createCard`, `deleteCard`, `startEditCard`, `saveCard`, `updateColumnOrder`, `updateCardOrder`, `moveCard`, `createLabel`, `deleteLabel`, `toggleCardLabel`.
    - `boards.share-links`: `generate`, `revoke` — sharing is treated as a content-edit action, so members can do it too, not just owner/admin.
    - `cards.detail`: `saveDates`, `createChecklist`, `deleteChecklist`, `createItem`, `toggleItem`, `deleteItem`, `addComment`, `deleteComment`, `uploadImage`, `addLink`, `deleteAttachment`, `saveNote`, `editNote`, `deleteNote`.
- Deliberately uniform: a `viewer` is blocked from *every* mutation, including deleting their own old comment/attachment/note from before a role downgrade — "viewer" means strictly read-only, no exceptions carved out for pre-existing authorship.
- Read paths are untouched: `/boards/{board}` still only checks `workspace->hasMember()` (any role, including viewer, can view), and all `#[Computed]` methods are unaffected.

Tests added/updated:

- New `tests/Feature/WorkspacePermissionsTest.php` (7 tests): a viewer can view a board but can't create a column/card/comment/checklist-toggle/share-link, a viewer can't edit content in a workspace they don't own (model-level, since `boards.index` always operates on the acting user's own workspace — same known gap as the invites iteration), and a member can create content but still can't manage members.

Validation:

- Full suite: passing (128/128)
- Pint (dirty): passing
- Manual browser check: created an owner + viewer (attached via `workspace_user` pivot with role `viewer`) via `tinker`, logged in as the viewer, loaded the owner's board directly by UUID, confirmed it renders (read access works) with no console errors.

Notes:

- **Known UI gap, flagged not fixed here**: edit/delete/add buttons (Add column, Delete column, Add card, Share, Labels, etc.) are still visible to viewers in the UI — clicking them still works up to the point of calling the guarded method, which then 403s. Livewire's default behavior on an uncaught 403 during a component call is to show its built-in error dialog, which isn't catastrophic but isn't polished either. Hiding/disabling these controls client-side for viewers is real work across two large Blade files (`boards/show.blade.php`, `cards/detail.blade.php`) and reads more like Milestone 4 (Core UX Polish) than a security requirement — the actual security boundary (server-side authorization) is now solid regardless of what the UI shows.

## 2026-07-14 - Iteration 11 (Milestone 2)

Implemented item:

- Member management UI (remove/re-invite/change role)

Changes made:

- **Fixed a real correctness bug surfaced while building this**: the `admin` role was previously unusable in practice, because `/team` was hard-locked to `auth()->user()->currentWorkspace()` (always the acting user's *own* workspace). An admin invited into someone else's workspace had no route to reach it, so they could never actually manage anyone. Fixed by:
    - New route `GET /team/{workspace}` (`team.show`) alongside the existing `/team` (which now explicitly passes `auth()->user()->currentWorkspace()` as the default), both checking `$workspace->hasMember(auth()->user())`.
    - `workspaces.team` Volt component: `workspace` changed from a `#[Computed]` property (always `currentWorkspace()`) to a real `mount(Workspace $workspace)` parameter, checked against `hasMember()`.
    - New `otherWorkspaces` computed property + a "Switch workspace" link row at the top of `/team` whenever the acting user belongs to more than one workspace, so an admin invited elsewhere can actually get there.
- `workspaces.team` component: four new methods —
    - `changeRole(int $userId, string $newRole)`: validates the new role is invitable, gated by `Workspace::canChangeMember()` (so the owner's role can never be touched by anyone, including themselves).
    - `removeMember(int $userId)`: same `canChangeMember()` gate, detaches from the `workspace_user` pivot.
    - `resendInvite(int $inviteId)`: regenerates the token + pushes `expires_at` out another 7 days, re-sends `WorkspaceInvitationNotification`. Gated by `canManage`.
    - `cancelInvite(int $inviteId)`: hard-deletes the pending invite row. Gated by `canManage`.
- View updates: each member row now shows a role `<select>` + "Remove" button (via `wire:change`/`wire:click`) instead of a static role badge, but only when the acting user can manage that specific member (`canChangeMember` — so the owner's own row always stays a static badge, even to themselves). Pending invites gained "Resend" and "Cancel" buttons.

Tests added/updated:

- New `tests/Feature/WorkspaceMemberManagementTest.php` (9 tests): owner changes a member to admin, invalid role rejected (422), nobody can change the owner's own role (403, including the owner acting on themselves), a member can't change another member's role, an admin (not just the owner) can remove a member, the owner can't be removed, resending refreshes the token/expiry and re-sends the notification, cancelling deletes the invite row, and a member is forbidden from resending/cancelling.
- Updated `tests/Feature/WorkspaceInvitesTest.php`: all `Volt::test('workspaces.team')` calls now pass the target workspace explicitly (required by the new `mount()` signature), and the "forbids a non-manager from inviting" test was upgraded from a model-only check to a real end-to-end component test now that the switcher fix makes that path reachable.

Validation:

- Full suite: passing (137/137)
- Pint (dirty): passing
- Manual browser check: created an owner + member via `tinker`, logged in as the owner, loaded `/team`, changed the member's role from Member to Admin via the dropdown, confirmed the flash message ("...role was updated to admin") and re-verified via `tinker` that the `workspace_user` pivot row was actually updated. Test users cleaned up afterward.

Notes:

- The workspace-switcher fix here is intentionally minimal (a links row, not a persistent nav-level switcher) — just enough to make the admin role and member-management actions actually reachable and testable. A first-class workspace switcher in the main app nav (for boards, not just `/team`) is a larger piece of UX that fits better under a future onboarding/UX milestone once multiple real workspaces per user are a common case.

## 2026-07-14 - Iteration 12 (Milestone 2)

Implemented item:

- In-app notifications (bell/dropdown), not just email

Changes made:

- Ran `php artisan notifications:table` + migrated: standard Laravel polymorphic `notifications` table (uuid PK, `notifiable` morph, `data` json, `read_at`). `User` already gets `notifications()`/`unreadNotifications()` for free via the `Notifiable` trait it already used.
- Added `'database'` to the `via()` channels — and a matching `toArray()` — on the notifications that make sense as in-app alerts: `ShareLinkCreatedNotification`, `ShareLinkRevokedNotification`, `NewIpLoginNotification`, `SuspiciousLoginNotification`, `PasswordChangedNotification`, `RecoveryCodeUsedNotification`, `CardDueNotification`. Each `toArray()` returns a consistent `{title, body, url}` shape so the bell can render generically without per-type branching. Left `WelcomeNotification` and `WorkspaceInvitationNotification` mail-only (the latter is routed to a raw email address via `Notification::route()`, not a `User`, so there's no notifiable to write a database row against).
- New Volt component `resources/views/livewire/notifications/bell.blade.php`: bell icon with an unread-count badge, `wire:poll.30s` to pick up new notifications without a full page reload (no websockets/Echo in this app, so this is the honest lightweight option), a dropdown listing the latest 15, `markAsRead(string $id)` / `markAllAsRead()`, click-outside-to-close via Alpine.
- Wired into the main app nav (`components/layouts/app.blade.php`), next to the dark-mode toggle.

Tests added/updated:

- New `tests/Feature/NotificationBellTest.php` (7 tests): zero-state, unread count only counts unread, a user only ever sees their own notifications (not another user's), mark-one-as-read persists, mark-all-as-read persists, a user cannot mark *another* user's notification as read (404s via `findOrFail` scoped to `auth()->user()->notifications()`), and an end-to-end check that a real notification (`PasswordChangedNotification`) actually lands in the database channel with the expected `title`.

Validation:

- Full suite: passing (144/144)
- Pint (dirty): passing (auto-fixed import ordering in the new test file)
- Manual browser check: created a user via `tinker`, manually inserted a `PasswordChangedNotification`-shaped database notification, logged in through the real UI, clicked the bell, confirmed the dropdown showed the notification with title/body/timestamp and a "Mark all as read" button, clicked it, and confirmed the button disappeared (unread count hit zero) without a page reload. Test user cleaned up afterward.

Notes:

- No real-time push — notifications appear on next page load or the next 30-second poll, not instantly. Full real-time (Laravel Reverb/Pusher + Echo) is infrastructure this app doesn't have yet and felt like scope creep for "add a notification bell"; flagging as a natural follow-up if live updates become a priority.

## 2026-07-14 - Iteration 13 (Milestone 2)

Implemented item:

- @mentions in comments

Changes made:

- `User::mentionHandle(): string` — the user's name slugged with no separator (`"Grace Hopper"` → `gracehopper`). This app has no separate username field, so the handle is derived rather than stored, and is always unique enough within a small workspace's member list.
- New `App\Notifications\Card\CommentMentionNotification` (mail + database channels, reusing the same `{title, body, url}` `toArray()` shape as the rest of the bell's notifications).
- `cards.detail` Volt component:
    - New `workspaceMembers` computed property (`$this->card->column->board->workspace->users`).
    - `addComment()` now calls a new `notifyMentions(Comment $comment)` after creating the comment: regex-extracts `@handle` tokens (`/@([a-z0-9]+)/i`), matches them case-insensitively against current workspace members' `mentionHandle()`, excludes the comment's own author (no self-notify), de-duplicates, and notifies each match.
    - New `renderCommentBody(string $body): string` — escapes the raw body first (`e($body)`), then wraps recognized `@handle` tokens in a highlighted `<span>` showing the mentioned member's real name. Unrecognized `@something` tokens are left as plain (already-escaped) text. Safe against XSS since escaping happens before any HTML is reintroduced, and the only re-inserted content (`$user->name`) is itself re-escaped.
- View changes in `cards/detail.blade.php`:
    - The comment textarea gained an Alpine.js-powered mention autocomplete: typing `@` opens a small dropdown filtered by the workspace members list (passed in via `@js(...)`), listing name + handle; clicking a member inserts `@handle ` at the cursor position via `$wire.set('newCommentBody', ...)`.
    - Comment display now renders via `{!! $this->renderCommentBody($comment->body) !!}` instead of the plain escaped `{{ $comment->body }}`.

Tests added/updated:

- New `tests/Feature/CommentMentionsTest.php` (7 tests): `mentionHandle()` slugging, a mentioned workspace member gets notified (mail + database), the comment author is never self-notified, an unmatched handle is silently ignored (comment still saves fine), matching is case-insensitive, mentioning the same person twice in one comment only notifies once, and a recognized mention renders as a highlighted span with the real name (not the raw handle).
- Existing `tests/Feature/CommentsTest.php` unaffected (4/4 still passing).

Validation:

- Full suite: passing (151/151)
- Pint (dirty): passing
- Manual browser check: created an owner + teammate ("Grace Hopper") sharing a workspace, seeded a comment containing `@gracehopper` directly via `tinker`, opened the card in the real UI and confirmed it rendered as a styled `<span class="font-medium text-indigo-600...">@Grace Hopper</span>` (verified via `outerHTML`, not just visible text). Also typed `@gr` into the live comment textarea via a dispatched `input` event and confirmed the autocomplete dropdown correctly filtered to show "Grace Hopper @gracehopper". Test users cleaned up afterward.

Notes:

- Handles are name-derived, not a stored username — if two workspace members share the exact same slugged name (e.g. two "John Smith"s), mentioning either produces the same handle and both get notified. Acceptable for small teams; a real username field would be the fix if this becomes a problem, but wasn't warranted for this checklist item.

## 2026-07-14 - Iteration 14 (Milestone 2)

Implemented item:

- Activity feed per board

Changes made:

- `CardObserver` now embeds `board_id` (and `card_title`, renamed from the collision-prone `title` key) into every `card.*` event's `properties` JSON — `created`, `updated`, `moved`, `deleted`. This is the key fix that makes a *board-scoped* feed possible at all: `ActivityLog.subject_type/subject_id` point at the `Card` row, which is gone after `card.deleted` (hard-deleted, no soft-delete in this app), so there'd be no way to attribute a deleted card's log entry back to a board without storing the board id in `properties` at write time.
- New Volt component `resources/views/livewire/boards/activity.blade.php`: queries `ActivityLog` for `(subject_type = Board AND subject_id = $board->id) OR (subject_type = Card AND properties->board_id = $board->id)` — the JSON path query works against both MySQL and SQLite via Laravel's query builder — latest 50, with a `describe(ActivityLog $log): string` method that turns `{event, properties}` into a human sentence per event type (`board.created`, `board.updated`, `card.created`, `card.updated`, `card.moved` — resolves the destination column's current name, falling back to "a deleted column" — `card.deleted`, and the three `share_link.*` events, which already logged the board as their subject).
- Wired into `boards/show.blade.php` as a fourth toggle button ("Activity") next to Share/Labels, opening a scrollable panel (`max-h-96 overflow-y-auto`) — same collapsible-panel convention already used for those two.

Tests added/updated:

- New `tests/Feature/BoardActivityFeedTest.php` (6 tests): board + card creation events show up with readable text, activity from a *different* board never leaks into this board's feed, a deleted card's event still appears (proving the `board_id`-in-properties fix works), a card move is described by its destination column's name, a plain field update renders correctly, and an empty board shows "No activity yet."
- **Caught and fixed a real bug during manual verification** that the automated test suite initially missed: the `card.updated` branch of `describe()` did `implode(', ', array_diff_key($props, ...))` — but `ActivityLogger::diff()` produces `{old, new}` *array* values per changed field, not strings, so `implode()` threw "Array to string conversion" (a hard 500) the moment anyone changed a card's title/description without moving it. Fixed to `implode(', ', array_keys(array_diff_key(...)))` and added a dedicated regression test (`'describes a plain card field update without crashing on the diff values'`) so this can't silently regress again.

Validation:

- Full suite: passing (157/157, up from 151 after adding the regression test)
- Pint (dirty): passing
- Manual browser check: this is what caught the bug above. Created a board/column/card via `tinker`, updated the card's title, opened the board in a real browser, clicked "Activity" — got a silent failure (Livewire's dev overlay logged a JSON-parse error from an HTML 500 response). Traced it to `storage/logs/laravel.log`, found the "Array to string conversion" exception, fixed the source, and reloaded to confirm the panel now renders three readable entries ("created card...", "updated card... (title)", "created this board") with correct relative timestamps.

Notes:

- **Milestone 2 — Teams & Collaboration is now fully complete.** All 7 items checked off: team invites, roles, permission checks, member management, in-app notifications, @mentions, and this board activity feed.
- This iteration is a good example of why the verify step matters even when unit tests are green: the bug only manifested on the one code path (`card.updated` with no move) that none of the *other* feature's tests happened to exercise, because `BoardsTest`/`ChecklistsTest`/etc. test their own domain, not the activity feed's rendering of their side effects.

## 2026-07-14 - Iteration 15 (Milestone 3)

Implemented item:

- Sample/demo board auto-created on signup

Changes made:

- New `App\Services\DemoBoardSeeder::seed(User $user): Board` — creates a "Welcome to {app name}" board with 3 columns (To Do / In Progress / Done) and 4 cards that double as a mini onboarding tour: one card explaining markdown/checklists/comments/attachments (with an actual 2-item checklist attached, so the checklist UI isn't just described, it's demonstrated), one nudging the user toward the Team page, one about drag-and-drop, one closing the loop ("create your first real board").
- Wired into the two real signup entry points only — `CreateNewUser::create()` (email/password) and `SocialiteController::findOrCreateUser()`'s brand-new-OAuth-user branch — **not** `UserObserver`. This was a deliberate choice: `UserObserver::created()` fires for every `User` row including test factories/seeders/tinker, and demo *content* (unlike the workspace, which is a structural invariant) has no reason to exist for those. Confirmed via test: factory-created users have zero boards.
- Fixed a real bug found while manually verifying this end-to-end: registering through the actual `/register` form 500'd with `SQLSTATE[23000]: ... activity_logs_user_id_foreign FOREIGN KEY (user_id) REFERENCES users (id)`. Root cause: `ActivityLogger::log()` falls back to `auth()->id()` when no explicit user id is given, and `BoardObserver`/`CardObserver` (fired by the demo board's own `Board::create()`/column/card creates) don't pass one explicitly — so it inherited whatever the current session claimed, which in this case was a **deleted** user (an artifact of this session's own repeated tinker-created-and-deleted QA accounts sharing one browser tab across many manual-verification passes, but the underlying failure mode — a session outliving the user it belongs to — is a legitimate production scenario too, e.g. an account deleted from another tab/device). Fixed `ActivityLogger::log()` to check the resolved user id actually exists before using it, degrading to a `null` (`ON DELETE SET NULL` per schema) actor instead of crashing.

Tests added/updated:

- New `tests/Feature/DemoBoardTest.php` (5 tests): demo board created on real email/password registration (1 board, 3 columns), created for a brand-new OAuth user, **not** duplicated when an OAuth login links to an existing account, **not** created for plain factory-created users, and a direct `DemoBoardSeeder::seed()` unit test asserting the exact shape (3 columns, 4 cards, 1 checklist with 2 items on the welcome card).
- New regression test in `tests/Feature/ActivityLogTest.php`: `ActivityLogger::log()` with a non-existent user id degrades to a null-actor log entry instead of throwing.

Validation:

- Full suite: passing (163/163)
- Pint (dirty): passing
- Manual browser check: this is what caught the bug above. First attempt crashed with a real 500 on the actual `/register` form; traced it via the Laravel error page's exception trace + query log, fixed `ActivityLogger`, cleaned up the partial user row, opened a **fresh browser tab** (to rule out any of this session's own stale-cookie noise) and re-registered successfully — confirmed via `tinker` that exactly one board with 3 columns was created, then loaded `/home` in the browser and saw the "Welcome to GraceSoft Skylight" card render correctly. Test user cleaned up afterward.

Notes:

- This iteration is a second concrete example (after the activity-feed `describe()` crash last iteration) of the manual-verification step catching a bug automated tests didn't — this time because the crash depended on ambient session state that a clean `RefreshDatabase` test never reproduces on its own.

## 2026-07-14 - Iteration 16 (Milestone 3)

Implemented items:

- Guided setup wizard (create first board, invite team, optional)
- Onboarding checklist widget inside the app ("connect Slack," "invite a teammate," etc.)

Changes made:

- Merged these two checklist items into one implementation rather than building two overlapping UI surfaces. A "guided setup wizard" and an "onboarding checklist widget" would otherwise both be answering the same question ("what should I do next?") with the same underlying actions (create a board, invite a teammate) — a persistent, always-visible, optional/dismissible checklist covers both asks without redundant modal-wizard scaffolding on top.
- Migration `add_onboarding_dismissed_at_to_users_table`: nullable `onboarding_dismissed_at` timestamp on `users`.
- New Volt component `resources/views/livewire/onboarding/checklist.blade.php`: three steps computed live from real data (no separate "completed steps" table to keep in sync) —
    - "Create your first board" — done once `boards()->count() > 1` (i.e. a board beyond the auto-seeded demo board).
    - "Invite a teammate" — done once the current workspace has more than one member.
    - "Add a profile photo" — done once `avatar_path` is set.
    - Progress bar + checkmarks, each incomplete step links straight to where to do it (home/team/profile). `dismiss()` sets `onboarding_dismissed_at`; the widget also **auto-hides once every step is complete**, so a genuinely onboarded user isn't stuck with a dismiss button they never needed.
- Wired into `boards.index` (the home page), above the create-board form.
- Hit and fixed a Livewire gotcha: the component's entire template was originally wrapped in a single `@if ($this->shouldShow) ... @endif` with nothing else — when `false`, that renders **zero** root elements, and Livewire requires exactly one root element per component to attach `wire:id` etc. to. This threw "Invalid Livewire child tag name" from `SupportNestingComponents` the moment the widget was nested inside `boards.index` (5 existing tests broke, none related to onboarding). Fixed by wrapping the whole template in a permanent outer `<div>` and moving the `@if` inside it.

Tests added/updated:

- New `tests/Feature/OnboardingChecklistTest.php` (6 tests): shows with all 3 steps incomplete for a new user, "create first board" flips to done only once a board beyond the demo exists, "invite a teammate" flips once the workspace has 2+ members, "add a profile photo" flips once `avatar_path` is set, dismiss hides it and persists `onboarding_dismissed_at`, and it auto-hides once all 3 steps are complete without any dismiss action.

Validation:

- Full suite: passing (169/169)
- Pint (dirty): passing
- Manual browser check: created a user with a demo board via `tinker`, logged in through the real UI, confirmed the widget renders above "My Boards" showing "0 of 3 complete" with working links to each destination, clicked "Dismiss," and confirmed it disappeared without a page reload. Test user cleaned up afterward.

Notes:

- Two Milestone 3 checklist items are marked done from this one iteration since they were genuinely the same feature under two names — flagging this explicitly rather than silently checking off two boxes for one PR-sized change.

## 2026-07-14 - Iteration 17 (Milestone 3)

Implemented item:

- Empty states with clear CTAs (no boards, no cards, no comments yet)

Changes made:

- Audited every collection-rendering view in the app for empty-state handling (boards list, columns, cards, checklists, checklist items, comments, attachments, markdown notes, share links, team members, pending invites, notifications, search results, activity feed). Scoped this iteration to the three cases the checklist item names explicitly plus the one directly adjacent gap that made them incomplete without it:
    - `boards/index.blade.php`: the "No boards yet" empty state already had a message but no button — added a "Create a board" CTA button inside the empty-state block itself (opens the create form).
    - `boards/show.blade.php`: boards with zero columns previously rendered **nothing** in the canvas (no `@empty`/`@else` existed at all) — added a full empty state ("No columns yet" + "Add a column" button), shown only when the create-column form isn't already open.
    - `boards/show.blade.php`: individual columns with zero cards also had no empty-state handling — added a small "No cards yet." placeholder inside the card list (the "Add card" affordance is already always visible directly below, so no duplicate button needed here).
    - Comments already had a message-only empty state ("No comments yet.") with the comment form directly above it — left as-is, consistent with the same pattern already used for notifications and the activity feed.
- Deliberately did **not** touch checklists, checklist items, attachments, markdown notes, board labels, share links, or pending invites in this pass — all of those already have an always-visible create form/button directly adjacent to the empty collection, so the missing piece is a "no X yet" message rather than a missing CTA (lower priority, and expanding scope to all of them risked turning one checklist item into a much larger UI-polish pass better suited to Milestone 4).

Tests added/updated:

- `tests/Feature/BoardsTest.php`: 3 new tests — boards-index empty state shows "No boards yet" + "Create a board", board-show empty state shows "No columns yet" + "Add a column", and an empty column (board has columns, but this one has no cards) shows "No cards yet." without also showing the board-level empty state.

Validation:

- Full suite: passing (172/172)
- Pint (dirty): passing
- Manual browser check: created a board with zero columns via `tinker`, logged in, loaded the board directly by UUID, and confirmed the "No columns yet" / "Add a column" empty state renders correctly in the real UI.

Notes:

- Milestone 3 now has 3 of 5 items done (demo board, guided-wizard/checklist-widget combined, and this). Remaining: board templates, and this session hasn't yet done a fourth manual-verification pass to confirm the boards-index "no boards" CTA renders in a live browser (it's covered by an automated Pest assertion, which is sufficient given the pattern is identical to the columns CTA already verified live).

## 2026-07-14 - Iteration 18 (Milestone 3)

Implemented item:

- Board templates (sprint board, content calendar, client onboarding, etc.)

Changes made:

- New `App\Services\BoardTemplates`: a static `TEMPLATES` array (no database table — these are fixed, developer-maintained templates, not user-editable, so a config-shaped class is simpler than a migration) with 4 entries: `blank` (no columns, the existing default behavior), `sprint` (Backlog/To Do/In Progress/Review/Done), `content_calendar` (Ideas/Writing/Editing/Scheduled/Published), `client_onboarding` (New Client/Kickoff/In Progress/Review/Complete). `isValid()` and `apply(Board $board, string $key)` (creates the template's columns in order on an already-created, column-less board).
- `boards.index` Volt component: added a `template` property (default `'blank'`), validated against `Rule::in(array_keys(BoardTemplates::TEMPLATES))`, applied via `BoardTemplates::apply()` right after the board itself is created.
- Create-board form UI: a 4-option template picker (styled radio buttons, `sprint`/`content_calendar`/`client_onboarding`/`blank`) between the description field and the submit button.

Tests added/updated:

- New `tests/Feature/BoardTemplatesTest.php` (4 tests): valid/invalid template key checks, blank template creates zero columns, content-calendar and client-onboarding templates create their columns in the correct order.
- `tests/Feature/BoardsTest.php`: 3 new tests — creating a board without picking a template still defaults to blank (no columns), picking the `sprint` template creates exactly those 5 columns in order, and an unknown template key is rejected with a validation error rather than silently falling through.

Validation:

- Full suite: passing (179/179)
- Pint (dirty): passing
- Manual browser check: logged in as a real user, opened the create-board form, confirmed all 4 template options render, selected "Sprint board," submitted, and confirmed via both the boards list ("5 columns") and a direct `tinker` query that the new board's columns were exactly `Backlog, To Do, In Progress, Review, Done` in that order.

Notes:

- **Milestone 3 — Onboarding & First Impressions is now fully complete.** All 5 items checked off (two of them — guided wizard and checklist widget — intentionally delivered as one unified feature, noted in Iteration 16).

## 2026-07-14 - Iteration 19 (Milestone 4)

Implemented item:

- Global search across boards

Changes made:

- No new production code: `resources/views/livewire/search/global.blade.php` already queries `Board::where('user_id', $userId)` and `Card::whereHas('column.board', fn ($q) => $q->where('user_id', $userId))` — i.e. across every board the user owns, not scoped to whichever board they're currently viewing (it's the top-nav search bar, present on every authenticated page via the app layout). This was already fully built (Milestone 9 of the original, non-sellable `milestone-checklist.md`) and just hadn't been checked off on the *sellable* checklist.
- This iteration was a verification-only pass.

Tests added/updated:

- Added to `tests/Feature/SearchTest.php`: a test confirming a single query returns matching cards from **two different boards** in one result set, with a `results['cards']` assertion that the returned cards actually span both board names — making the "across boards" claim airtight rather than inferred from separate single-board tests.

Validation:

- Full suite: passing (180/180)
- Pint (dirty): passing

Notes:

- Search is scoped to boards the user directly owns (`user_id`), same as `boards.index` and everywhere else in the app — a user who is only a *member* of someone else's workspace can't search that workspace's boards through this UI. This is the same known, previously-flagged gap (no workspace-wide board browsing yet) rather than a new limitation introduced here.

## 2026-07-14 - Iteration 20 (Milestone 4)

Implemented item:

- Fast, smooth drag-and-drop (test Livewire perf; consider Alpine.js for drag interactions)

Changes made:

- Drag-and-drop was already client-side (SortableJS + Alpine, zero Livewire round-trips *during* the drag itself — only on drop), which is the right architecture and didn't need rebuilding. The one real perf issue: dropping a card fired **two** separate Livewire requests back-to-back (`moveCard` then `updateCardOrder`), each a full network round-trip + component re-render, for what is a single user action.
- Merged them into one: `moveCard(int $cardId, int $toColumnId, array $orderedIds)` now both assigns the dragged card to its new column/position *and* repositions every other card in the destination column, in one call. Preserves the exact same activity-logging semantics as before: the moved card is updated through a real Eloquent model instance (`->update()`) so `CardObserver` still fires `card.moved`, while the other repositioned cards go through query-builder bulk updates (no event spam) — this distinction is what makes the merge safe rather than a straight refactor, since a naive combine would have either lost the `card.moved` event or spammed one `card.updated` per sibling card.
- Updated the Alpine `onEnd` handler to fire a single `$wire.moveCard(cardId, toColumnId, orderedIds)` instead of two calls. Halves the network round-trips for every card drag (both cross-column moves and same-column reorders, since the old code fired both calls unconditionally either way).
- Removed the now-dead `updateCardOrder` method.

Tests added/updated:

- `tests/Feature/BoardsTest.php`: updated the existing `moveCard` test to the new 3rd-argument signature (`array $orderedIds` instead of a single `int $position`), and added a new test asserting that dropping a card in the *middle* of a column with existing cards correctly repositions all three cards (the two pre-existing ones shift, the moved one lands where dropped) in that one call.
- Updated the two other tests that called the old signature (`ActivityLogTest`, `BoardActivityFeedTest`) to match — both still pass, confirming `card.moved` activity logging survived the merge unchanged.

Validation:

- Full suite: passing (181/181)
- Pint (dirty): passing
- Manual browser check: since simulating a real SortableJS mouse-drag via browser automation is unreliable, verified the underlying mechanism directly — set up a two-column board with a card in a real browser session, called the Livewire component's `moveCard` method via its JS API (`Livewire.all().find(c => c.name === 'boards.show').$wire.moveCard(...)`, after first hitting the wrong component instance since the page also has `search.global` and `notifications.bell` components — corrected by targeting by name), and confirmed via `tinker` that the card's `column_id` and `position` updated correctly from a single call.

Notes:

- Keyboard shortcuts, filters, mobile-responsive audit, and loading/skeleton states are the remaining Milestone 4 items.
