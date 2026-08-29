<?php

namespace App\Support;

use App\Models\Event;

/**
 * An event flattened into the shape the pages render.
 *
 * A view model rather than the raw models: the split put size and the dice
 * limit on the board and everything else on the event, and pushing that seam
 * into every template would mean the UI has to know which half a field lives
 * in just to display it. `id` is deliberately the EVENT's — that is what the
 * URLs address.
 *
 * Extracted from BoardController when the live channels started sending it
 * too. Two builders would be two shapes, and the pages swap one for the other
 * on every push — the page cannot tell which one it is looking at, so they
 * have to be the same thing.
 *
 * `authors` carries the loaded relation as-is, so callers are expected to
 * have loaded it by column (BoardController::EVENT_WITH does): `User` hides
 * only password and remember_token, and a bare `authors.user` publishes every
 * host's email address.
 */
final class EventCard
{
    public static function for(Event $event): array
    {
        return [
            ...$event->only(['id', 'title', 'type', 'metric', 'description', 'mode', 'access_mode', 'is_listed', 'start_date', 'end_date', 'paused_at', 'pause_reason', 'standings_stale_since']),
            // 'skill' or 'boss' — decides whether a race page counts XP or
            // kills, and which i18n namespace the metric name comes from.
            // Here rather than added by the race controller alone, because
            // the live channel sends this same card and a page that lost the
            // field on the first push would stop knowing what it is counting.
            'metricKind' => $event->metricKind(),
            'size' => $event->board?->size,
            'dice_roll_limit' => $event->board?->dice_roll_limit,
            // Whether a tile claim needs a host's sign-off before it counts
            // — the S&L half of the same setting bingo's card carries.
            'requires_approval' => $event->board?->requires_approval,
            // Bingo's grid is a side length, not a size enum — a separate
            // field so a card never has to guess which kind of grid it holds.
            'bingo_size' => $event->bingoCard?->size,
            // The whole card, for the settings modal's Format tab. Nested
            // rather than flattened alongside bingo_size because everything
            // in here belongs to the card and nothing else reads it — the
            // modal picks it apart in cardFields().
            'card' => $event->bingoCard ? [
                'size' => $event->bingoCard->size,
                'winCondition' => $event->bingoCard->win_condition,
                'lineBonus' => $event->bingoCard->line_bonus,
                'requiresApproval' => $event->bingoCard->requires_approval,
                'winLines' => $event->bingoCard->winLines(),
            ] : null,
            'authors' => $event->authors,
        ];
    }

    /**
     * The same thing, read from the database rather than from the instance
     * handed in.
     *
     * For the live channels, which are given an event loaded when the
     * connection opened and then asked about it for the next 45 seconds. The
     * relations are named because they are the ones the card reads, and a
     * cached one would be as stale as the attributes.
     */
    public static function fresh(Event $stale): array
    {
        $event = Event::with(['authors.user:id,discord_username,nickname,avatar_url', 'board', 'bingoCard'])
            ->whereKey($stale->getKey())
            ->first();

        return self::for($event ?? $stale);
    }
}
