# GraceSoft Skylight

GraceSoft Skylight is a Laravel 13 task and board management application.
It includes user authentication, board and card workflows, public share links,
and notification tooling for security and reminders.

## Tech Stack

- PHP 8.3+
- Laravel 13
- Laravel Fortify (authentication + 2FA)
- Livewire 4 + Volt
- Tailwind CSS 4 + Vite 8
- Pest 4 for testing
- MySQL (default)

## Core Features

- Auth flows: registration, login, password reset, profile update, password update, 2FA
- Personal boards with columns, cards, checklists, comments, labels, and attachments
- Public read-only board share links (`/view/{token}`)
- Activity and access logging for shared links
- Notification email templates and test command
- Due/overdue card reminder command with daily scheduler entry

## Quick Start

### 1) Install dependencies

```bash
composer install
npm install
```

### 2) Environment setup

```bash
# macOS / Linux
cp .env.example .env

# Windows (PowerShell)
Copy-Item .env.example .env

php artisan key:generate
```

Configure database and mail values in `.env`.

### 3) Database setup

```bash
php artisan migrate
```

### 4) Build frontend assets

```bash
npm run build
```

### 5) Run the app locally

```bash
composer run dev
```

This starts:

- Laravel HTTP server
- Queue listener
- Vite dev server

## One-Command Setup

You can also use the project bootstrap script:

```bash
composer run setup
```

This installs dependencies, prepares `.env`, generates `APP_KEY`, runs migrations,
and builds assets.

## Custom Artisan Commands

### Create a user

```bash
php artisan app:create-user
```

Options:

```bash
php artisan app:create-user --name="Jane Doe" --email="jane@example.com" --password="secret"
```

### Send test notification mail

```bash
php artisan app:test-mail
```

Options:

```bash
php artisan app:test-mail --type=welcome --to=jane@example.com
```

### Send due reminder emails

```bash
php artisan app:send-card-due-reminders
```

Scheduled daily at `08:00` in `routes/console.php`.

## Testing and Quality

Run tests:

```bash
php artisan test --compact
```

Run formatter:

```bash
vendor/bin/pint --dirty --format agent
```

## Queue and Scheduler

- Local queue processing is started by `composer run dev`
- For cron-based scheduling in production, run Laravel scheduler every minute:

```bash
php artisan schedule:run
```

## Troubleshooting

- If frontend changes are not visible, run `npm run dev` (or `npm run build`)
- If mail is not arriving, verify `MAIL_*` values in `.env`
- If queue jobs are not processed, ensure a queue worker is running

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
