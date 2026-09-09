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
     * The drifting pattern behind the landing and content pages.
     *
     * Not a board setting at all, which is why the settings page renders it
     * apart from the two above: it is on pages a signed-out visitor sees, and
     * for them there is no stored answer — only this default. Reduced motion
     * already stills it without this switch; this one removes it.
     */
    public const BACKGROUND = 'animate_background';

    /**
     * On by default except the override: somebody who has never seen the
     * animation cannot know to turn it on. PLAY_WHEN_REDUCED is off for the
     * opposite reason — a stated accessibility preference wins until its
     * owner says otherwise.
     *
     * @var array<string, bool>
     */
    public const ALL = [
        self::OWN_MOVES => true,
        self::OTHER_MOVES => true,
        self::BACKGROUND => true,
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

    /**
     * The switches about the site itself rather than about a board.
     *
     * Its own group because the card it lands in has its own heading: the
     * movement switches are answered while thinking about a roll, and this
     * one is answered while looking at a page.
     *
     * @var array<int, string>
     */
    public const AMBIENT = [
        self::BACKGROUND,
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
