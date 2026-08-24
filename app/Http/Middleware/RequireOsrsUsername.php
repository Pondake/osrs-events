<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Every account needs an OSRS username, and this is what makes that true for
 * accounts the registration form never touched.
 *
 * The email/password form can simply ask (RegisteredUserController), but
 * Discord OAuth cannot: the round-trip returns a Discord identity and nothing
 * else, so the only place to ask is after the login lands. Same for every
 * account that already existed before the field did.
 *
 * A redirect rather than a 403: the user has done nothing wrong, there is
 * just one thing left to fill in, and a wall would be a dead end where a
 * redirect is a next step.
 */
class RequireOsrsUsername
{
    /**
     * Routes that stay reachable without one — otherwise the gate would also
     * block the page that clears it, which is a loop the user cannot leave.
     * Logout is here for the same reason: being unable to sign out of an
     * account you are locked inside is worse than the missing field.
     */
    private const ALLOWED = [
        'osrs.create',
        'osrs.store',
        'logout',
        // The first-run wizard asks for the name itself, so its own endpoints
        // have to survive the gate or the tour cannot finish or record that
        // it finished.
        'onboarding.complete',
        'onboarding.joinable',
        // Registering a browser for notifications is not playing, so the
        // gate has no interest in it — and blocking it has a cost the gate
        // was never meant to have. The silent opt-in runs on every page
        // load, so a user who has not filled in their name yet would fail
        // this POST (and log an error) on every single page until they did.
        'push.subscribe',
        'push.unsubscribe',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || filled($user->osrs_username)) {
            return $next($request);
        }

        if (in_array($request->route()?->getName(), self::ALLOWED, true)) {
            return $next($request);
        }

        // While the first-run wizard is still pending, it is already asking
        // for this — bouncing to the standalone page as well meant finishing
        // the tour and being met immediately by a screen demanding the one
        // thing the tour just covered.
        //
        // Reads only. Anything that WRITES still needs the name, so nobody
        // can join, roll or claim their way past the gate in this window,
        // and it closes by itself the moment onboarding is completed or
        // skipped — which is when the standalone page takes over again.
        if ($user->onboarding_completed_at === null && $request->isMethodSafe()) {
            return $next($request);
        }

        // Where to come back to. Nothing was storing this, so
        // `redirect()->intended()` in OsrsUsernameController had nothing to
        // read and always fell back to /events — reported as being sent to
        // the name page while claiming a bingo square and then not being
        // returned to the card.
        //
        // A safe request comes back to itself. Anything else is a write, and
        // its URL only answers that verb, so the page it was made from is the
        // honest destination.
        $request->session()->put(
            'url.intended',
            $request->isMethodSafe() ? $request->fullUrl() : url()->previous(),
        );

        // Inertia follows a 302 as a client-side visit, so this lands as a
        // normal page change rather than the "all Inertia responses must be
        // Inertia responses" error a bare redirect can otherwise cause.
        return redirect()->route('osrs.create');
    }
}
