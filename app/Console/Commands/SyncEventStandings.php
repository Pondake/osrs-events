<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\EventStanding;
use App\Services\EventStandingsService;
use App\Services\WiseOldManService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * Refreshes skill-race standings from Wise Old Man.
 *
 * This is the only thing that talks to their API. Everything else — the page
 * render, the SSE stream — reads the rows this writes, so the number of
 * outbound requests depends on how often this runs, not on how many people
 * are watching a leaderboard.
 */
class SyncEventStandings extends Command
{
    protected $signature = 'events:sync-standings
        {--event= : Sync one event by id instead of every running one}
        {--track : Also ask Wise Old Man to re-import players it has no data for}';

    protected $description = 'Refresh skill-race standings from the Wise Old Man API';

    public function handle(EventStandingsService $standings, WiseOldManService $wom): int
    {
        $events = Event::query()
            ->where('type', 'SKILL_RACE')
            ->when($this->option('event'), fn ($q, $id) => $q->where('id', $id))
            // A finished event's numbers are final and a future one has
            // nothing to measure, so neither is worth an API call. Passing
            // --event overrides this: that form is for fixing one event by
            // hand, including one that just ended.
            ->unless($this->option('event'), fn ($q) => $q
                ->where(fn ($w) => $w->whereNull('start_date')->orWhere('start_date', '<=', Carbon::now()))
                ->where(fn ($w) => $w->whereNull('end_date')->orWhere('end_date', '>=', Carbon::now()->subDay())))
            ->get();

        if ($events->isEmpty()) {
            $this->info('No skill races to sync.');

            return self::SUCCESS;
        }

        // Their anonymous limit is 20 requests a minute, so every outbound
        // call — the --track write included — is followed by a three-second
        // pause. Sleeping per request rather than per participant is what
        // keeps --track under the same ceiling instead of doubling the rate.
        $perRequestDelay = intdiv(60 * 1_000_000, WiseOldManService::RATE_LIMIT_PER_MINUTE);

        $synced = 0;
        $failed = 0;

        foreach ($events as $event) {
            // Cheap and local — catches anyone who renamed since entering, so
            // the lookups below use the name that account actually has now.
            // Wrapped for the same reason as the per-row work below: this is
            // unattended, and one event's problem is not the others'.
            try {
                $standings->syncUsernames($event);
            } catch (Throwable $e) {
                $this->error("  {$event->title}: {$e->getMessage()}");
                report($e);
            }

            // Least recently synced first, so a run that gets killed halfway
            // through still makes progress on a different slice next time
            // instead of refreshing the same few rows forever.
            $rows = EventStanding::query()
                ->where('event_id', $event->id)
                ->orderByRaw('synced_at is null desc')
                ->orderBy('synced_at')
                ->get();

            foreach ($rows as $row) {
                // One participant must never be able to stop the run. This is
                // scheduled work with no one watching it: an exception on row
                // three leaves every row after it stale indefinitely, across
                // every remaining event, and the only symptom is a leaderboard
                // that quietly stops moving.
                try {
                    if ($this->option('track') && ($row->sync_error !== null || $row->synced_at === null)) {
                        $wom->trackPlayer($row->username);
                        usleep($perRequestDelay);
                    }

                    $standings->refresh($event, $row);

                    if ($row->refresh()->sync_error === null) {
                        $synced++;
                    } else {
                        $failed++;
                    }
                } catch (Throwable $e) {
                    $failed++;
                    $this->error("  {$row->username}: {$e->getMessage()}");
                    report($e);
                }

                usleep($perRequestDelay);
            }
        }

        $this->info("Synced {$synced} standing(s)".($failed > 0 ? ", {$failed} not updated." : '.'));

        return self::SUCCESS;
    }
}
