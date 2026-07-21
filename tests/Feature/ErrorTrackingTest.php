<?php

use Illuminate\Contracts\Debug\ExceptionHandler;

it('resolves the Sentry DSN from env, defaulting to unset', function () {
    // A blank env value ('') is intentionally not null -- unlike this app's
    // own config/backup.php, Sentry's SDK (Options::normalizeDsnOption())
    // explicitly treats '' the same as null/disabled, so no `?: null`
    // coercion is needed here the way it was for BACKUP_ARCHIVE_PASSWORD.
    expect(config('sentry.dsn'))->toBeEmpty();
});

it('does not send Sentry health-check transactions for /up', function () {
    expect(config('sentry.ignore_transactions'))->toContain('/up');
});

it('reports an exception through the handler without throwing, even with no DSN configured', function () {
    $handler = app(ExceptionHandler::class);

    try {
        throw new RuntimeException('Error tracking smoke test');
    } catch (Throwable $e) {
        $handler->report($e);
    }

    // No DSN configured means Sentry\Laravel\Integration::handles() is wired
    // (bootstrap/app.php) but has nothing to send to -- getting here at all
    // (not throwing during ->report()) is what proves that wiring is inert
    // and safe by default, matching this app's placeholder-credential
    // convention for every other third-party integration.
    expect(true)->toBeTrue();
});
