<?php

use App\Http\Middleware\ResolveCurrentWorkspace;
use App\Http\Middleware\SetLocale;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn () => route('auth.login'));
        $middleware->redirectUsersTo(fn () => route('dashboard'));
        $middleware->alias(['workspace' => ResolveCurrentWorkspace::class]);
        $middleware->web(append: [SetLocale::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $wantsJson = fn (Request $request) => $request->is('api/*') || $request->expectsJson();

        $exceptions->shouldRenderJsonWhen($wantsJson);

        // every JSON error has the same { success: false, message } shape as Controller::jsonError,
        // so the frontend (app.js submitForm) handles thrown exceptions and returned errors alike
        $exceptions->render(function (Throwable $e, Request $request) use ($wantsJson) {
            if (! $wantsJson($request)) {
                return null; // fall through to the regular HTML error pages
            }

            $status = 500;
            $message = __('An unexpected error occurred');
            $response = [];

            if ($e instanceof HttpException) {
                $status = $e->getStatusCode();
                $message = $e->getMessage() ?: (Response::$statusTexts[$status] ?? __('An error occurred'));
            }

            // Laravel wraps these in an HttpException before this callback runs; check the original
            if ($e->getPrevious() instanceof ModelNotFoundException) {
                $status = 404;
                $message = __('The requested record was not found');
            }

            if ($e instanceof TokenMismatchException || $e->getPrevious() instanceof TokenMismatchException) {
                $status = 419;
                $message = __('Your session has expired. Reload the page and try again');
            }

            if ($e instanceof ValidationException) {
                $status = 422;
                $message = $e->getMessage();
                $response['errors'] = $e->errors(); // per-field messages, shown inline by app.js fieldError()
            }

            if ($e instanceof AuthenticationException) {
                $status = 401;
                $message = __('Unauthenticated');
            }

            if ($e instanceof AuthorizationException) {
                $status = 403;
                $message = $e->getMessage() ?: __('Unauthorized');
            }

            $response = ['success' => false, 'message' => $message] + $response;

            if (config('app.debug') && $status >= 500) {
                $response['debug'] = [
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                    'line' => $e->getLine(),
                    'file' => $e->getFile(),
                    'trace' => $e->getTraceAsString(),
                ];
            }

            return response()->json($response, $status);
        });
    })->create();
