<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use App\Http\Middleware\EnsureCanAccessAdmin;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RequireOsrsUsername;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
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
