<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Rules\OsrsUsername;
use App\Services\OsrsIdentityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The services this account is wired to, on their own settings page.
 *
 * Split out of Settings → Account on 2026-08-30. Account had grown into two
 * unrelated jobs — how you get INTO this account (email, password, closing
 * it) and which outside services it talks to — and a second outside service
 * arriving is what made that obvious. They are separate concerns with
 * separate reasons to be visited, so they are separate pages.
 *
 * Discord's own connect/disconnect still live on DiscordController: that flow
 * is an OAuth round trip and belongs with the rest of it. This page only
 * renders the state and links into it.
 *
 * The OSRS account name moved here from Settings → Profile on 2026-09-03. It
 * is the one field on the site that reaches an outside service — Wise Old Man
 * is asked whether the name exists, and the answer is stored — which is this
 * page's subject, where Profile's is who you are here. The two write actions
 * came with it; nothing on the profile page reads the field any more.
 */
class ConnectionsController extends Controller
{
    public function show(): Response
    {
        $user = Auth::user();

        return Inertia::render('Settings/Connections', [
            'hasDiscord' => $user->discord_id !== null,

            // Needed for the disconnect guard, not for a password field: an
            // account whose only way in is Discord may not unlink it, and the
            // button says so rather than failing on submit.
            'hasPassword' => $user->password !== null,

            'osrsUsername' => $user->osrs_username,

            // Whether Wise Old Man could find it. Rendered as a state on the
            // card rather than left implicit: a typed name sitting next to an
            // OAuth-linked Discord account reads as equally proven, and it
            // isn't.
            'osrsVerified' => $user->osrs_verified_at !== null,
        ]);
    }

    /**
     * The OSRS account name, kept on its own endpoint rather than folded into
     * a shared settings update.
     *
     * Two forms writing through one validated action is how a field gets
     * wiped: validate() returns only the keys it has rules for, so a save
     * that also listed osrs_username would blank it whenever the other form
     * didn't send one.
     */
    public function updateOsrsUsername(Request $request, OsrsIdentityService $identity): RedirectResponse
    {
        $data = $request->validate([
            // Required rather than nullable: every account has one by the
            // time it gets here (RequireOsrsUsername sees to that), so
            // allowing a blank would let someone quietly undo it and drop
            // out of every race they had entered.
            'osrs_username' => ['required', 'string', new OsrsUsername],
        ]);

        $found = $identity->apply($request->user(), $data['osrs_username']);

        // Saved regardless; an unconfirmed name is a warning, not a rejection.
        return $found === false
            ? back()->with('board-save-error', trans('auth.osrs_not_found'))
            : back()->with('board-save', trans('profile.osrs_username_saved'));
    }

    /**
     * Re-run the Wise Old Man check on the name already stored — the action
     * behind the recurring "we can't find this account" notice.
     */
    public function verifyOsrsUsername(Request $request, OsrsIdentityService $identity): RedirectResponse
    {
        $found = $identity->recheck($request->user());

        return match ($found) {
            true => back()->with('board-save', trans('auth.osrs_found')),
            false => back()->with('board-save-error', trans('auth.osrs_not_found')),
            // Their API was unreachable. Saying "not found" here would tell
            // someone their own RSN is wrong because a third party was down.
            default => back()->with('board-save-error', trans('auth.osrs_check_failed')),
        };
    }
}
