<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
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
        $user = Auth::user()->load('userRoles.role');

        $playerBoards = $user->playerBoards()
            ->with(['board', 'completedTiles'])
            ->orderByDesc('created_at')
            ->get()
            ->filter(fn ($pb) => $pb->board?->event !== null)
            ->values();

        return Inertia::render('Settings/Profile', [
            'roles' => $user->userRoles->pluck('role.name'),
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
    public function updateOsrsUsername(Request $request): RedirectResponse
    {
        $data = $request->validate([
            // Jagex allows letters, digits, spaces, underscores and hyphens,
            // up to 12 characters. Validating here means a name that cannot
            // exist never reaches the hiscores lookup as a 404.
            'osrs_username' => ['nullable', 'string', 'max:12', 'regex:/^[a-zA-Z0-9 _-]+$/'],
        ]);

        $request->user()->update(['osrs_username' => trim($data['osrs_username'] ?? '') ?: null]);

        return back()->with('board-save', trans('profile.osrs_username_saved'));
    }
}
