<?php

namespace App\Support;

/**
 * Discord's CDN paths, in one place.
 *
 * Extracted from BoardController on 2026-09-02, when a second caller appeared:
 * a team's linked-server icon is now the fallback avatar for a team that has
 * no icon of its own, so the same hash has to become a URL in two places.
 */
class DiscordCdn
{
    /**
     * A guild's icon, or null when the server has none — a real and common
     * state, since a guild icon is optional.
     */
    public static function guildIcon(?string $guildId, ?string $hash, int $size = 64): ?string
    {
        if ($guildId === null || $guildId === '' || $hash === null || $hash === '') {
            return null;
        }

        // An animated icon's hash is prefixed `a_`; asking for it as .png
        // works but serves the still frame, which is the cheaper request.
        $extension = str_starts_with($hash, 'a_') ? 'gif' : 'png';

        return "https://cdn.discordapp.com/icons/{$guildId}/{$hash}.{$extension}?size={$size}";
    }
}
