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
 * What stays reachable while it is on:
 *
 *   - the lock screen itself, or there is nowhere to type the password;
 *   - the SIGN-IN routes, so an admin can get in and bypass the lock
 *     properly rather than everyone sharing one secret. Registration is not
 *     among them: a shut door that hands out keys is not shut. Discord is a
 *     sign-in route AND a way to acquire an account, so the account-creating
 *     half is refused separately — see DiscordController::callback;
 *   - the public pages — the landing pages, the guides and the CMS pages.
 *     The lock exists to keep the APP unannounced, not to hide the shop
 *     window. Those pages are the ones a search engine indexes and the ones
 *     somebody lands on from a Discord post; serving them a password box
 *     costs the launch the audience it is being built for.
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
        // Recovery for an account that already exists, which is the one way
        // back in for whoever is building the site. Not a way to acquire one.
        'forgot-password',
        'reset-password/*',
        'auth/discord/*',
        'up',
    ];

    /**
     * The public pages, by ROUTE NAME rather than by path.
     *
     * `pages.show` is the CMS catch-all `/{page}`, which matches any single
     * segment — as a path pattern it would let `/events` and `/teams` through
     * with it. Matching on the name the router actually resolved says exactly
     * what is meant, and it keeps working when an admin adds a page nobody
     * listed here.
     */
    private const PUBLIC_ROUTES = [
        'home',
        'landing.snakes',
        'landing.clan-events',
        'landing.event-ideas',
        'pages.show',
        'sitemap',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! Setting::get('site_lock_enabled')) {
            return $next($request);
        }

        if ($request->is(...self::ALWAYS_ALLOWED)) {
            return $next($request);
        }

        if (in_array($request->route()?->getName(), self::PUBLIC_ROUTES, true)) {
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
