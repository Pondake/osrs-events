<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\EventStanding;
use App\Services\EventStandingsService;
use App\Services\RaceRankNotifier;
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

    public function handle(EventStandingsService $standings, WiseOldManService $wom, RaceRankNotifier $ranks): int
    {
        $events = Event::query()
            // Every type that races on a metric, not just skill races — a
            // drop race reads the same standings table.
            ->whereIn('type', Event::metricTypes())
            ->when($this->option('event'), fn ($q, $id) => $q->where('id', $id))
            // A finished event's numbers are final and a future one has
            // nothing to measure, so neither is worth an API call. Passing
            // --event overrides this: that form is for fixing one event by
            // hand, including one that just ended.
            ->unless($this->option('event'), fn ($q) => $q
                ->where(fn ($w) => $w->whereNull('start_date')->orWhere('start_date', '<=', Carbon::now()))
                ->where(fn ($w) => $w->whereNull('end_date')->orWhere('end_date', '>=', Carbon::now()->subDay()))
                // A paused race is a race whose scoreboard must not move.
                // Skipped rather than synced-and-hidden: the standings are
                // what a race IS, so pulling new numbers into them while the
                // host has stopped the event would make the pause a lie the
                // moment it was lifted.
                ->whereNull('paused_at'))
            ->get();

        if ($events->isEmpty()) {
            $this->info('No skill races to sync.');

            return self::SUCCESS;
        }

        $synced = 0;
        $failed = 0;

        // Every outbound call — the --track write included — is followed by a
        // pause. Sleeping per request rather than per participant is what
        // keeps --track under the same ceiling instead of doubling the rate.
        // The budget is theirs and depends on whether a key is configured.
        $perRequestDelay = $wom->shouldThrottle()
            ? intdiv(60 * 1_000_000, $wom->requestsPerMinute())
            : 0;

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

            // Ranks as they were before this event's rows are touched, so
            // "somebody passed you" can be answered by comparison rather
            // than by a stored column that goes stale the moment anything
            // else writes to the table. See RaceRankNotifier for why this
            // fires only on the podium boundaries and not on movement.
            $ranksBefore = $ranks->snapshot($event);

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

            // After the whole event, not per row: a mid-sync leaderboard is
            // a half-updated one, and notifying from it would announce
            // overtakes that unwind two rows later.
            try {
                $ranks->announce($event, $ranksBefore);
            } catch (Throwable $e) {
                // A failed notification must never cost more than the
                // notification — the standings are already correct by now.
                report($e);
            }
        }

        $this->info("Synced {$synced} standing(s)".($failed > 0 ? ", {$failed} not updated." : '.'));

        return self::SUCCESS;
    }
}
