<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\LoginLink;
use App\Support\Registration;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Laravel\Fortify\Actions\AttemptToAuthenticate;
use Laravel\Fortify\Actions\CanonicalizeUsername;
use Laravel\Fortify\Actions\PrepareAuthenticatedSession;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Fortify\Contracts\ResetsUserPasswords;
use Laravel\Fortify\Events\TwoFactorAuthenticationFailed;
use Laravel\Fortify\Events\ValidTwoFactorAuthenticationCodeProvided;
use Laravel\Fortify\Features;
use Laravel\Fortify\Http\Requests\TwoFactorLoginRequest;
use Laravel\Passkeys\Http\Controllers\PasskeyLoginController;

/**
 * AuthController
 *
 * Every auth page and endpoint. Fortify's own routes are off (FortifyServiceProvider); its actions do the work here,
 * and config/fortify.php `features` decides which of these routes exist.
 */
class AuthController extends Controller
{
    /** Sign-in links expire after this many minutes and work once. */
    const LOGIN_LINK_MINUTES = 15;

    /**
     * The sign-in page. ?logged-out and ?reset switch the heading / success alert; logout and password reset redirect
     * here with them.
     */
    public function login(Request $request)
    {
        $this->loggedOut = $request->has('logged-out');
        $this->passwordReset = $request->has('reset');

        return view('auth.login', $this->data);
    }

    /**
     * Fortify's login pipeline: lowercase the email, check the credentials (failures count towards the throttle and
     * come back as a 422 on email), then regenerate the session. An account with two-factor on stops after the
     * credential check: the session remembers who (login.id) and the page moves on to the code.
     */
    public function authenticate(Request $request)
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $result = (new Pipeline(app()))->send($request)->through(array_values(array_filter([
            CanonicalizeUsername::class,
            Features::enabled(Features::twoFactorAuthentication()) ? RedirectIfTwoFactorAuthenticatable::class : null,
            AttemptToAuthenticate::class,
            PrepareAuthenticatedSession::class,
        ])))->thenReturn();

        if (! $result instanceof Request) {
            $this->twoFactor = true;
            $this->redirect = route('auth.two-factor');

            return $this->jsonSuccess(__('Enter the code from your authenticator app'));
        }

        $this->redirect = redirect()->intended(route('dashboard'))->getTargetUrl();

