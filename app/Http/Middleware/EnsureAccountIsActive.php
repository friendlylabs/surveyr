<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsureAccountIsActive
 *
 * Signs a disabled account out on its next request, whichever way it signed in (password, email link, passkey), and
 * sends it to the sign-in page with the reason. Runs for every web request.
 */
class EnsureAccountIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->isDisabled()) {
            return $next($request);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $message = __('This account is disabled. Ask an admin to enable it.');

        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'message' => $message, 'redirect' => route('auth.login')], 403);
        }

        return redirect()->route('auth.login')->with('error', $message);
    }
}
