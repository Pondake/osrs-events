<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserGuild;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Ported from the old NestJS AuthService::handleDiscordCallback() /
 * UsersService::upsertFromDiscord() / UsersService::syncGuilds() (see
 * stale/backend/src/auth/auth.service.ts and stale/backend/src/users/users.service.ts) —
 * same scope, same guild-sync-is-non-fatal behavior, same new-user-gets-PLAYER-role
 * rule. The old backend issued its own JWT in a cookie; this uses Laravel's
 * ordinary session auth instead (Auth::login()), since Inertia pages are
 * already behind the `web` session middleware group.
 */
class DiscordController extends Controller
{
    public function redirect(): SymfonyResponse
    {
        // setScopes(), not scopes() — the latter MERGES with the discord
        // driver's own default scope list (which includes `email`, unwanted
        // here) rather than replacing it. Confirmed by curling this route:
        // ->scopes() produced "identify email guilds" in the redirect URL.
        //
        // withConsent(), which is the driver's own switch, and NOT
        // ->with(['prompt' => 'consent']): getCodeFields() overwrites prompt
        // AFTER merging custom parameters, so passing it that way is
        // silently discarded. A test asserts the value on the real URL,
        // because the first attempt here looked completely correct and
        // changed nothing.
        //
        // It matters because the driver's default is prompt=none, which makes
        // Discord silently reuse an EXISTING authorisation: an account that
        // first logged in before `guilds` was requested gets a token without
        // it, /users/@me/guilds 401s, syncGuilds is deliberately non-fatal,
        // and that account is left with zero servers forever and nothing on
        // screen explaining why.
        //
        // Wrapped in Inertia::location() rather than returned bare. Every
        // button that starts this flow is rendered by @nuxt/ui, which routes
        // its links through Inertia — so this arrives as an XHR, and a bare
        // 302 makes the BROWSER follow it to discord.com from within that
        // XHR. That is a cross-origin request carrying Inertia's own
        // X-Inertia and X-XSRF-TOKEN headers, so it preflights, and Discord
        // (correctly) does not allow those headers: the whole login dies in
        // CORS with a network error and no server-side trace at all.
        // Inertia::location() answers an XHR with 409 + X-Inertia-Location,
        // which the client turns into a real navigation, and still returns a
        // plain 302 for a normal request.
        return Inertia::location(
            Socialite::driver('discord')
                ->setScopes(['identify', 'guilds'])
                ->withConsent()
                ->redirect()
        );
    }

    /**
     * Same OAuth kickoff as redirect(), but for an already-logged-in user
     * (an email/password account) attaching Discord to their existing
     * account rather than logging in. The only difference is this one
     * session value — callback() checks it to decide which path to take.
     */
    public function connect(Request $request): SymfonyResponse
    {
        $request->session()->put('discord_link_user_id', $request->user()->id);

        // Same Inertia::location() reasoning as redirect() above — this one
        // is triggered from a button in account settings, which is every bit
        // as much an Inertia visit.
        return Inertia::location(
            Socialite::driver('discord')
                ->setScopes(['identify', 'guilds'])
                ->withConsent()
                ->redirect()
        );
    }

    public function disconnect(Request $request): RedirectResponse
    {
        $user = $request->user();

        // A user must always have at least one way back in — refuse to
        // strip Discord off an account that can't log in without it. Both
        // halves matter: the password is the credential, the email is the
        // only way to recover it (see AccountController::updatePassword).
        abort_unless($user->password !== null && $user->email !== null, 400, 'Set an email and password before disconnecting Discord.');

        // Freeze the Discord username into nickname first if nothing else
        // was ever set — otherwise a user who never customized their
        // nickname loses BOTH fields at once and displayName() (nickname ??
        // discord_username) returns null. Caught live: the header rendered
        // completely nameless immediately after disconnecting.
        if ($user->nickname === null) {
            $user->nickname = $user->discord_username;
        }

        $user->update(['discord_id' => null, 'discord_username' => null, 'avatar_url' => null, 'nickname' => $user->nickname]);

        return back()->with('board-save', trans('profile.discord_disconnected'));
    }