        return $this->jsonSuccess(__('Signed in'));
    }

    /** Signs out and sends the user back to the sign-in page with their email filled in. */
    public function logout(Request $request)
    {
        $email = $request->user()->email;

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $this->redirect = route('auth.login', ['logged-out' => 1, 'email' => $email]);

        return $this->jsonSuccess(__('Signed out'));
    }

    /**
     * The second step for accounts with two-factor on; only reachable straight after a correct password (or email
     * link).
     */
    public function twoFactor(TwoFactorLoginRequest $request)
    {
        if (! $request->hasChallengedUser()) {
            return redirect()->route('auth.login');
        }

        return view('auth.two-factor');
    }

    /** Signs in with a code from the authenticator app, or one of the recovery codes (each works once). */
    public function twoFactorLogin(TwoFactorLoginRequest $request)
    {
        if (! $request->hasChallengedUser()) {
            $this->redirect = route('auth.login');

            return $this->jsonError(__('Your sign-in timed out. Enter your password again.'), 419);
        }

        $user = $request->challengedUser();

        if ($code = $request->validRecoveryCode()) {
            $user->replaceRecoveryCode($code);
        } elseif (! $request->hasValidCode()) {
            event(new TwoFactorAuthenticationFailed($user));

            [$field, $message] = $request->filled('recovery_code')
                ? ['recovery_code', __("That recovery code isn't valid.")]
                : ['code', __("That code didn't match. Try the newest one in your app.")];

            $this->errors = [$field => [$message]];

            return $this->jsonError($message, 422);
        }

        event(new ValidTwoFactorAuthenticationCodeProvided($user));

        Auth::guard('web')->login($user, $request->remember());
        $request->session()->regenerate();

        $this->redirect = redirect()->intended(route('dashboard'))->getTargetUrl();

        return $this->jsonSuccess(__('Signed in'));
    }

    /**
     * The page form of password.confirm, for a full-page visit to a protected url; the profile page asks in a modal
     * instead.
     */
    public function confirmPassword()
    {
        return view('auth.confirm-password');
    }

    /**
     * Marks the password as confirmed for auth.password_timeout seconds (the password.confirm middleware checks it).
     */
    public function storeConfirmedPassword(Request $request)
    {
        $request->validate(['password' => ['required', 'string']]);

        if (! Hash::check($request->input('password'), $request->user()->password)) {
            $this->errors = ['password' => [__("That password isn't right.")]];

            return $this->jsonError(__("That password isn't right."), 422);
        }

        $request->session()->passwordConfirmed();

        $this->redirect = redirect()->intended(route('profile'))->getTargetUrl();

        return $this->jsonSuccess(__('Password confirmed'));
    }

    /** The sign-up page. */
    public function register()
    {
        return view('auth.register');
    }

    /**
     * Fortify's CreateNewUser validates and creates the account (default role); Registered sends the verification
     * email when that feature is on, and the verified middleware holds the app until it's confirmed.
     */
    public function store(Request $request)
    {
        $user = app(CreatesNewUsers::class)->create($request->all());

        event(new Registered($user));

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        $this->redirect = Features::enabled(Features::emailVerification())
            ? route('auth.verify-email')
            : route('dashboard');

        return $this->jsonSuccess(__('Account created'));
    }

    /** The forgot-password page. */
    public function forgotPassword()
    {
        return view('auth.forgot-password');
    }

    /**
     * Emails a password reset link. An unknown address gets the same answer as a known one, so the form can't be used
     * to probe for accounts.
     */
    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => ['required', 'string', 'email']]);
        $email = Str::lower($request->input('email'));

        $status = Password::sendResetLink(['email' => $email]);

        if ($status === Password::RESET_THROTTLED) {
            return $this->jsonError(__($status), 429);
        }

        $this->redirect = route('auth.check-email', ['type' => 'reset', 'email' => $email]);

        return $this->jsonSuccess(__('Reset link sent'));
    }

    /** The reset-password page; an unknown email, a used token and an expired token all show the expired state. */
    public function resetPassword(Request $request, string $token)
    {
        $email = $request->query('email');
        $user = $email ? User::where('email', $email)->first() : null;

        $this->token = $token;
        $this->email = $email;
        $this->expired = ! $user || ! Password::tokenExists($user, $token);
        $this->minutes = config('auth.passwords.users.expire');

        return view('auth.reset-password', $this->data);
    }

    /**
     * Fortify's ResetUserPassword validates and saves the password; rotating the remember token and dropping the
     * user's sessions is what "you'll be signed out on your other devices" means.
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email'],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user) use ($request) {
                app(ResetsUserPasswords::class)->reset($user, $request->all());

                $user->setRememberToken(Str::random(60));
                $user->save();

                // the link reached their inbox (an invite, or a reset), so the address is confirmed
                if (! $user->hasVerifiedEmail()) {
                    $user->markEmailAsVerified();
                }

                if (config('session.driver') === 'database') {
                    DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
                }

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            $this->expired = true; // the page reloads into its expired state

            return $this->jsonError(__($status), 422);
        }

        $this->redirect = route('auth.login', ['reset' => 1, 'email' => $request->input('email')]);

        return $this->jsonSuccess(__('Password updated'));
    }

    /** The "check your email" page. type = reset | link; email = the address the link was sent to. */
    public function checkEmail(Request $request)
    {
        $this->isLink = $request->query('type') === 'link' && Features::enabled('email-link');
        $this->email = $request->query('email');
        $this->minutes = $this->isLink ? self::LOGIN_LINK_MINUTES : config('auth.passwords.users.expire');

        return view('auth.check-email', $this->data);
    }

    /** The email-link sign-in page. */
    public function emailLink()
    {
        return view('auth.email-link');
    }

    /**
     * Emails a sign-in link. The link carries a random token; the cache holds only its hash, and pulling it on use
     * makes it single-use.
     */
    public function sendEmailLink(Request $request)
    {
        $request->validate(['email' => ['required', 'string', 'email']]);
        $email = Str::lower($request->input('email'));

        // a disabled account gets no link; the page reads the same either way
        if (($user = User::where('email', $email)->first()) && ! $user->isDisabled()) {
            $token = Str::random(64);
            Cache::put('login-link:'.hash('sha256', $token), $user->id, now()->addMinutes(self::LOGIN_LINK_MINUTES));

            $user->notify(new LoginLink(route('auth.email-link.login', $token), self::LOGIN_LINK_MINUTES));
        }

        $this->redirect = route('auth.check-email', ['type' => 'link', 'email' => $email]);

        return $this->jsonSuccess(__('Sign-in link sent'));
    }

    /** Signs in from an email link. */
    public function emailLinkLogin(Request $request, string $token)
    {
        $userId = Cache::pull('login-link:'.hash('sha256', $token));

        $user = $userId ? User::find($userId) : null;

        if (! $user) {
            return redirect()->route('auth.email-link')
                ->with('error', __('That sign-in link has expired or was already used. Request a new one.'));
        }

        // the link reached their inbox, so the address is confirmed
        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        // the link stands in for the password, not for the second factor
        if (Features::enabled(Features::twoFactorAuthentication()) && $user->hasTwoFactorEnabled()) {
            $request->session()->put(['login.id' => $user->getKey(), 'login.remember' => false]);

            return redirect()->route('auth.two-factor');
        }

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    /** The verified middleware sends unconfirmed users here; a confirmed one has nothing to do on it. */
    public function verifyEmail(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        $this->email = $request->user()->email;
        $this->minutes = config('auth.verification.expire', 60);

        return view('auth.verify-email', $this->data);
    }

    /**
     * The signed link from the verification email; EmailVerificationRequest checks it belongs to the signed-in
     * user.
     */
    public function confirmEmail(EmailVerificationRequest $request)
    {
        $request->fulfill();

        return redirect()->intended(route('dashboard'))->with('toast', __('Email confirmed. Welcome aboard.'));
    }

    /** Sends the confirmation email again, unless the address is already confirmed. */
    public function sendVerificationEmail(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            $this->redirect = route('dashboard');

            return $this->jsonSuccess(__('Your email is already confirmed'));
        }

        $request->user()->sendEmailVerificationNotification();

        return $this->jsonSuccess(__('Confirmation email sent to :email', ['email' => $request->user()->email]));
    }

    /** Registers the auth routes. */
    public static function routes()
    {
        Route::middleware('guest')->group(function () {
            Route::get('/login', [self::class, 'login'])->name('auth.login');
            Route::post('/authenticate', [self::class, 'authenticate'])->middleware('throttle:login')->name('auth.authenticate');

            // config/auth.php registration (App\Support\Registration): off, or invitation-only, means no public sign-up
            if (Registration::open()) {
                Route::get('/register', [self::class, 'register'])->name('auth.register');
                Route::post('/register', [self::class, 'store'])->middleware('throttle:login')->name('auth.store');
            }

            Route::get('/forgot-password', [self::class, 'forgotPassword'])->name('auth.forgot-password');
            Route::post('/forgot-password', [self::class, 'sendResetLink'])->middleware('throttle:auth-email')->name('auth.send-reset-link');
            Route::get('/reset-password/{token}', [self::class, 'resetPassword'])->name('auth.reset-password');
            Route::post('/reset-password', [self::class, 'updatePassword'])->name('auth.update-password');
            Route::get('/check-email', [self::class, 'checkEmail'])->name('auth.check-email');

            if (Features::enabled(Features::twoFactorAuthentication())) {
                Route::get('/two-factor', [self::class, 'twoFactor'])->name('auth.two-factor');
                Route::post('/two-factor', [self::class, 'twoFactorLogin'])->middleware('throttle:two-factor')->name('auth.two-factor.login');
            }

            // laravel/passkeys runs the WebAuthn ceremony: options first, then the signed credential
            if (Features::enabled(Features::passkeys())) {
                Route::get('/passkeys/options', [PasskeyLoginController::class, 'index'])->middleware('throttle:passkeys')->name('auth.passkeys.options');
                Route::post('/passkeys', [PasskeyLoginController::class, 'store'])->middleware('throttle:passkeys')->name('auth.passkeys.login');
            }

            if (Features::enabled('email-link')) {
                Route::get('/email-link', [self::class, 'emailLink'])->name('auth.email-link');
                Route::post('/email-link', [self::class, 'sendEmailLink'])->middleware('throttle:auth-email')->name('auth.send-email-link');
                Route::get('/email-link/{token}', [self::class, 'emailLinkLogin'])->name('auth.email-link.login');
            }
        });

        Route::middleware('auth')->group(function () {
            if (Features::enabled(Features::emailVerification())) {
                Route::get('/verify-email', [self::class, 'verifyEmail'])->name('auth.verify-email');
                Route::get('/verify-email/{id}/{hash}', [self::class, 'confirmEmail'])->middleware(['signed', 'throttle:6,1'])->name('auth.verify-email.verify');
                Route::post('/verify-email', [self::class, 'sendVerificationEmail'])->middleware('throttle:auth-email')->name('auth.verify-email.send');
            }

            Route::get('/confirm-password', [self::class, 'confirmPassword'])->name('auth.confirm-password');
            Route::post('/confirm-password', [self::class, 'storeConfirmedPassword'])->middleware('throttle:login')->name('auth.confirm-password.store');

            Route::post('/logout', [self::class, 'logout'])->name('auth.logout');
        });
    }
}
