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
 * Display-side settings (name, roles, joined boards). Account-side settings
 * — auth methods, password — live in AccountController next to this one;
 * both render inside Components/SettingsLayout.vue's sidebar shell.
 */
class ProfileController extends Controller
{
    public function show(): Response
    {
        $user = Auth::user()->load('roles:id,name');

        $playerBoards = $user->playerBoards()
            ->with(['board', 'completedTiles'])
            ->orderByDesc('created_at')
            ->get()
            ->filter(fn ($pb) => $pb->board?->event !== null)
            ->values();

        return Inertia::render('Settings/Profile', [
            'roles' => $user->roles->pluck('name'),
            'playerBoards' => $playerBoards,
            // Not shared globally via HandleInertiaRequests: this is the only
            // page that edits it, and the skill-race page gets its own copy.
            'osrsUsername' => $user->osrs_username,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate(['nickname' => ['nullable', 'string', 'max:255']]);

        $request->user()->update(['nickname' => $data['nickname'] ?: null]);

        return back()->with('board-save', trans('profile.nickname_saved'));
    }

    /**
     * The OSRS account name, kept on its own endpoint rather than folded into
     * update() above.
     *
     * Two forms writing through one validated action is how a field gets
     * wiped: validate() returns only the keys it has rules for, so a nickname
     * save that also listed osrs_username would blank it whenever the
     * nickname form didn't send one.
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
