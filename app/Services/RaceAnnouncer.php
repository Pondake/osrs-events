<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventStanding;
use App\Support\AnnouncementTrigger;
use Illuminate\Support\Collection;

/**
 * What a metric race has to say for itself in a Discord channel.
 *
 * Until 2026-09-12 it had nothing: a skill or drop race posted the six host
 * actions and not one word about the racing. Boards and cards got a post per
 * finisher because they have finishes; a race has standings that move, and
 * `RaceRankNotifier` — the only thing watching them — is push-only by design.
 *
 * The three moments here were chosen against how often each can fire, which
 * is the same rule the push catalogue runs on. The closing standings can
 * happen once and are on by default; the other two are off until a host asks,
 * and the announcer throttles the lead change on top of that.
 */
class RaceAnnouncer
{
    /** How many places a post names. Beyond three it stops being a sentence. */
    private const PODIUM = 3;

    public function __construct(private readonly DiscordAnnouncer $discord) {}

    /** The standings as they finally stand. Once, when the race closes. */
    public function final(Event $event): bool
    {
        $podium = $this->podium($event);

        if ($podium->isEmpty()) {
            return false;
        }

        return $this->discord->announce($event, AnnouncementTrigger::RACE_FINAL, trans(
            'notifications.discord_race_final',
            ['event' => $event->title, 'podium' => $this->asList($podium)],
        ).' '.route('events.show', $event));
    }

    /**
     * First place changed hands.
     *
     * Takes both rank maps rather than reading the table twice: the caller
     * already holds a before and an after, and a second query here would be
     * measuring a different moment than the one that triggered this.
     *
     * @param  array<string, int>  $before
     * @param  array<string, int>  $after
     */
    public function leadChanged(Event $event, array $before, array $after): bool
    {
        $was = array_search(1, $before, true);
        $is = array_search(1, $after, true);

        // Nothing to say when there was no leader, there is none now, or it
        // is the same person. A first leader on an empty board is not an
        // overtake — nobody was passed.
        if ($is === false || $was === false || $was === $is) {
            return false;
        }

        $leader = EventStanding::query()
            ->where('event_id', $event->id)
            ->where('user_id', $is)
            ->first();

        if ($leader === null) {
            return false;
        }

        return $this->discord->announce($event, AnnouncementTrigger::RACE_LEAD, trans(
            'notifications.discord_race_lead',
            ['event' => $event->title, 'name' => $leader->username],
        ).' '.route('events.show', $event));
    }

    /** Where the race stands, once a day. */
    public function digest(Event $event): bool
    {
        $podium = $this->podium($event);

        if ($podium->isEmpty()) {
            return false;
        }

        return $this->discord->announce($event, AnnouncementTrigger::RACE_DIGEST, trans(
            'notifications.discord_race_digest',
            ['event' => $event->title, 'podium' => $this->asList($podium)],
        ).' '.route('events.show', $event));
    }

    /**
     * The top rows that actually measured.
     *
     * Unsynced rows are excluded rather than sorted last: a row with no
     * number is not in last place, it is unknown, and naming it on a podium
     * would report a zero somebody never scored.
     *
     * @return Collection<int, EventStanding>
     */
    private function podium(Event $event): Collection
    {
        return EventStanding::query()
            ->where('event_id', $event->id)
            ->whereNotNull('user_id')
            ->whereNull('sync_error')
            ->whereNotNull('synced_at')
            ->orderByDesc('gained')
            ->orderBy('username')
            ->limit(self::PODIUM)
            ->get();
    }

    /** @param  Collection<int, EventStanding>  $podium */
    private function asList(Collection $podium): string
    {
        return $podium
            ->values()
            ->map(fn (EventStanding $row, int $i) => sprintf(
                '%d. %s — %s',
                $i + 1,
                $row->username,
                number_format((int) $row->gained),
            ))
            ->implode("\n");
    }
}
