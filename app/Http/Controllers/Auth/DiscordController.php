<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Models\UserGuild;
use App\Models\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

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
    public function redirect(): RedirectResponse
    {
        // setScopes(), not scopes() — the latter MERGES with the discord
        // driver's own default scope list (which includes `email`, unwanted
        // here) rather than replacing it. Confirmed by curling this route:
        // ->scopes() produced "identify email guilds" in the redirect URL.
        return Socialite::driver('discord')
            ->setScopes(['identify', 'guilds'])
            ->redirect();
    }

    public function callback(): RedirectResponse
    {
        $discordUser = Socialite::driver('discord')->user();

        $avatarUrl = $discordUser->avatar
            ? "https://cdn.discordapp.com/avatars/{$discordUser->id}/{$discordUser->avatar}.png"
            : null;

        $user = $this->upsertFromDiscord(
            discordId: (string) $discordUser->id,
            discordUsername: $discordUser->nickname ?? $discordUser->name,
            avatarUrl: $avatarUrl,
        );

        // Non-fatal: guild sync failure should not block login — matches the
        // old NestJS service's try/catch around this same call.
        try {
            $this->syncGuilds($user, $discordUser->token);
        } catch (\Throwable $e) {
            Log::warning("Guild sync failed for user {$user->id}: {$e->getMessage()}");
        }

        Auth::login($user, remember: true);

        return redirect()->intended('/boards');
    }

    private function upsertFromDiscord(string $discordId, string $discordUsername, ?string $avatarUrl): User
    {
        return DB::transaction(function () use ($discordId, $discordUsername, $avatarUrl) {
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
                UserRole::firstOrCreate(['user_id' => $user->id, 'role_id' => $playerRole->id]);
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
