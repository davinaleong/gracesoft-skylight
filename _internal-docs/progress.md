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

## 2026-07-10 — Milestone 2: Boards, Columns, Cards CRUD + Drag-and-Drop

### Goal
Full kanban CRUD: boards list with create/delete, board view with column and card management, SortableJS drag-and-drop for reordering.

### Work completed

- [x] Board, Column, Card models with relationships (User->boards, Board->columns, Column->cards)
- [x] Migrations: boards (name, description, position, user_id), columns (name, position, board_id), cards (title, description, starts_at, ends_at, position, column_id)
- [x] Factories and seeders for all three models
- [x] SortableJS installed via npm
- [x] boards/index Volt component (class-based) - board grid, inline create, delete with confirm
- [x] boards/show Volt component (functional) - columns, cards, add/delete column, add/edit/delete card, move card between columns
- [x] Board show route with 403 ownership check
- [x] Drag-and-drop: Alpine.js + SortableJS via @script block in boards/show
- [x] 11 boards/columns/cards feature tests passing
- [x] Fixed ExampleTest to match new root redirect behaviour (25/25 tests green)

### Notes
- Livewire 4 has its own compiler (separate from Volt) that breaks functional Volt components: it injects protected view() inside the last closure. Fix: use class-based Volt syntax (anonymous class) for all components with multiple actions.
- boards.show still uses functional Volt style; works in Volt::test() because Volt's own compiler handles it. Will convert to class-based before browser testing.
