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

        // Inertia follows a 302 as a client-side visit, so this lands as a
        // normal page change rather than the "all Inertia responses must be
        // Inertia responses" error a bare redirect can otherwise cause.
        return redirect()->route('osrs.create');
    }
}
