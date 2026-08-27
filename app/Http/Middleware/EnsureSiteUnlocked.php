<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Two doors, not one — `site_lock_enabled` (the pre-launch door) and
 * `admin_lockdown_enabled` (full lockdown), checked in that order of
 * strictness.
 *
 * **The pre-launch door** is for the stretch before launch when the site is
 * deployed but not announced. Deliberately NOT maintenance mode —
 * `php artisan down` takes the app off the air for everyone including the
 * people building it, returns 503 to crawlers, and cannot be turned off from
 * a browser. This is the opposite shape: the app runs normally, the door is
 * just shut to STRANGERS, and anyone with the password, an admin login, or
 * an existing account of their own walks through it.
 *
 * That last clause is the one worth stating plainly, because it used to be
 * missing: this door exists to keep the app unannounced to people who have
 * never been let in, not to lock out people who already have an account.
 * Someone already signed in has, by definition, already gotten past
 * whichever door they came through (an invite, a Discord login, a
 * registration made before the lock went on) — asking them for the shared
 * password on top of that answered "are you allowed to use the site you are
 * already using" with a password box.
 *
 * What stays reachable while the pre-launch door is shut:
 *
 *   - the lock screen itself, or there is nowhere to type the password;
 *   - the SIGN-IN routes, so anyone with an existing account — admin or
 *     not — can get past it properly rather than everyone sharing one
 *     secret. Registration is not among them: a shut door that hands out
 *     keys is not shut. Discord is a sign-in route AND a way to acquire an
 *     account, so the account-creating half is refused separately — see
 *     DiscordController::callback;
 *   - the public pages — the landing pages, the guides and the CMS pages.
 *     The lock exists to keep the APP unannounced, not to hide the shop
 *     window. Those pages are the ones a search engine indexes and the ones
 *     somebody lands on from a Discord post; serving them a password box
 *     costs the launch the audience it is being built for;
 *   - anyone already signed in, of any role.
 *
 * **Full lockdown** is the stricter, rarer switch: while it is on, NOTHING
 * but an admin session gets through — not an existing player, not someone
 * holding the shared password. It is checked first and is not softened by
 * the pre-launch door's rules; the two are independent, not layered, so
 * turning the pre-launch door off does nothing to a full lockdown left on.
 *
 * Health checks are exempt from both — a locked site that reports itself
 * down to its own monitoring is a false alarm every minute.
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
        //
        // BOTH spellings of the reset path. `reset-password/*` is the link
        // from the email, which carries a token; `reset-password` is where
        // that page POSTs to, and it has no segment after it. With only the
        // first, a locked site let somebody open the link, type a new
        // password, and then answered 423 when they saved it — the recovery
        // path was dead in exactly the state that needs it most.
        'forgot-password',
        'reset-password',
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
        'landing.bingo',
        'landing.skill-race',
        'landing.drop-race',
        'pages.show',
        'sitemap',
        // The catch-all miss. A locked site should answer a dead URL the same
        // way an open one does — and it already did for single-segment paths,
        // which `pages.show` above lets through to 404 from PageController.
        // Without this a deeper dead URL redirected to the lock screen
        // instead, so `/nope` and `/nope/nope` disagreed for no reason a
        // visitor could see. Nothing leaks: the error page is identical for
        // every unknown URL, and the header it wears is the trimmed
        // locked-site nav.
        'miss',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! self::isShutFor($request)) {
            return $next($request);
        }

        if ($request->is(...self::ALWAYS_ALLOWED)) {
            return $next($request);
        }

        // Full lockdown does not carve out the public marketing pages —
        // "blocks everything except for admins" means everything. Only the
        // pre-launch door offers that carve-out.
        if (! Setting::get('admin_lockdown_enabled')
            && in_array($request->route()?->getName(), self::PUBLIC_ROUTES, true)) {
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

    /**
     * Whether this visitor is blocked by either door — the one place both
     * this middleware and HandleInertiaRequests' shared `site.locked`/
     * announcement-visibility props ask the question, so the two can't drift
     * apart the way "is this visitor let in" and "is this visitor let in"
     * managed to disagree before (the bug this whole rewrite fixes: the
     * pre-launch door blocked an ordinary signed-in account from the app
     * while the header/home page still rendered as if they had full access).
     */
    public static function isShutFor(Request $request): bool
    {
        if ($request->user()?->isAdmin()) {
            return false;
        }

        // The stricter door. If it's on, nothing below matters — not the
        // shared password, not an ordinary account already signed in.
        if (Setting::get('admin_lockdown_enabled')) {
            return true;
        }

        if (! Setting::get('site_lock_enabled')) {
            return false;
        }

        // Any signed-in account is a pass — the pre-launch door is for
        // strangers, not for people who already have a way in.
        if ($request->user() !== null) {
            return false;
        }

        return $request->session()->get(self::SESSION_KEY) !== true;
    }
}
