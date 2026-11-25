<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
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
        // AuthController owns the auth routes (see routes/web.php); Fortify supplies the actions behind them
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
