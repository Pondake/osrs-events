<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Services\AccountDeletionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Inertia\Inertia;
use Inertia\Response;

/** Auth-method settings — linked accounts and password. See ProfileController for the display side. */
class AccountController extends Controller
{
    public function show(AccountDeletionService $deletion): Response
    {
        $user = Auth::user();

        return Inertia::render('Settings/Account', [
            'email' => $user->email,
            'hasPassword' => $user->password !== null,
            'hasDiscord' => $user->discord_id !== null,

            // What closing the account would still need decided. Loaded with
            // the page rather than behind a button: somebody weighing whether
            // to leave should be able to see what it would cost without
            // starting anything.
            'deletion' => $deletion->preflight($user),
            'deletionPhrase' => $user->deletionPhrase(),
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

        $rules = [
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ];

        // Once an account has a password, its email address IS the recovery
        // path — a reset link goes there and nowhere else. Changing it from a
        // session alone would turn any borrowed session into a permanent
        // takeover: point the address elsewhere, then ask for a reset link.
        // So it takes the password, exactly as changing the password does.
        //
        // Not asked of a Discord login, which has no password to give and
        // needs this endpoint to get an email in the first place.
        if ($user->password !== null) {
            $rules['current_password'] = ['required', 'current_password'];
        }

        $data = $request->validate($rules);

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

        // Every other session goes. Changing a password is what somebody does
        // when they think a session is not theirs, so leaving those signed in
        // would be answering the wrong question — and it takes the remember
        // cookies with it, which the middleware alone would not.
        //
        // This session survives: AuthenticateSession re-stores the new hash
        // after the response, so the person doing it is not thrown out by
        // their own action.
        Auth::logoutOtherDevices($data['password']);

        return back()->with('board-save', trans('profile.password_updated'));
    }

    /**
     * Close the account.
     *
     * Three guards, and each answers a different question:
     *
     *  - **The password**, when the account has one. Same rule as changing the
     *    email: a borrowed session must not be able to do the irreversible
     *    things. A Discord-only account has none to give, which is why this is
     *    conditional rather than required.
     *  - **The OSRS name, typed out.** The one thing every account has, that
     *    the person closing it knows and a passer-by at an unlocked laptop does
     *    not. It is deliberately not a checkbox — this deletes other people's
     *    evening as well as your own.
     *  - **A decision per owned event and team**, enforced in the service. A
     *    default would be either "silently delete somebody else's event" or
     *    "silently hand it over", and both are worse than a refused request.
     */
    public function destroy(Request $request, AccountDeletionService $deletion): RedirectResponse
    {
        $user = $request->user();

        $rules = [
            'confirmation' => ['required', 'string'],
            'events' => ['array'],
            'events.*' => ['required', 'string'],
            'teams' => ['array'],
            'teams.*' => ['required', 'string'],
        ];

        if ($user->password !== null) {
            $rules['current_password'] = ['required', 'current_password'];
        }

        $data = $request->validate($rules);

        // Compared case-insensitively and trimmed: RuneScape names are matched
        // that way everywhere else here, and failing somebody's last action on
        // a capital letter would be a poor note to end on.
        if (mb_strtolower(trim($data['confirmation'])) !== mb_strtolower($user->deletionPhrase())) {
            throw ValidationException::withMessages([
                'confirmation' => trans('profile.delete_confirmation_mismatch'),
            ]);
        }

        try {
            $deletion->delete($user, $data['events'] ?? [], $data['teams'] ?? []);
        } catch (InvalidArgumentException) {
            // The page was stale — an event was created in another tab after
            // it rendered. Say so rather than deleting on a half-answered form.
            throw ValidationException::withMessages([
                'confirmation' => trans('profile.delete_needs_decisions'),
            ]);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('board-save', trans('profile.account_deleted'));
    }
}
