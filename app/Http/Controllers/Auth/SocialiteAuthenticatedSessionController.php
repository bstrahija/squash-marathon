<?php

namespace App\Http\Controllers\Auth;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Contracts\User as OAuthUser;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Laravel\Socialite\Two\InvalidStateException;

class SocialiteAuthenticatedSessionController extends Controller
{
    public function redirectToGoogle(): RedirectResponse
    {
        /** @var GoogleProvider $driver */
        $driver = Socialite::driver('google');

        return $driver
            ->redirectUrl(route('oauth.google.callback'))
            ->redirect();
    }

    public function handleGoogleCallback(): RedirectResponse
    {
        try {
            /** @var GoogleProvider $driver */
            $driver = Socialite::driver('google');
            $oauthUser = $driver
                ->redirectUrl(route('oauth.google.callback'))
                ->user();
        } catch (InvalidStateException) {
            return redirect()->route('login')->withErrors([
                'email' => __('auth.failed'),
            ]);
        }

        $user = $this->resolveAllowedUser($oauthUser);

        if (! $user) {
            return redirect()->route('login')->withErrors([
                'email' => __('auth.failed'),
            ]);
        }

        Auth::login($user, true);
        request()->session()->regenerate();

        return $this->redirectAfterLogin($user);
    }

    private function resolveAllowedUser(OAuthUser $oauthUser): ?User
    {
        $providerId = (string) $oauthUser->getId();

        $linkedUserId = DB::table('socialite_users')
            ->where('provider', 'google')
            ->where('provider_id', $providerId)
            ->value('user_id');

        $user = $linkedUserId ? User::query()->find($linkedUserId) : null;

        if (! $user) {
            $email = $oauthUser->getEmail();

            if (! filled($email)) {
                return null;
            }

            $user = User::query()->where('email', $email)->first();
        }

        if (! $user) {
            return null;
        }

        if (! $user->hasAnyRole([RoleName::Admin->value, RoleName::Player->value])) {
            return null;
        }

        DB::table('socialite_users')->updateOrInsert(
            [
                'provider' => 'google',
                'provider_id' => $providerId,
            ],
            [
                'user_id' => $user->id,
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );

        return $user;
    }

    private function redirectAfterLogin(User $user): RedirectResponse
    {
        if ((int) $user->getAuthIdentifier() === 1) {
            return redirect()->intended(Filament::getUrl());
        }

        return redirect()->route('home')->with('status', 'Prijavljeni ste');
    }
}
