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
