<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Contracts\UpdatesUserPasswords;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;
use Laravel\Fortify\Features;
use Laravel\Fortify\Http\Controllers\ConfirmedTwoFactorAuthenticationController;
use Laravel\Fortify\Http\Controllers\RecoveryCodeController;
use Laravel\Fortify\Http\Controllers\TwoFactorAuthenticationController;
use Laravel\Fortify\Http\Controllers\TwoFactorQrCodeController;
use Laravel\Fortify\Http\Controllers\TwoFactorSecretKeyController;
use Laravel\Passkeys\Http\Controllers\PasskeyRegistrationController;

/**
 * ProfileController
 *
 * The signed-in user's own account (app/profile/index): profile information, password, two-factor authentication,
 * browser sessions and passkeys. Fortify's actions and controllers do the work; two-factor, passkeys and sessions
 * sit behind password.confirm, which the page answers with its confirm-password modal (a 423 from any of them).
 */
class ProfileController extends Controller
{
    /** The profile page. */
    public function index(Request $request)
    {
        $this->user = $request->user()->load('role');
        $this->twoFactor = Features::enabled(Features::twoFactorAuthentication());
        $this->passkeysEnabled = Features::enabled(Features::passkeys());
        $this->passkeys = $this->passkeysEnabled ? $this->user->passkeys()->latest()->get() : collect();
        $this->sessionsListed = config('session.driver') === 'database';
        $this->sessions = $this->sessionsListed ? $this->sessions($request) : collect();

        return view('app.profile.index', $this->data);
    }

    /**
     * Saves the name and email. A changed email is unconfirmed again (while email verification is on), so that sends
     * them to confirm it.
     */
    public function update(Request $request, UpdatesUserProfileInformation $updater)
    {
        $user = $request->user();
        $updater->update($user, $request->only('name', 'email'));

        $mustConfirm = Features::enabled(Features::emailVerification()) && ! $user->hasVerifiedEmail();
        $this->redirect = $mustConfirm ? route('auth.verify-email') : url()->previous();

        return $this->jsonSuccess($mustConfirm ? 'Profile saved. Confirm your new email to carry on.' : 'Profile saved');
    }

    /** Changes the password. The user's other sessions end with the old password; this one carries on. */
    public function password(Request $request, UpdatesUserPasswords $updater)
    {
        $user = $request->user();
        $updater->update($user, $request->only('current_password', 'password', 'password_confirmation'));

        $this->otherSessions($request)?->delete();

        $this->redirect = url()->previous();

        return $this->jsonSuccess('Password changed. Your other devices are signed out.');
    }

    /** Signs out one of the user's other devices; this device signs out through Log out instead. */
    public function destroySession(Request $request, string $id)
    {
        if ($id === $request->session()->getId()) {
            return $this->jsonError('That\'s this device. Use Log out in the account menu instead.', 422);
        }

        if (! $this->otherSessions($request)?->where('id', $id)->delete()) {
            return $this->jsonError('That session has already ended', 404);
        }

        return $this->jsonSuccess('Device signed out');
    }

    /** Signs out every other device the user is signed in on. */
    public function destroyOtherSessions(Request $request)
    {
        $count = (int) $this->otherSessions($request)?->delete();

        $this->redirect = url()->previous();

        return $this->jsonSuccess($count ? "{$count} other ".str('device')->plural($count).' signed out' : 'No other devices were signed in');
    }

    /** The user's sessions, this device first: { id, current, ip_address, last_active, device }. */
    private function sessions(Request $request): Collection
    {
        return DB::connection(config('session.connection'))
            ->table(config('session.table', 'sessions'))
            ->where('user_id', $request->user()->id)
            ->where('last_activity', '>=', self::liveSince())
            ->orderByDesc('last_activity')
            ->get()
            ->map(fn ($session) => (object) [
                'id' => $session->id,
                'current' => $session->id === $request->session()->getId(),
                'ip_address' => $session->ip_address,
                'last_active' => Carbon::createFromTimestamp($session->last_activity),
                'device' => self::describeUserAgent($session->user_agent),
                'mobile' => (bool) preg_match('/iPhone|iPad|Android|Mobile/', (string) $session->user_agent),
            ])
            ->sortByDesc('current')
            ->values();
    }

