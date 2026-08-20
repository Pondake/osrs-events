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
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate(['nickname' => ['nullable', 'string', 'max:255']]);

        $request->user()->update(['nickname' => $data['nickname'] ?: null]);

        return back()->with('board-save', trans('profile.nickname_saved'));
    }
}
