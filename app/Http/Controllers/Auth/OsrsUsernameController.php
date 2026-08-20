<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Rules\OsrsUsername;
use App\Services\OsrsIdentityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The one-field page every account without an OSRS username is sent to.
 *
 * Exists because Discord OAuth has nowhere to ask: the callback returns a
 * Discord identity and that is all, so the question has to come after the
 * login rather than during it. Accounts created before the field existed land
 * here too, which is the point — an untracked account is an account that
 * cannot take part in a skill race.
 *
 * @see \App\Http\Middleware\RequireOsrsUsername
 */
class OsrsUsernameController extends Controller
{
    public function create(Request $request): Response
    {
        return Inertia::render('Auth/OsrsUsername', [
            // Prefilled from whatever we already know them by. A Discord
            // handle is often the same name, and a wrong guess costs one
            // edit while a right one costs nothing.
            'suggestion' => substr((string) $request->user()->displayName(), 0, 12),
        ]);
    }

    public function store(Request $request, OsrsIdentityService $identity): RedirectResponse
    {
        $data = $request->validate([
            'osrs_username' => ['required', 'string', new OsrsUsername],
        ]);

        // Saved either way — Wise Old Man only knows accounts somebody has
        // looked up there before, so a real newcomer 404s and refusing the
        // name would lock out exactly the people this is for.
        $found = $identity->apply($request->user(), $data['osrs_username']);

        $redirect = redirect()->intended('/events');

        return $found === false
            ? $redirect->with('board-save-error', trans('auth.osrs_not_found'))
            : $redirect;
    }
}
