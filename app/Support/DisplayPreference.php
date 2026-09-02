<?php

namespace App\Support;

/**
 * The display choices an account can make, and what they are when unset.
 *
 * Same shape as NotificationCategory: a catalogue the settings page renders,
 * the validator whitelists against, and every reader takes its default from.
 * A setting that is not in here does not exist — a stray key in the stored
 * JSON is ignored rather than honoured.
 */
class DisplayPreference
{
    /** Walk your own piece across the board after a roll. */
    public const OWN_MOVES = 'animate_own_moves';

    /** Walk everybody else's, when their roll arrives on the live stream. */
    public const OTHER_MOVES = 'animate_other_moves';

    /**
     * Both on by default. The animation is how a snake reads as something
     * that happened TO you rather than a number changing, and somebody who
     * has never seen it cannot know to turn it on.
     *
     * @var array<string, bool>
     */
    public const ALL = [
        self::OWN_MOVES => true,
        self::OTHER_MOVES => true,
    ];

    /** @return array<string, bool> every setting, stored value or default. */
    public static function resolve(?array $stored): array
    {
        $stored ??= [];

        $resolved = [];

        foreach (self::ALL as $key => $default) {
            $resolved[$key] = is_bool($stored[$key] ?? null) ? $stored[$key] : $default;
        }

        return $resolved;
    }
}
