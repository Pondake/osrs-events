<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Email/password registration — the non-Discord auth path. Discord OAuth
 * stays the primary flow (DiscordController); this is an alternative, not a
 * replacement, for anyone who doesn't want to link a Discord account.
 */
class RegisteredUserController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Register', [
            'registrationOpen' => Setting::get('registration_open'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        // Enforced here, not only by hiding the form: the endpoint is
        // reachable directly, so the UI state is a courtesy and this is the
        // actual gate.
        abort_unless(Setting::get('registration_open'), 403, 'Registration is currently closed.');

        $data = $request->validate([
            // 'nickname', not 'name' — this becomes the user's displayName()
            // the same way a Discord signup's discord_username does, and
            // discord_id/discord_username stay null for this path (see the
            // migration), so nickname is the ONLY thing displayName() can
            // fall back to. Required here specifically so that invariant
            // can never break, not just conventionally sensible.
            'nickname' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()],
        ]);

        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'nickname' => $data['nickname'],
                'email' => $data['email'],
                'password' => $data['password'],
            ]);

            $playerRole = Role::firstOrCreate(
                ['name' => 'PLAYER'],
                ['description' => 'Standaard spelerrol'],
            );
            UserRole::firstOrCreate(['user_id' => $user->id, 'role_id' => $playerRole->id]);

            return $user;
        });

        Auth::login($user, remember: true);

        // Session fixation prevention — issue a fresh session ID post-login,
        // same as AuthenticatedSessionController::store() and now retrofitted
        // onto DiscordController::callback() too.
        $request->session()->regenerate();

        return redirect()->intended('/boards');
    }
}
