# GraceSoft Skylight — Implementation Progress Log

A chronological record of work done per session.

---

## 2026-07-10 — Milestone 1: Auth Scaffold

### Goal
Set up authentication with Fortify: TOTP (QR code) 2FA with recovery codes, plus Blade views for all auth screens.

### Packages installed
- `laravel/fortify` — auth actions, TOTP 2FA, recovery codes
- `livewire/livewire` + `livewire/volt` — reactive single-file components (Milestone 2+)
- `bacon/bacon-qr-code` — QR code image generation for TOTP setup

### Work completed

- [x] Composer installs (laravel/fortify, livewire/livewire, livewire/volt, bacon/bacon-qr-code)
- [x] Fortify publish + config (php artisan fortify:install)
- [x] users migration updated with 2FA columns (merged into base migration)
- [x] FortifyServiceProvider registered; all Fortify features enabled
- [x] Auth actions scaffolded (CreateNewUser, UpdatePassword, ResetUserPassword, UpdateUserProfileInformation)
- [x] Auth views: login, register, forgot-password, reset-password, confirm-password, two-factor-challenge
- [x] Profile page: update name/email, change password, enable/disable TOTP 2FA with QR code + recovery codes
- [x] Layouts: components/layouts/app.blade.php and components/layouts/auth.blade.php
- [x] Routes: / redirects by auth state, /home and /profile protected
- [x] 12 feature tests written and passing

### Notes
- Fortify's separate 2FA columns migration was removed; columns are in the base users migration.
- Auth views use plain Blade (Fortify handles POST via its own routes).
