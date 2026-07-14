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
