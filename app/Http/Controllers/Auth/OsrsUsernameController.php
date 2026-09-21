<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Middleware\RequireOsrsUsername;
use App\Models\Setting;
use App\Rules\OsrsUsername;
use App\Services\OsrsIdentityService;
use Illuminate\Http\JsonResponse;
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
 * @see RequireOsrsUsername
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
            // See RegisteredUserController::create — same notice, the other
            // place a name is first typed.
            'proofMatters' => Setting::get('runelite_plugin_mode') === 'live',
        ]);
    }

    /**
     * What the hiscores and this site already know about a name, asked
     * while it is being typed rather than after it is saved.
     *
     * Every form that takes an RSN calls this on blur. Nothing is stored and
     * nothing is refused — a name Wise Old Man has never heard of is normal
     * for a new player, and a name another account carries is a thing to be
     * told about, not stopped by. The answer is advice; store() is still
     * where the decision is made.
     */
    public function check(Request $request, OsrsIdentityService $identity): JsonResponse
    {
        $data = $request->validate([
            'osrs_username' => ['required', 'string', new OsrsUsername],
        ]);

        $name = trim($data['osrs_username']);
        $found = $identity->look($name);

        return response()->json([
            'found' => $found['found'],
            'displayName' => $found['displayName'],
            'taken' => $identity->takenByAnother($request->user(), $name),
        ]);
    }

    public function store(Request $request, OsrsIdentityService $identity): RedirectResponse
    {
        $data = $request->validate([
            'osrs_username' => ['required', 'string', new OsrsUsername],
            // The first-run wizard posts this from inside its own modal, and
            // the redirect below would navigate the page out from under it —
            // closing the tour on the step that was meant to be one of
            // several. `stay` keeps the save and drops the navigation.
            'stay' => ['sometimes', 'boolean'],
        ]);

        // Saved either way — Wise Old Man only knows accounts somebody has
        // looked up there before, so a real newcomer 404s and refusing the
        // name would lock out exactly the people this is for.
        $found = $identity->apply($request->user(), $data['osrs_username']);
        $taken = $identity->takenByAnother($request->user(), $request->user()->osrs_username);

        $redirect = ($data['stay'] ?? false) ? back() : redirect()->intended('/events');

        // Two things can be worth saying and only one toast is worth
        // showing. The duplicate wins: it is the one with a consequence
        // waiting (a race will refuse the second entrant), and it already
        // implies the name is real.
        if ($taken) {
            return $redirect->with('board-save-error', trans('auth.osrs_taken'));
        }

        return $found === false
            ? $redirect->with('board-save-error', trans('auth.osrs_not_found'))
            : $redirect;
    }
}
