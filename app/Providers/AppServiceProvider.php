<?php

namespace App\Providers;

use App\Models\Board;
use App\Models\Card;
use App\Observers\BoardObserver;
use App\Observers\CardObserver;
use App\Services\ActivityLogger;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Events\TwoFactorAuthenticationChallenged;
use Laravel\Fortify\Events\TwoFactorAuthenticationEnabled;
use Laravel\Fortify\Events\TwoFactorAuthenticationFailed;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Board::observe(BoardObserver::class);
        Card::observe(CardObserver::class);

        // Auth activity logging
        Login::class;

        Event::listen(Login::class, function ($event) {
            ActivityLogger::log('login.success', null, null, $event->user->id);
        });

        Event::listen(Failed::class, function ($event) {
            ActivityLogger::log('login.failed', null, [
                'email' => $event->credentials['email'] ?? null,
            ], $event->user?->id);
        });

        Event::listen(TwoFactorAuthenticationChallenged::class, function ($event) {
            ActivityLogger::log('2fa.challenged', null, null, $event->user->id);
        });

        Event::listen(TwoFactorAuthenticationEnabled::class, function ($event) {
            ActivityLogger::log('2fa.enabled', null, null, $event->user->id);
        });

        Event::listen(TwoFactorAuthenticationFailed::class, function ($event) {
            ActivityLogger::log('2fa.failed', null, null, $event->user->id);
        });
    }
}
