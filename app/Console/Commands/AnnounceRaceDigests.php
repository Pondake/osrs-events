<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Services\RaceAnnouncer;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * The once-a-day standing, for every race whose host asked for one.
 *
 * Its own command rather than a branch of push:sweep: that one runs every
 * fifteen minutes and claims once-per-event facts with a cache key, and a
 * daily post does not need either. A command that runs once a day is allowed
 * to be a command that runs once a day.
 *
 * Only live races. A race that has not started has nothing to report, and one
 * that has ended already sent its closing standings — repeating them daily
 * afterwards is how a channel learns to ignore the feed.
 */
class AnnounceRaceDigests extends Command
{
    protected $signature = 'events:race-digest';

    protected $description = 'Post the daily standings of every metric race that asked for one';

    public function handle(RaceAnnouncer $announcer): int
    {
        $now = Carbon::now();

        $events = Event::query()
            ->whereIn('type', Event::metricTypes())
            ->whereNull('closed_at')
            ->whereNotNull('discord_webhook_url')
            ->where(fn ($q) => $q->whereNull('start_date')->orWhere('start_date', '<=', $now))
            ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $now))
            ->get();

        $posted = 0;

        foreach ($events as $event) {
            // One race must not stop the rest, the same rule the standings
            // sync runs on: this is scheduled work nobody is watching, and a
            // throw on the third event silently costs every event after it.
            try {
                if ($announcer->digest($event)) {
                    $posted++;
                }
            } catch (Throwable $e) {
                $this->error("  {$event->title}: {$e->getMessage()}");
                report($e);
            }
        }

        $this->info("Posted {$posted} digest(s) across {$events->count()} live race(s).");

        return self::SUCCESS;
    }
}
