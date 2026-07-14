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
