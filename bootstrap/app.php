<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;
use Sentry\Laravel\Integration;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Off by default (no proxies trusted) so this changes nothing for a
        // direct-to-PHP deployment. Set TRUSTED_PROXIES in production when
        // running behind a reverse proxy/load balancer that terminates TLS
        // (nginx, Caddy, a PaaS) -- otherwise Laravel sees the proxy's plain
        // HTTP connection, not the client's HTTPS one, and generates http://
        // URLs and refuses to mark the session cookie Secure even though the
        // client connection really is HTTPS. '*' trusts any proxy (only
        // appropriate when the app isn't directly reachable except through a
        // platform-managed proxy, e.g. most PaaS setups); otherwise list
        // specific proxy IPs/CIDRs, comma-separated.
        if ($trustedProxies = env('TRUSTED_PROXIES')) {
            $middleware->trustProxies(
                at: $trustedProxies === '*' ? '*' : explode(',', $trustedProxies),
            );
        }

        // Sanctum token abilities: full-access tokens carry '*', bot tokens
        // carry only 'bot:read' (see User::API_TOKEN_TYPES).
        $middleware->alias([
            'abilities' => CheckAbilities::class,
            'ability' => CheckForAnyAbility::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        // No-ops when SENTRY_LARAVEL_DSN is unset (config/sentry.php), so this
        // is safe to leave wired in every environment, not just production.
        Integration::handles($exceptions);
    })->create();
