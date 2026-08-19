<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
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
     * A Discord-only account has no email at all (the OAuth scopes are
     * identify+guilds, deliberately not email — see DiscordController), so
     * this is how such a user gets one. Without it they could never use the
     * forgot-password flow, which is the whole reason a password is allowed
     * to exist on an account in the first place.
     */
    public function updateEmail(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ]);

        $user->update(['email' => $data['email']]);

        return back()->with('board-save', trans('profile.email_updated'));
    }

    /**
     * Set (no password yet — a Discord-only account) or change (has one —
     * current_password required) the account password. Same form either way.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $request->user();

        // Refuse to create a password that could never be recovered: reset
        // links go to the account's email, so a passworded account without
        // one is a lockout waiting to happen. The UI hides the form in this
        // state too, but that's cosmetic — this is the actual guard.
        if ($user->email === null) {
            throw ValidationException::withMessages([
                'password' => trans('profile.password_needs_email'),
            ]);
        }

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
