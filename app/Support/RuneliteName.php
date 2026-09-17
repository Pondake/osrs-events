<?php

namespace App\Support;

/**
 * How a name from the game is compared to a wiki-linked task title.
 *
 * The plugin applies the same rules to decide what to report, so a change
 * here is a change to the API contract in docs/runelite-plugin.md.
 *
 * Only a numeric suffix is dropped: "Prayer potion(4)" is the wiki's
 * "Prayer potion", but "(uncharged)", "(i)" or "(or)" name a different item.
 */
class RuneliteName
{
    public static function normalize(string $name): string
    {
        $name = strip_tags($name);
        $name = str_replace(['_', "\u{00A0}"], ' ', $name);
        $name = str_replace(["'", "\u{2019}", "\u{2018}", '`'], '', $name);
        $name = trim(preg_replace('/\s+/u', ' ', $name));
        $name = trim(preg_replace('/\s*\(\d+\)$/u', '', $name));

        return mb_strtolower($name);
    }

    /** Spaces, underscores and hyphens are the same character in an OSRS name. */
    public static function sameRsn(?string $a, ?string $b): bool
    {
        $clean = fn (?string $rsn) => mb_strtolower(trim(preg_replace('/[\s_\-]+/u', ' ', (string) $rsn)));

        return $clean($a) !== '' && $clean($a) === $clean($b);
    }
}
