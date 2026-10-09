<?php

declare(strict_types=1);

use App\Domain\Exceptions\DomainException;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\AuthenticateSession;
use Inertia\Inertia;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
    )
    // Event discovery (Laravel's own mechanism): listener classes in the shell's
    // or any module's Listeners folder are registered by the event their
    // handle() method type-hints. Module listener files are mapped to class
    // names through Composer's PSR-4 prefixes (see AppServiceProvider).
    ->withEvents(discover: [
        __DIR__.'/../app/Listeners',
        __DIR__.'/../app-modules/*/Listeners',
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'theme', 'layout', 'locale', 'sidebar_state']);

        $middleware->throttleApi();

        // AuthenticateSession signs a session out once the account's password
        // changes elsewhere (a password reset, "Sign out other sessions").
        $middleware->web(append: [
            AuthenticateSession::class,
            SecurityHeaders::class,
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // A broken business rule (a DomainException) normally never reaches
        // here: policies and FormRequests catch it first. When it does, the
        // page was out of date (someone changed the record meanwhile), so the
        // user gets a toast and the previous page, not an error page.
        $exceptions->dontReport(DomainException::class);
        $exceptions->render(function (DomainException $exception, Request $request): JsonResponse|RedirectResponse {
            if ($request->expectsJson() && ! $request->hasHeader('X-Inertia')) {
                return response()->json(['message' => $exception->getMessage()], 409);
            }

            Inertia::flash('toast', ['type' => 'error', 'message' => __('That can’t be done any more: something changed in the meantime. Check and try again.')]);

            return back();
        });
    })->create();
