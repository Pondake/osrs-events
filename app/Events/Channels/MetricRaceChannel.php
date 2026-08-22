<?php

namespace App\Events\Channels;

use App\Events\Channels\Concerns\SignalsEventEdits;
use App\Models\Event;
use App\Services\EventStandingsService;
use App\Support\EventCard;

/**
 * Skill races and drop races.
 *
 * Both rank the same stored standings table, so both push the same thing —
 * whether the number is XP or killcounts was settled long before this point.
 */
class MetricRaceChannel implements EventChannel
{
    use SignalsEventEdits;

    public function __construct(private readonly EventStandingsService $standings) {}

    public function name(): string
    {
        return 'standings';
    }

    public function fingerprint(Event $event): string
    {
        return $this->standings->fingerprint($event).'#'.$this->eventVersion($event);
    }

    public function payload(Event $event): array
    {
        return [
            'standings' => $this->standings->forEvent($event)->all(),
            'event_version' => $this->eventVersion($event),
            // The event itself, so an edit arrives on the connection that is
            // already open. Sending a version and letting the page re-ask
            // cost a second request, which on a single-worker dev server
            // queues behind this very stream — the edit showed up thirty
            // seconds late, and the delay looked like the feature.
            'event' => EventCard::fresh($event),
        ];
    }
}
