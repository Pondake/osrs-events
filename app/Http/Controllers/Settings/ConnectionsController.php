<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use App\Rules\OsrsCharacterList;
use App\Rules\OsrsUsername;
use App\Rules\RsnNotProvenByAnother;
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
            // A different question, with a consequence attached: until a
            // RuneLite client has reported this account playing the name,
            // every claim goes past a host. See ReviewsClaims.
            'osrsProven' => $user->hasProvenOsrsName(),
            'provenAt' => $user->osrs_proven_at?->toIso8601String(),
            'characters' => self::characters($user),
            'maxCharacters' => OsrsIdentityService::maxCharacters(),
            'pluginMode' => Setting::get('runelite_plugin_mode'),
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
        if ($request->has('characters')) {
            return self::saveCharacters($request, $identity);
        }

        $data = $request->validate([
            // Required rather than nullable: every account has one by the
            // time it gets here (RequireOsrsUsername sees to that), so
            // allowing a blank would let someone quietly undo it and drop
            // out of every race they had entered.
            'osrs_username' => ['required', 'string', new OsrsUsername, new RsnNotProvenByAnother($request->user())],
        ]);

        $found = $identity->apply($request->user(), $data['osrs_username']);

        // Saved regardless; an unconfirmed name is a warning, not a rejection.
        // Same order as OsrsUsernameController::store — a name another
        // account already carries is the more consequential of the two.
        if ($identity->takenByAnother($request->user(), $request->user()->osrs_username)) {
            return back()->with('board-save-error', trans('auth.osrs_taken'));
        }

        return $found === false
            ? back()->with('board-save-error', trans('auth.osrs_not_found'))
            : back()->with('board-save', trans('profile.osrs_username_saved'));
    }

    /**
     * The whole list of characters, main first — the repeater in settings
     * and in the first-run tour both post this.
     */
    public static function saveCharacters(Request $request, OsrsIdentityService $identity): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'characters' => ['required', 'array', 'min:1', new OsrsCharacterList($user)],
            'characters.*' => ['required', 'string', new OsrsUsername, new RsnNotProvenByAnother($user)],
        ], [], [
            'characters.*' => trans('auth.field_osrs_username'),
        ]);

        $result = $identity->saveCharacters($user, $data['characters']);

        if ($result['taken'] !== []) {
            return back()->with('board-save-error', trans('auth.osrs_taken_names', ['names' => implode(', ', $result['taken'])]));
        }

        return $result['missing'] !== []
            ? back()->with('board-save-error', trans('auth.osrs_not_found_names', ['names' => implode(', ', $result['missing'])]))
            : back()->with('board-save', trans('profile.osrs_characters_saved'));
    }

    /** What the character repeater renders, main first. */
    public static function characters(User $user): array
    {
        return $user->osrsAccounts()->get()->map(fn ($account) => [
            'id' => $account->id,
            'username' => $account->username,
            'main' => $account->isMain(),
            'verified' => $account->osrs_verified_at !== null,
            'proven' => $account->osrs_proven_at !== null,
        ])->all();
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
