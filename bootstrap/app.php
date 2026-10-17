<?php

use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\RestrictAdminAccess;
use App\Http\Middleware\SetLocale;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Which proxies may speak for the client.
        //
        // Behind nginx, Apache or a tunnel the connection reaches PHP from the
        // proxy, so $request->ip() is the PROXY's address -- usually 127.0.0.1
        // -- and the real visitor's address is only in X-Forwarded-For. Laravel
        // reads that header only from a proxy named here, because anyone can
        // send it.
        //
        // This matters most to RestrictAdminAccess: without it an allow-list
        // containing 127.0.0.1 matches every request that came through a
        // same-host proxy, which is everyone. Set TRUSTED_PROXIES to the
        // proxy's address (or "*" when only the proxy can reach PHP at all).
        // Empty means no proxy is believed, which is right for PHP serving
        // directly.
        $raw = trim((string) env('TRUSTED_PROXIES', ''));

        if ($raw !== '') {
            $middleware->trustProxies(at: $raw === '*'
                ? '*'
                : array_values(array_filter(array_map('trim', explode(',', $raw)))));
        }

        $middleware->web(append: [
            SetLocale::class,
        ]);

        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            'admin.network' => RestrictAdminAccess::class,
        ]);

        // The network lock has to be the first thing the admin routes do.
        //
        // Laravel sorts the stack by its own priority list, and Authenticate is
        // on that list while our middleware is not -- so auth was running
        // first, and a stranger probing /admin got a redirect to the login page
        // instead of the 404 the lock exists to give them. That redirect is the
        // answer to "is there an admin area here?", which is exactly what
        // RestrictAdminAccess is meant never to reveal.
        // Anchored on the interface, because that is what Laravel's priority
        // list actually holds -- naming Authenticate itself matches nothing and
        // lands us silently at the end of the list, which is where this started.
        $middleware->prependToPriorityList(
            before: AuthenticatesRequests::class,
            prepend: RestrictAdminAccess::class,
        );

        // Written by JavaScript (the browser's time zone name, used only to
        // pick a first language), so it is plain text, not an encrypted cookie.
        $middleware->encryptCookies(except: [
            SetLocale::ZONE_COOKIE,
        ]);

        // ABA PayWay POSTs here from its own servers when a payment lands. It
        // has no session with us and therefore no CSRF token, so the check has
        // to be skipped or every callback would be a 419 and no course would
        // ever unlock.
        //
        // Safe because the endpoint trusts nothing it is sent: it reads only the
        // transaction id and then asks PayWay's check-transaction API what
        // actually happened (see PayWayCallbackController). A forged POST can
        // therefore do no more than make us re-ask about a real transaction.
        $middleware->validateCsrfTokens(except: [
            'payway/callback',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // An expired/tampered verification link (bad signature) or one that
        // belongs to a different account (hash mismatch) would otherwise be a
        // bare 403 page. Send the user back to the verify screen with a way
        // forward instead. Scoped to that one route: everything else keeps
        // Laravel's normal 403 handling.
        // (Laravel has already turned the hash-mismatch AuthorizationException
        // into a 403 HttpException by the time render callbacks run, and
        // InvalidSignatureException is itself a 403 HttpException.)
        $exceptions->render(function (HttpException $e, Request $request) {
            if ($e->getStatusCode() !== 403 || ! $request->routeIs('verification.verify')) {
                return null;
            }

            return redirect()->route('verification.notice')->withErrors([
                'verification' => __('That verification link has expired or is not valid. Request a new one below.'),
            ]);
        });
    })->create();
