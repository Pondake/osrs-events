<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate(['nickname' => ['nullable', 'string', 'max:255']]);

        $request->user()->update(['nickname' => $data['nickname'] ?: null]);

        return back()->with('board-save', 'Profile updated.');
    }
}
