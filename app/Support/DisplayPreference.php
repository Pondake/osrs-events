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
     * Animate anyway, on a machine whose OS asks for reduced motion.
     *
     * Off by default and deliberately its own flag, because the browser's
     * `prefers-reduced-motion` otherwise wins silently: the board skipped
     * every animation while the two switches above still read "on", which is
     * a settings page that lies. Reported from a work laptop that had the
     * Windows animation effects turned off without its owner knowing.
     *
     * Not folded into OWN_MOVES being true. It looks free — the column is
     * null until somebody touches it, so "stored true" could pass for "asked
     * for it" — but the update action writes every key at once, so switching
     * *other* people's moves off would silently force your own back on
     * against an accessibility preference. Consent to override that has to be
     * its own answer.
     */
    public const PLAY_WHEN_REDUCED = 'play_when_reduced_motion';

    /**
     * The movement switches are on by default: somebody who has never seen
     * the animation cannot know to turn it on. The override is off for the
     * opposite reason — a stated accessibility preference wins until its
     * owner says otherwise.
     *
     * @var array<string, bool>
     */
    public const ALL = [
        self::OWN_MOVES => true,
        self::OTHER_MOVES => true,
        self::PLAY_WHEN_REDUCED => false,
    ];

    /**
     * The switches about what to animate, without the override.
     *
     * The settings page renders these in a plain list and the override
     * separately, because the override is only worth showing on a machine
     * that actually asks for reduced motion — a question nobody else has.
     *
     * @var array<int, string>
     */
    public const MOVEMENT = [
        self::OWN_MOVES,
        self::OTHER_MOVES,
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
