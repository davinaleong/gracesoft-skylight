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
