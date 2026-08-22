<?php

use App\Http\Middleware\EnsureCanAccessAdmin;
use App\Http\Middleware\EnsureSiteUnlocked;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RequireOsrsUsername;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\AuthenticateSession;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            // Changing a password signs you out everywhere else.
            //
            // This is the middleware that does it: it keeps the password hash
            // in the session and logs out any session whose copy no longer
            // matches the stored one. Without it `logoutOtherDevices()` cycles
            // the remember token and nothing else — other sessions carry on.
            //
            // Safe for Discord logins: it returns early for a user with no
            // password at all, which is every account that has never set one.
            //
            // Before HandleInertiaRequests, so a session being invalidated is
            // turned away rather than having a page's worth of props built for
            // it first.
            AuthenticateSession::class,
            HandleInertiaRequests::class,
            // Last in the group, so a locked site still gets a session and
            // its shared Inertia props — the lock screen is an Inertia page
            // like any other, and the flag it checks lives in the session.
            EnsureSiteUnlocked::class,
        ]);

        // Gate for the whole /admin group — see EnsureCanAccessAdmin for why
        // the controllers still check individually on top of it.
        $middleware->alias([
            'can-access-admin' => EnsureCanAccessAdmin::class,
            'require-osrs-username' => RequireOsrsUsername::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
