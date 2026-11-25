<?php

namespace App\Http\Middleware;

use App\Support\Locales;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * SetLocale
 *
 * Sets the request's interface language (Locales::resolve): the user's choice, their workspace's default, the browser's
 * language, then app.locale. Runs for every web request, sign-in pages included.
 */
class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        app()->setLocale(Locales::resolve($request));

        return $next($request);
    }
}