    public function callback(Request $request): RedirectResponse
    {
        // Pulled before anything can fail, because it decides where a failed
        // round-trip sends the user back to — and because leaving it behind
        // would make the NEXT plain login silently try to link instead.
        $linkingUserId = $request->session()->pull('discord_link_user_id');
        $failureRedirect = $linkingUserId ? '/settings/account' : '/login';

        // Discord answers a cancelled authorisation with ?error=access_denied
        // rather than a code — no exception, just a callback with nothing in
        // it. Socialite would go on to POST a null code to the token endpoint
        // and turn a user's own "no thanks" into a 500.
        if ($request->query('error')) {
            return redirect($failureRedirect)->with(
                'board-save-error',
                trans($request->query('error') === 'access_denied' ? 'auth.discord_cancelled' : 'auth.discord_failed'),
            );
        }

        try {
            $discordUser = Socialite::driver('discord')->user();
        } catch (\Throwable $e) {
            // Anything from here is a dead OAuth round-trip, and there are
            // several ordinary ways to get one: closing the Discord app or
            // browser tab mid-flow, letting the page sit until the code
            // expires, or a refresh that replays an already-spent code —
            // all of which come back as a 400 from Discord's token endpoint
            // (ClientException) or as an InvalidStateException from a
            // session that no longer matches. Reported from staging as an
            // unhandled 500 stack trace on a phone screen.
            Log::warning('Discord OAuth callback failed: '.$e->getMessage());

            return redirect($failureRedirect)->with('board-save-error', trans('auth.discord_failed'));
        }

        // socialiteproviders/discord's formatAvatar() already returns the
        // full CDN URL (Provider.php) — re-wrapping it in another
        // "https://cdn.discordapp.com/avatars/{id}/{avatar}.png" template,
        // as this used to, produced a malformed doubled-up URL
        // ("...avatars/ID/https://cdn.discordapp.com/avatars/ID/hash.jpg.png"),
        // confirmed live in the database for a real logged-in user.
        $avatarUrl = $discordUser->avatar ?: null;

        // Discord's API also returns global_name (the display name shown
        // everywhere in Discord's own UI, e.g. "Marthijn") separately from
        // username (the @handle, e.g. "mbeetje") — but the Socialite
        // provider's mapUserToObject() maps BOTH `name` and `nickname` to
        // username only, never surfacing global_name. Pulled from the raw
        // API payload instead so a new user's nickname defaults to what
        // they'd actually recognize as their own name, not their handle.
        $globalName = $discordUser->user['global_name'] ?? null;
        $discordId = (string) $discordUser->id;
        $discordUsername = $discordUser->nickname ?? $discordUser->name;

        if ($linkingUserId) {
            return $this->linkToExistingUser($linkingUserId, $discordId, $discordUsername, $avatarUrl, $discordUser->token);
        }

        // Discord is two things at once: a way to sign in and a way to get an
        // account without ever seeing a registration form. While the site is
        // locked only the first is on offer — a shut door that hands out keys
        // is not shut. An account that already exists signs in as normal, so
        // whoever is building the site is not locked out of their own login.
        if ($this->registrationClosed() && ! User::where('discord_id', $discordId)->exists()) {
            return redirect('/login')->with('board-save-error', trans('lock.registration_closed'));
        }

        $user = $this->upsertFromDiscord(
            discordId: $discordId,
            discordUsername: $discordUsername,
            avatarUrl: $avatarUrl,
            globalName: $globalName,
        );

        // Non-fatal: guild sync failure should not block login — matches the
        // old NestJS service's try/catch around this same call.
        try {
            $this->syncGuilds($user, $discordUser->token);
        } catch (\Throwable $e) {
            Log::warning("Guild sync failed for user {$user->id}: {$e->getMessage()}");
        }

        Auth::login($user, remember: true);

        // Session fixation prevention — this was never here before; a
        // pre-login session ID stayed valid post-login. Retrofitted while
        // adding the email/password path below, which needed the same fix.
        $request->session()->regenerate();

        return redirect()->intended('/boards');
    }

    /**
     * Attach a Discord identity to an already-authenticated user's existing
     * account, instead of the normal find-or-create-then-login flow. Guards
     * against claiming a Discord account that's already linked to a
     * *different* user — that would otherwise silently merge two identities
     * onto one row via upsertFromDiscord()'s discord_id-keyed update.
     */
    private function linkToExistingUser(string $userId, string $discordId, string $discordUsername, ?string $avatarUrl, string $accessToken): RedirectResponse
    {
        $claimedBy = User::where('discord_id', $discordId)->first();
        if ($claimedBy && $claimedBy->id !== $userId) {
            return redirect('/settings/account')->with('board-save-error', trans('profile.discord_already_linked'));
        }

        $user = User::findOrFail($userId);
        $user->update([
            'discord_id' => $discordId,
            'discord_username' => $discordUsername,
            'avatar_url' => $avatarUrl ?? $user->avatar_url,
        ]);

        try {
            $this->syncGuilds($user, $accessToken);
        } catch (\Throwable $e) {
            Log::warning("Guild sync failed for user {$user->id}: {$e->getMessage()}");
        }

        return redirect('/settings/account')->with('board-save', trans('profile.discord_connected'));
    }

    /**
     * Whether the site is shut to newcomers.
     *
     * Not `EnsureSiteUnlocked`'s own check: that one asks whether THIS
     * request may pass, and a request arriving here has already been let
     * through as a sign-in route. This asks the narrower question the lock is
     * actually for.
     */
    private function registrationClosed(): bool
    {
        return (bool) Setting::get('site_lock_enabled');
    }

    private function upsertFromDiscord(string $discordId, string $discordUsername, ?string $avatarUrl, ?string $globalName): User
    {
        return DB::transaction(function () use ($discordId, $discordUsername, $avatarUrl, $globalName) {
            $isNewUser = ! User::where('discord_id', $discordId)->exists();

            $user = User::updateOrCreate(
                ['discord_id' => $discordId],
                ['discord_username' => $discordUsername, 'avatar_url' => $avatarUrl],
            );

            if ($isNewUser) {
                $playerRole = Role::firstOrCreate(
                    ['name' => 'PLAYER'],
                    ['description' => 'Standaard spelerrol'],
                );
                $user->assignRole($playerRole);

                // Only on first creation — a returning user may have already
                // set their own custom nickname (Profile.vue), which a login
                // must never silently overwrite.
                if ($globalName && $globalName !== $discordUsername) {
                    $user->update(['nickname' => $globalName]);
                }
            }

            return $user;
        });
    }

    /**
     * Replace the user's cached Discord guild memberships — delete-all +
     * re-insert in a transaction, same as the old syncGuilds().
     */
    private function syncGuilds(User $user, string $discordAccessToken): void
    {
        $response = Http::withToken($discordAccessToken)
            ->get('https://discord.com/api/users/@me/guilds')
            ->throw();

        $guilds = $response->json();

        DB::transaction(function () use ($user, $guilds) {
            UserGuild::where('user_id', $user->id)->delete();

            UserGuild::insert(array_map(fn ($guild) => [
                'id' => (string) str()->uuid(),
                'user_id' => $user->id,
                'guild_id' => $guild['id'],
                'guild_name' => $guild['name'],
                'guild_icon' => $guild['icon'] ?? null,
                'synced_at' => now(),
            ], $guilds));
        });
    }
}
