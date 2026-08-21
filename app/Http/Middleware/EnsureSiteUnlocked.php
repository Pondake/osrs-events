<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * A shared password in front of the whole site, for the stretch before
 * launch when it is deployed but not announced.
 *
 * Deliberately NOT maintenance mode. `php artisan down` takes the app off
 * the air for everyone including the people building it, returns 503 to
 * crawlers, and cannot be turned off from a browser. This is the opposite
 * shape: the app runs normally, the door is just shut, and anyone with the
 * password or an admin login walks through it.
 *
 * Two things stay reachable while it is on, and only two:
 *
 *   - the lock screen itself, or there is nowhere to type the password;
 *   - the auth routes, so an admin can sign in and bypass it properly
 *     rather than everyone sharing one secret.
 *
 * Health checks are exempt too — a locked site that reports itself down to
 * its own monitoring is a false alarm every minute.
 *
 * Two passes, deliberately: the shared password is for showing somebody the
 * site, and an admin session is for working on it. Neither implies the
 * other, and the shared one can be rotated without touching accounts.
 */
class EnsureSiteUnlocked
{
    /** The session flag set by SiteLockController once the password matches. */
    public const SESSION_KEY = 'site_unlocked';

    /**
     * Paths that answer normally while the site is locked.
     *
     * Patterns, not exact matches — `auth/discord/*` covers both legs of the
     * OAuth round trip, and leaving the callback out would lock people out
     * halfway through logging in.
     */
    private const ALWAYS_ALLOWED = [
        'locked',
        'login',
        'logout',
        'register',
        'forgot-password',
        'reset-password/*',
        'auth/discord/*',
        'up',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! Setting::get('site_lock_enabled')) {
            return $next($request);
        }

        if ($request->is(...self::ALWAYS_ALLOWED)) {
            return $next($request);
        }

        // An admin session is a pass on its own. isAdmin() rather than any
        // authenticated user: the point is to keep the site from being read
        // by people who wander in, and every account can be created freely.
        if ($request->user()?->isAdmin()) {
            return $next($request);
        }

        if ($request->session()->get(self::SESSION_KEY) === true) {
            return $next($request);
        }

        // A JSON caller gets a status code rather than a redirect to an HTML
        // page it cannot use — that includes every fetch() in this app, which
        // would otherwise parse the lock screen as a failed response body.
        if ($request->expectsJson()) {
            abort(423, trans('lock.locked'));
        }

        return redirect()->route('site-lock.show');
    }
}
