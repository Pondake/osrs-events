<?php

namespace App\Support;

use App\Models\Event;

/**
 * Everything this app can post into a host's Discord channel, in one table.
 *
 * Same shape and same reasoning as NotificationCategory: the settings tab
 * renders it, the validator whitelists against it, and DiscordAnnouncer
 * checks it before a request leaves. Adding a trigger is a row here.
 *
 * `types` is enforcement, not display. A trigger that cannot fire for an
 * event type must not be offered on it — an unticked box for something that
 * was never going to happen teaches a host that the list is decorative.
 *
 * `default` follows how often a trigger can fire, not how interesting it is.
 * Anything that can go off more than once a day ships off, for the reason
 * the push catalogue spells out: one chatty trigger is how a channel gets
 * muted, and a muted channel takes the rare important posts with it.
 */
final class AnnouncementTrigger
{
    /** The six host actions. Their keys ARE EventStatusChanged's constants. */
    public const PAUSED = 'paused';

    public const RESUMED = 'resumed';

    public const ENDED = 'ended';

    public const REOPENED = 'reopened';

    public const CANCELLED = 'cancelled';

    public const RESTORED = 'restored';

    /** Somebody got home — boards only. */
    public const FINISH = 'finish';

    /** The finish that ended it, under the STOP rule. */
    public const WON = 'won';

    /** The closing standings of a metric race. */
    public const RACE_FINAL = 'race_final';

    /** Position one changed hands. */
    public const RACE_LEAD = 'race_lead';

    /** Once a day, where the race stands. */
    public const RACE_DIGEST = 'race_digest';

    private const BOARDS = ['SNAKES_LADDERS', 'BINGO'];

    private const RACES = ['SKILL_RACE', 'DROP_RACE'];

    /**
     * key => [types, default, icon]
     *
     * @var array<string, array{types: list<string>|null, default: bool, icon: string}>
     */
    public const ALL = [
        // Null types means every type: a host pausing an event is the same
        // news whatever is being paused.
        self::PAUSED => ['types' => null, 'default' => true, 'icon' => 'i-lucide-pause'],
        self::RESUMED => ['types' => null, 'default' => true, 'icon' => 'i-lucide-play'],
        self::ENDED => ['types' => null, 'default' => true, 'icon' => 'i-lucide-flag'],
        self::REOPENED => ['types' => null, 'default' => true, 'icon' => 'i-lucide-rotate-ccw'],
        self::CANCELLED => ['types' => null, 'default' => true, 'icon' => 'i-lucide-x'],
        self::RESTORED => ['types' => null, 'default' => true, 'icon' => 'i-lucide-undo-2'],

        self::FINISH => ['types' => self::BOARDS, 'default' => true, 'icon' => 'i-lucide-trophy'],
        self::WON => ['types' => self::BOARDS, 'default' => true, 'icon' => 'i-lucide-crown'],

        // Once per event, so it cannot become noise however close the race is.
        self::RACE_FINAL => ['types' => self::RACES, 'default' => true, 'icon' => 'i-lucide-list-ordered'],
        // A close race swaps its lead repeatedly; the throttle holds it to one
        // an hour and the default holds it to nobody until a host asks.
        self::RACE_LEAD => ['types' => self::RACES, 'default' => false, 'icon' => 'i-lucide-trending-up'],
        // Predictable rather than frequent, but on a quiet day it posts
        // nothing new — which is its own way of getting a channel muted.
        self::RACE_DIGEST => ['types' => self::RACES, 'default' => false, 'icon' => 'i-lucide-calendar-days'],
    ];

    /** How long one event must wait before the same trigger fires again. */
    public const THROTTLE = [
        self::RACE_LEAD => 3600,
    ];

    /** @return list<string> the triggers that can fire for this event type */
    public static function forType(string $type): array
    {
        return array_keys(array_filter(
            self::ALL,
            fn (array $meta) => $meta['types'] === null || in_array($type, $meta['types'], true),
        ));
    }

    /** @return list<string> what a host gets when nobody has chosen yet */
    public static function defaultsFor(string $type): array
    {
        return array_values(array_filter(
            self::forType($type),
            fn (string $key) => self::ALL[$key]['default'],
        ));
    }

    /**
     * The tab's contents for one event: what it can send, and what it does.
     *
     * Here rather than in a controller because two of them render this form —
     * the event page and the admin list — and two copies of "null means the
     * defaults" is exactly the drift this catalogue exists to prevent.
     *
     * @return array{chosen: list<string>, available: list<array{key: string, icon: string}>}
     */
    public static function settingsFor(Event $event): array
    {
        return [
            'chosen' => $event->discord_announcements ?? self::defaultsFor($event->type),
            'available' => array_map(
                fn (string $key) => ['key' => $key, 'icon' => self::ALL[$key]['icon']],
                self::forType($event->type),
            ),
        ];
    }

    public static function appliesTo(string $trigger, Event $event): bool
    {
        $meta = self::ALL[$trigger] ?? null;

        return $meta !== null
            && ($meta['types'] === null || in_array($event->type, $meta['types'], true));
    }
}
