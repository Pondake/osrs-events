<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserGuild;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Everything that happens to an account once Discord has said who somebody
 * is — shared by DiscordController, which does the OAuth round trip, and
 * SiteLockController, which finishes a signup the pre-launch door
 * interrupted.
 *
 * That second caller is the reason this is a service at all. A stranger
 * arriving through Discord while the door is shut is not refused any more:
 * the identity Discord just handed back is parked here, they are sent to the
 * password screen, and the account is created the moment the password
 * matches. Without somewhere to keep it, the only alternative was to throw
 * the identity away and walk them through Discord a second time after the
 * unlock — an extra consent screen for a detour the app caused.
 */
class DiscordAccountService
{
    /** Where a signup interrupted by the pre-launch door waits. */
    public const PENDING_KEY = 'discord_pending_signup';

    /**
     * How long that wait may last. Long enough to read the password out of
     * a Discord message, short enough that a machine left open on the lock
     * screen overnight does not still hold a login on the next visitor's
     * behalf.
     */
    private const PENDING_MINUTES = 15;

    /**
     * Park a verified Discord identity until the shared password is
     * entered. Server-side only — the session lives in the database, and
     * nothing here is ever written by the browser.
     */
    public function rememberPending(Request $request, array $identity): void
    {
        $request->session()->put(self::PENDING_KEY, [
            ...$identity,
            'expires_at' => now()->addMinutes(self::PENDING_MINUTES)->timestamp,
        ]);
    }

    /** The Discord name waiting on a password, or null when none is. */
    public function pendingName(Request $request): ?string
    {
        $pending = $request->session()->get(self::PENDING_KEY);

        if (! is_array($pending) || $pending['expires_at'] < now()->timestamp) {
            return null;
        }

        return $pending['discord_username'];
    }

    /**
     * Finish a parked signup, if there is one that hasn't gone stale. The
     * entry is pulled either way: an expired one is spent, not retried.
     */
    public function completePending(Request $request): ?User
    {
        $pending = $request->session()->pull(self::PENDING_KEY);

        if (! is_array($pending) || $pending['expires_at'] < now()->timestamp) {
            return null;
        }

        return $this->signIn($request, $pending);
    }

    /**
     * Create or refresh the account behind a Discord identity and log it in.
     *
     * @param  array{discord_id: string, discord_username: string, avatar_url: ?string, global_name: ?string, token: string}  $identity
     */
    public function signIn(Request $request, array $identity): User
    {
        $user = $this->upsert(
            discordId: $identity['discord_id'],
            discordUsername: $identity['discord_username'],
            avatarUrl: $identity['avatar_url'],
            globalName: $identity['global_name'],
        );

        // Non-fatal: guild sync failure should not block login — matches the
        // old NestJS service's try/catch around this same call.
        try {
            $this->syncGuilds($user, $identity['token']);
        } catch (\Throwable $e) {
            Log::warning("Guild sync failed for user {$user->id}: {$e->getMessage()}");
        }

        Auth::login($user, remember: true);

        // Session fixation prevention — this was never here before; a
        // pre-login session ID stayed valid post-login. Retrofitted while
        // adding the email/password path, which needed the same fix.
        $request->session()->regenerate();

        return $user;
    }

    public function upsert(string $discordId, string $discordUsername, ?string $avatarUrl, ?string $globalName): User
    {
        return DB::transaction(function () use ($discordId, $discordUsername, $avatarUrl, $globalName) {
            $isNewUser = ! User::where('discord_id', $discordId)->exists();

            $user = User::updateOrCreate(
                ['discord_id' => $discordId],
                ['discord_username' => $discordUsername, 'avatar_url' => $avatarUrl],
            );

            if ($isNewUser) {
                $user->grantStarterAccess();

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
    public function syncGuilds(User $user, string $discordAccessToken): void
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
