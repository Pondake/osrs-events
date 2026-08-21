<?php

namespace App\Events\Channels;

use App\Models\Event;
use App\Services\EventStandingsService;

/**
 * Skill races and drop races.
 *
 * Both rank the same stored standings table, so both push the same thing —
 * whether the number is XP or killcounts was settled long before this point.
 */
class MetricRaceChannel implements EventChannel
{
    public function __construct(private readonly EventStandingsService $standings) {}

    public function name(): string
    {
        return 'standings';
    }

    public function fingerprint(Event $event): string
    {
        return $this->standings->fingerprint($event);
    }

    public function payload(Event $event): array
    {
        return ['standings' => $this->standings->forEvent($event)->all()];
    }
}
