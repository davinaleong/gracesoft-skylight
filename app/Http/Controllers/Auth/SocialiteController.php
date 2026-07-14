<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\DemoBoardSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;

class SocialiteController extends Controller
{
    /**
     * Providers this application accepts OAuth sign-in from.
     */
    public const PROVIDERS = ['google', 'github'];

    public function redirect(string $provider): RedirectResponse
    {
        abort_unless(in_array($provider, self::PROVIDERS, true), 404);

        return Socialite::driver($provider)->redirect();
    }

    public function callback(string $provider): RedirectResponse
    {
        abort_unless(in_array($provider, self::PROVIDERS, true), 404);

        try {
            $socialiteUser = Socialite::driver($provider)->user();
        } catch (InvalidStateException) {
            return redirect()->route('login')->withErrors([
                'email' => 'Your sign-in session expired before it could be confirmed. Please try again.',
            ]);
        } catch (\Throwable) {
            return redirect()->route('login')->withErrors([
                'email' => 'We could not sign you in with '.ucfirst($provider).'. Please try again.',
            ]);
        }

        $user = $this->findOrCreateUser($provider, $socialiteUser);

        Auth::login($user, remember: true);

        return redirect()->intended(route('home'));
    }

    protected function findOrCreateUser(string $provider, SocialiteUser $socialiteUser): User
    {
        $existing = User::where('oauth_provider', $provider)
            ->where('oauth_provider_id', $socialiteUser->getId())
            ->first();

        if ($existing) {
            return $existing;
        }

        $byEmail = User::where('email', $socialiteUser->getEmail())->first();

        if ($byEmail) {
            $byEmail->forceFill([
                'oauth_provider' => $provider,
                'oauth_provider_id' => $socialiteUser->getId(),
                'email_verified_at' => $byEmail->email_verified_at ?? now(),
            ])->save();

            return $byEmail;
        }

        $user = User::create([
            'name' => $socialiteUser->getName() ?: $socialiteUser->getNickname() ?: $socialiteUser->getEmail(),
            'email' => $socialiteUser->getEmail(),
            'password' => null,
            'oauth_provider' => $provider,
            'oauth_provider_id' => $socialiteUser->getId(),
        ]);

        $user->forceFill(['email_verified_at' => now()])->save();

        DemoBoardSeeder::seed($user);

        return $user;
    }
}
