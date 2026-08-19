<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

/** Auth-method settings — linked accounts and password. See ProfileController for the display side. */
class AccountController extends Controller
{
    public function show(): Response
    {
        $user = Auth::user();

        return Inertia::render('Settings/Account', [
            'email' => $user->email,
            'hasPassword' => $user->password !== null,
            'hasDiscord' => $user->discord_id !== null,
        ]);
    }

    /**
     * Set (no password yet — a Discord-only account) or change (has one —
     * current_password required) the account password. Same form either way.
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
