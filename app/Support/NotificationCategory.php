<?php

namespace App\Support;

/**
 * Every kind of push this app sends, in one table.
 *
 * The catalogue is the contract between four places that otherwise drift:
 * the settings page renders it, the preference validator whitelists it, the
 * senders look up their own default from it, and the throttle reads its
 * window from it. Adding a tenth category means adding a row here and
 * nothing else structural.
 *
 * **`default` is the whole design.** Permission to notify is not permission
 * to notify about everything: a category that fires often enough to annoy is
 * the reason people revoke permission outright, and revoking takes the rare
 * important ones with it. So anything high-frequency ships off, and the user
 * turns it on if they want it — never the other way around.
 *
 * `throttle` is a per-entity floor in seconds, not a global rate: two
 * different events may both notify within the minute, but one event cannot
 * notify twice about the same thing. Zero means the trigger is already rare
 * by its nature (an event ends once) and needs no floor.
 */
final class NotificationCategory
{
    /** A claim you submitted was approved or rejected by the host. */
    public const CLAIM_REVIEWED = 'claim_reviewed';

    /** Claims are sitting in your review queue. Hosts only. */
    public const REVIEW_QUEUE = 'review_queue';

    /** An event you joined is about to start, or about to end. */
    public const EVENT_SCHEDULE = 'event_schedule';

    /** An event you joined finished — here is where you placed. */
    public const EVENT_RESULT = 'event_result';

    /** A host paused, resumed, cancelled or restored an event you joined. */
    public const EVENT_STATUS = 'event_status';

    /** Your event's standings stopped updating. Hosts only. */
    public const STANDINGS_HEALTH = 'standings_health';

    /** Your dice rolls reset and you have a board in progress. */
    public const ROLLS_AVAILABLE = 'rolls_available';

    /** Somebody took a podium place off you. */
    public const RANK_CHANGE = 'rank_change';

    /** A teammate scored on a card you share. */
    public const TEAM_ACTIVITY = 'team_activity';

    /**
     * An admin's nudge that your OSRS username isn't syncing — sent by a
     * person pressing a button on the diagnostics page, not by a scheduled
     * job. See DiagnosticsController::nudgeStandingsFailure().
     */
    public const OSRS_USERNAME_REMINDER = 'osrs_username_reminder';

    /**
     * key => [audience, default, throttle seconds, icon]
     *
     * `audience` is display grouping on the settings page, not enforcement —
     * a host is also a player, and every send checks the real relationship
     * (participant, event editor, team member) at the point it sends.
     */
    public const ALL = [
        self::CLAIM_REVIEWED => ['audience' => 'player', 'default' => true, 'throttle' => 0, 'icon' => 'i-lucide-check-check'],
        self::EVENT_SCHEDULE => ['audience' => 'player', 'default' => true, 'throttle' => 0, 'icon' => 'i-lucide-hourglass'],
        self::EVENT_RESULT => ['audience' => 'player', 'default' => true, 'throttle' => 0, 'icon' => 'i-lucide-flag'],
        self::EVENT_STATUS => ['audience' => 'player', 'default' => true, 'throttle' => 0, 'icon' => 'i-lucide-pause'],
        self::ROLLS_AVAILABLE => ['audience' => 'player', 'default' => false, 'throttle' => 43200, 'icon' => 'i-lucide-dice-6'],
        self::RANK_CHANGE => ['audience' => 'player', 'default' => false, 'throttle' => 3600, 'icon' => 'i-lucide-trending-down'],
        self::TEAM_ACTIVITY => ['audience' => 'player', 'default' => false, 'throttle' => 3600, 'icon' => 'i-lucide-users'],
        self::REVIEW_QUEUE => ['audience' => 'host', 'default' => true, 'throttle' => 3600, 'icon' => 'i-lucide-gavel'],
        self::STANDINGS_HEALTH => ['audience' => 'host', 'default' => true, 'throttle' => 86400, 'icon' => 'i-lucide-triangle-alert'],
        // Not high-frequency by nature — an admin has to press the button —
        // so this defaults on like the other important player-facing
        // categories, rather than off the way an automated, frequent one would.
        self::OSRS_USERNAME_REMINDER => ['audience' => 'player', 'default' => true, 'throttle' => 86400, 'icon' => 'i-lucide-user-round-x'],
    ];

    /** @return array<int, string> */
    public static function keys(): array
    {
        return array_keys(self::ALL);
    }

    public static function exists(string $key): bool
    {
        return array_key_exists($key, self::ALL);
    }

    /**
     * What a user gets when they have never touched the settings page.
     *
     * An unknown key answers false rather than throwing. Preferences are
     * stored as JSON that outlives the code that wrote it: a category
     * removed in a later release leaves rows behind referring to it, and a
     * throw here would turn old data into a 500 on the settings page.
     */
    public static function defaultFor(string $key): bool
    {
        return self::ALL[$key]['default'] ?? false;
    }

    /** Seconds one entity must stay quiet for after notifying about it. */
    public static function throttleFor(string $key): int
    {
        return self::ALL[$key]['throttle'] ?? 0;
    }

    /**
     * The catalogue as the settings page needs it — label and description
     * resolved, so the Vue side renders a list instead of knowing nine keys.
     */
    public static function forDisplay(): array
    {
        return collect(self::ALL)
            ->map(fn (array $meta, string $key) => [
                'key' => $key,
                'audience' => $meta['audience'],
                'default' => $meta['default'],
                'icon' => $meta['icon'],
                'label' => trans("notifications.category_{$key}"),
                'description' => trans("notifications.category_{$key}_desc"),
            ])
            ->values()
            ->all();
    }
}
