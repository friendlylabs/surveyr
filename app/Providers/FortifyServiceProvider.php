<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;

/**
 * FortifyServiceProvider
 *
 * Fortify's auth actions, email links and rate limits.
 */
class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Auth\AuthController owns the auth routes (routes/auth.php); Fortify supplies the actions behind them
        Fortify::ignoreRoutes();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        // the usual email + password check, but a disabled account is turned away (only once the password is right,
        // so it says nothing about accounts to someone guessing)
        Fortify::authenticateUsing(function (Request $request) {
            $user = User::where('email', $request->input(Fortify::username()))->first();
            if (! $user || ! Hash::check((string) $request->input('password'), $user->password)) {
                return null;
            }
            if ($user->isDisabled()) {
                throw ValidationException::withMessages([Fortify::username() => __('This account is disabled. Ask an admin to enable it.')]);
            }

            return $user;
        });

        // reset emails link to our route name, not Fortify's password.reset
        ResetPassword::createUrlUsing(fn ($user, string $token) => route('auth.reset-password', [
            'token' => $token,
            'email' => $user->getEmailForPasswordReset(),
        ]));

        // verification emails link to our route name, not Laravel's verification.verify
        VerifyEmail::createUrlUsing(fn ($user) => URL::temporarySignedRoute(
            'auth.verify-email.verify',
            now()->addMinutes(Config::get('auth.verification.expire', 60)),
            ['id' => $user->getKey(), 'hash' => sha1($user->getEmailForVerification())]
        ));

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        // the two-factor challenge: five codes a minute for the account being signed in
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        RateLimiter::for('passkeys', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        // reset-link, sign-in-link and verification emails: a few per minute per address, so the forms can't be used
        // to spam an inbox
        RateLimiter::for('auth-email', function (Request $request) {
            return Limit::perMinute(3)->by(Str::lower((string) ($request->input('email') ?? $request->user()?->email)).'|'.$request->ip());
        });
    }
}
