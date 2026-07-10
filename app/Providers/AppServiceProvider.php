<?php

namespace App\Providers;

use App\Models\ActivityLog;
use App\Models\Board;
use App\Models\Card;
use App\Models\User;
use App\Notifications\Auth\NewIpLoginNotification;
use App\Notifications\Auth\PasswordChangedNotification;
use App\Notifications\Auth\RecoveryCodeUsedNotification;
use App\Notifications\Auth\SuspiciousLoginNotification;
use App\Notifications\Auth\WelcomeNotification;
use App\Observers\BoardObserver;
use App\Observers\CardObserver;
use App\Services\ActivityLogger;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Events\TwoFactorAuthenticationChallenged;
use Laravel\Fortify\Events\TwoFactorAuthenticationEnabled;
use Laravel\Fortify\Events\TwoFactorAuthenticationFailed;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Board::observe(BoardObserver::class);
        Card::observe(CardObserver::class);

        // Rate-limit for public viewer routes: 30 req/min per IP
        RateLimiter::for('viewer', fn () => Limit::perMinute(30)->by(Request::ip()));

        // â”€â”€â”€ Activity logging â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

        Event::listen(Login::class, function (Login $event) {
            ActivityLogger::log('login.success', null, null, $event->user->id);

            // P0 #5: notify on login from a new IP (checked after the log is written)
            if ($event->user instanceof User) {
                $ipHash = ActivityLogger::hashIp(Request::ip());
                $seenBefore = ActivityLog::where('user_id', $event->user->id)
                    ->where('event', 'login.success')
                    ->where('ip_hash', $ipHash)
                    ->count() > 1; // >1 because the log above was just written

                if (! $seenBefore) {
                    $event->user->notify(new NewIpLoginNotification);
                }
            }
        });

        Event::listen(Failed::class, function (Failed $event) {
            ActivityLogger::log('login.failed', null, [
                'email' => $event->credentials['email'] ?? null,
            ], $event->user?->id);

            // P0 #4: notify on 5+ failed logins in the last 10 minutes
            if ($event->user instanceof User) {
                $recentFailures = ActivityLog::where('user_id', $event->user->id)
                    ->where('event', 'login.failed')
                    ->where('created_at', '>=', now()->subMinutes(10))
                    ->count();

                if ($recentFailures >= 5 && $recentFailures % 5 === 0) {
                    $event->user->notify(new SuspiciousLoginNotification($recentFailures));
                }
            }
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

        // â”€â”€â”€ Auth lifecycle notifications â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

        // P1 #6: welcome email on registration
        Event::listen(Registered::class, function (Registered $event) {
            if ($event->user instanceof User) {
                $event->user->notify(new WelcomeNotification);
            }
        });

        // P0 #2: password changed confirmation
        Event::listen(PasswordReset::class, function (PasswordReset $event) {
            if ($event->user instanceof User) {
                $event->user->notify(new PasswordChangedNotification);
            }
        });

        // P0 #3: recovery code used â€” detected when two_factor_recovery_codes changes on the User model
        User::updated(function (User $user) {
            if ($user->wasChanged('two_factor_recovery_codes') && $user->two_factor_recovery_codes !== null) {
                $user->notify(new RecoveryCodeUsedNotification);
            }
        });
    }
}
