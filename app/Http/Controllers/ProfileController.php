<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

/** Ported from UsersService::updateProfile() + the profile page's own player-boards query. */
class ProfileController extends Controller
{
    public function show(): Response
    {
        $user = Auth::user()->load('userRoles.role');

        $playerBoards = $user->playerBoards()
            ->with(['board', 'completedTiles'])
            ->orderByDesc('created_at')
            ->get()
            ->filter(fn ($pb) => $pb->board !== null)
            ->values();

        return Inertia::render('Profile', [
            'roles' => $user->userRoles->pluck('role.name'),
            'playerBoards' => $playerBoards,
            // Account-settings tab needs to know which auth methods are
            // already attached — not exposed via HandleInertiaRequests'
            // globally-shared auth.user since no other page needs it.
            'email' => $user->email,
            'hasPassword' => $user->password !== null,
            'hasDiscord' => $user->discord_id !== null,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate(['nickname' => ['nullable', 'string', 'max:255']]);

        $request->user()->update(['nickname' => $data['nickname'] ?: null]);

        return back()->with('board-save', 'Profile updated.');
    }

    /**
     * Set (Discord-only account, no current password to check) or change
     * (email account, current_password required) the account's password —
     * same form either way, the current-password requirement is the only
     * thing that differs.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $request->user();

        $rules = [
            'password' => ['required', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()],
        ];
        if ($user->password !== null) {
            $rules['current_password'] = ['required', 'current_password'];
        }

        $data = $request->validate($rules);

        $user->update(['password' => $data['password']]);

        return back()->with('board-save', trans('profile.password_updated'));
    }
}
