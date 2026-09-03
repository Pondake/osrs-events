<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Display-side settings (name, roles). Account-side settings — auth methods,
 * password — live in AccountController next to this one; both render inside
 * Components/SettingsLayout.vue's sidebar shell.
 *
 * Used to also list every event you host or play, built from Event rows with
 * a hand-rolled progress calculation. Removed 2026-08-26, not fixed in place:
 * `/my-events` (BoardController::mine()) already answers the same question
 * with real board previews and hosted/playing filters across every event
 * type, reachable from the header's Events → My events nav item. A second,
 * simpler list here duplicating the same data was two places that could
 * disagree about it, not two features — this page now just links to that one
 * instead of rebuilding it.
 *
 * The OSRS account name used to live here too. It moved to
 * ConnectionsController on 2026-09-03: it is the one field that talks to an
 * outside service, which is that page's subject rather than this one's.
 */
class ProfileController extends Controller
{
    public function show(): Response
    {
        $user = Auth::user()->load('roles:id,name');

        return Inertia::render('Settings/Profile', [
            'roles' => $user->roles->pluck('name'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate(['nickname' => ['nullable', 'string', 'max:255']]);

        $request->user()->update(['nickname' => $data['nickname'] ?: null]);

        return back()->with('board-save', trans('profile.nickname_saved'));
    }
}