    /** The user's sessions on other devices, as a query; null when sessions aren't kept in the database. */
    private function otherSessions(Request $request)
    {
        if (config('session.driver') !== 'database') {
            return null;
        }

        return DB::connection(config('session.connection'))
            ->table(config('session.table', 'sessions'))
            ->where('user_id', $request->user()->id)
            ->where('id', '!=', $request->session()->getId())
            ->where('last_activity', '>=', self::liveSince());
    }

    /**
     * Sessions idle longer than session.lifetime have expired; Laravel only sweeps their rows now and then
     * (session.lottery), so they're filtered out here rather than listed as signed-in devices.
     */
    private static function liveSince(): int
    {
        return now()->subMinutes((int) config('session.lifetime'))->getTimestamp();
    }

    /** A raw user agent as "Browser on Platform" (Chrome on Windows); a friendly label, not a full parser. */
    private static function describeUserAgent(?string $userAgent): string
    {
        $userAgent ??= '';

        $browser = match (true) {
            str_contains($userAgent, 'Edg/') => 'Edge',
            str_contains($userAgent, 'OPR/') || str_contains($userAgent, 'Opera') => 'Opera',
            str_contains($userAgent, 'Firefox/') => 'Firefox',
            str_contains($userAgent, 'HeadlessChrome/') => 'Headless Chrome',
            str_contains($userAgent, 'Chrome/') => 'Chrome',
            str_contains($userAgent, 'Safari/') => 'Safari',
            default => null,
        };

        $platform = match (true) {
            str_contains($userAgent, 'iPhone') => 'iPhone',
            str_contains($userAgent, 'iPad') => 'iPad',
            str_contains($userAgent, 'Android') => 'Android',
            str_contains($userAgent, 'Windows') => 'Windows',
            str_contains($userAgent, 'Macintosh') || str_contains($userAgent, 'Mac OS X') => 'Mac',
            str_contains($userAgent, 'Linux') => 'Linux',
            default => null,
        };

        return match (true) {
            $browser && $platform => "{$browser} on {$platform}",
            $browser !== null => $browser,
            $platform !== null => $platform,
            default => 'Unknown device',
        };
    }

    /** Registers the profile routes. */
    public static function routes(): void
    {
        Route::get('/', [self::class, 'index'])->name('profile');
        Route::post('/', [self::class, 'update'])->name('profile.update');
        Route::post('/password', [self::class, 'password'])->name('profile.password');

        // a page visit lands on auth/confirm-password; the profile page's fetches get a 423 and ask in a modal
        Route::middleware('password.confirm:auth.confirm-password')->group(function () {
            if (Features::enabled(Features::twoFactorAuthentication())) {
                Route::post('/two-factor', [TwoFactorAuthenticationController::class, 'store'])->name('profile.two-factor.enable');
                Route::post('/two-factor/confirm', [ConfirmedTwoFactorAuthenticationController::class, 'store'])->name('profile.two-factor.confirm');
                Route::post('/two-factor/disable', [TwoFactorAuthenticationController::class, 'destroy'])->name('profile.two-factor.disable');
                Route::get('/two-factor/qr-code', [TwoFactorQrCodeController::class, 'show'])->name('profile.two-factor.qr-code');
                Route::get('/two-factor/secret-key', [TwoFactorSecretKeyController::class, 'show'])->name('profile.two-factor.secret-key');
                Route::get('/two-factor/recovery-codes', [RecoveryCodeController::class, 'index'])->name('profile.two-factor.recovery-codes');
                Route::post('/two-factor/recovery-codes', [RecoveryCodeController::class, 'store'])->name('profile.two-factor.recovery-codes.regenerate');
            }

            if (Features::enabled(Features::passkeys())) {
                Route::get('/passkeys/options', [PasskeyRegistrationController::class, 'index'])->name('profile.passkeys.options');
                Route::post('/passkeys', [PasskeyRegistrationController::class, 'store'])->name('profile.passkeys.store');
                Route::post('/passkeys/{passkey}/delete', [PasskeyRegistrationController::class, 'destroy'])->name('profile.passkeys.destroy');
            }

            Route::post('/sessions/others/logout', [self::class, 'destroyOtherSessions'])->name('profile.sessions.destroy-others');
            Route::post('/sessions/{id}/logout', [self::class, 'destroySession'])->name('profile.sessions.destroy');
        });
    }
}
