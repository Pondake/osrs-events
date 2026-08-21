<?php

namespace App\Events\Channels;

use App\Models\Event;

/**
 * Maps an event type to its live channel.
 *
 * One place that knows the mapping, so adding an event type means adding a
 * channel and one line here — not touching a controller that would otherwise
 * accumulate a branch per type.
 *
 * Returns null for a type with nothing to stream, which the controller turns
 * into a 404 rather than an empty connection: holding a PHP worker open for
 * an event that can never push anything is the one cost this feature has.
 */
class EventChannelResolver
{
    /** @var array<string, class-string<EventChannel>> */
    private const CHANNELS = [
        'SNAKES_LADDERS' => SnakesLaddersChannel::class,
        'SKILL_RACE' => MetricRaceChannel::class,
        'DROP_RACE' => MetricRaceChannel::class,
        'BINGO' => BingoChannel::class,
    ];

    public function for(Event $event): ?EventChannel
    {
        $channel = self::CHANNELS[$event->type] ?? null;

        return $channel === null ? null : app($channel);
    }

    /** Whether this event has anything to stream at all. */
    public function has(Event $event): bool
    {
        return isset(self::CHANNELS[$event->type]);
    }
}
