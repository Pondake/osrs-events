<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventStanding;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Reads and refreshes the standings table for metric events.
 *
 * The read side is deliberately cheap — a single indexed query — because the
 * SSE stream polls it on a timer and a page request renders it. Anything
 * expensive (talking to Wise Old Man) lives in refresh(), which only the sync
 * command calls.
 */
class EventStandingsService
{
    public function __construct(private readonly WiseOldManService $wom) {}

    /**
     * The standings as the leaderboard renders them, best first.
     *
     * Ranking is dense-ish but not clever: ties share a rank, and the next
     * distinct score skips accordingly (1, 2, 2, 4). That is how Wise Old Man
     * ranks a competition, and matching it means a player comparing the two
     * pages does not find two different answers.
     */
    public function forEvent(Event $event): Collection
    {
        $rows = EventStanding::query()
            ->where('event_id', $event->id)
            ->with('user:id,discord_username,nickname,avatar_url')
            // Anyone we have no measurement for sorts to the bottom and is
            // left unranked below. Their gained is 0, so without this they
            // tie with everyone who genuinely gained nothing and take a rank
            // off the people who are actually competing.
            ->orderByRaw('case when sync_error is not null or synced_at is null then 1 else 0 end')
            ->orderByDesc('gained')
            ->orderBy('username')
            ->get();

        $rank = 0;
        $seen = 0;
        $previous = null;

        return $rows->map(function (EventStanding $row) use (&$rank, &$seen, &$previous) {
            $measured = $row->sync_error === null && $row->synced_at !== null;

            if ($measured) {
                $seen++;

                if ($row->gained !== $previous) {
                    $rank = $seen;
                    $previous = $row->gained;
                }
            }

            return [
                'id' => $row->id,
                'rank' => $measured ? $rank : null,
                'name' => $row->username,
                'displayName' => $row->user?->nickname ?: $row->user?->discord_username,
                'avatarUrl' => $row->user?->avatar_url,
                'gained' => $row->gained,
                'start' => $row->start_value,
                'end' => $row->end_value,
                // Null synced_at is "never looked up", which the page shows as
                // pending rather than as a real zero.
                'syncedAt' => $row->synced_at?->toIso8601String(),
                'error' => $row->sync_error,
            ];
        });
    }

    /**
     * A cheap value that changes exactly when the rendered standings change.
     *
     * The SSE stream compares this instead of diffing rows, so an idle event
     * sends nothing at all down an open connection. Built from the fields the
     * client actually displays — a sync that finds no change rewrites
     * synced_at but must not wake every connected browser.
     */
    public function fingerprint(Event $event): string
    {
        $rows = EventStanding::query()
            ->where('event_id', $event->id)
            ->orderBy('username')
            ->get(['username', 'gained', 'sync_error']);

        return md5($rows->map(fn ($r) => "{$r->username}:{$r->gained}:{$r->sync_error}")->implode('|'));
    }

    /**
     * Enter a user into the race.
     *
     * Deliberately explicit, not derived from access. An OPEN event grants
     * access implicitly and stores no row for it (see
     * BoardAccessService::hasAccess), so deriving participation from access
     * would leave every open race permanently empty — and on the modes where
     * it does work, it would enrol anyone who merely looked at a public
     * leaderboard. Entering is a decision, so it takes a click.
     *
     * Returns null when the user has no RSN: a row with no name to look up is
     * a permanent zero on the leaderboard, which is worse than being absent.
     */
    public function enter(Event $event, User $user): ?EventStanding
    {
        if (blank($user->osrs_username)) {
            return null;
        }

        // Someone else already entered under this name. The database enforces
        // it too (see the unique index), but hitting a constraint gives the
        // user a 500 where this gives them a message.
        $claimed = EventStanding::where('event_id', $event->id)
            ->where('username', $user->osrs_username)
            ->where('user_id', '!=', $user->id)
            ->exists();

        if ($claimed) {
            throw ValidationException::withMessages(['osrs_username' => trans('events.rsn_already_entered')]);
        }

        $standing = EventStanding::firstOrNew([
            'event_id' => $event->id,
            'user_id' => $user->id,
        ]);

        // A rename since last time means the stored baseline belongs to a
        // different account's history. Cleared so the next sync re-baselines
        // rather than reporting the difference between two people.
        if ($standing->exists && $standing->username === $user->osrs_username) {
            return $standing;
        }

        $standing->fill([
            'username' => $user->osrs_username,
            'start_value' => null,
            'end_value' => null,
            'gained' => 0,
            'sync_error' => null,
            'synced_at' => null,
        ])->save();

        return $standing;
    }

    public function leave(Event $event, User $user): void
    {
        EventStanding::where(['event_id' => $event->id, 'user_id' => $user->id])->delete();
    }

    /**
     * Re-point any row whose user has changed their RSN since entering.
     *
     * Runs before a sync rather than on save in settings: the standing knows
     * which name its numbers came from, and this is the one place that has to
     * care. Same re-baselining rule as enter() — a new name means the old
     * start value is somebody else's.
     */
    public function syncUsernames(Event $event): void
    {
        $rows = EventStanding::where('event_id', $event->id)->with('user:id,osrs_username')->get();

        foreach ($rows as $row) {
            if ($row->user === null || blank($row->user->osrs_username)) {
                continue;
            }

            if ($row->username === $row->user->osrs_username) {
                continue;
            }

            // enter() refuses a name someone else already races under, but
            // nothing stops a user changing their RSN in settings afterwards
            // to a name that is taken here. Writing it anyway violates the
            // unique index — and because this runs inside the scheduled sync,
            // that exception killed the whole command, freezing standings for
            // every remaining participant in every remaining event.
            //
            // So the standing keeps the name its numbers came from and says
            // why it is stuck, which is a thing the page can show. The clash
            // is between two accounts and only a person can settle it.
            $taken = EventStanding::where('event_id', $event->id)
                ->where('username', $row->user->osrs_username)
                ->whereKeyNot($row->getKey())
                ->exists();

            if ($taken) {
                $row->forceFill(['sync_error' => 'duplicate_username'])->save();

                continue;
            }

            $row->fill([
                'username' => $row->user->osrs_username,
                'start_value' => null,
                'end_value' => null,
                'gained' => 0,
                'sync_error' => null,
                'synced_at' => null,
            ])->save();
        }
    }

    /**
     * Refresh one participant's numbers from Wise Old Man.
     *
     * The window is the event's own: gains before it started or after it ended
     * do not count, which is what makes this a competition rather than a
     * hiscores mirror. An event with no start date falls back to when the row
     * was created — the earliest moment we can honestly claim to have been
     * watching.
     */
    public function refresh(Event $event, EventStanding $standing): void
    {
        $start = $event->start_date ? Carbon::parse($event->start_date)->startOfDay() : $standing->created_at;
        $end = $event->end_date ? Carbon::parse($event->end_date)->endOfDay() : Carbon::now();

        // A future event has nothing to measure yet, and asking for a window
        // that has not begun returns noise. Left untouched rather than marked:
        // "not started" is a fact about the event, which the page already
        // knows from its dates, not a per-participant failure.
        if ($start->isFuture()) {
            return;
        }

        // Guarded because the column is nullable while a metric race is
        // meaningless without one. Validation stops the form creating such an
        // event, but nothing stops a seeder, a console command or a future
        // event type — and unguarded this is a TypeError on every sync, which
        // is a far worse way to find out than a message on the row.
        if (blank($event->metric)) {
            $standing->forceFill([
                'sync_error' => 'no_metric',
                'synced_at' => Carbon::now(),
            ])->save();

            return;
        }

        // Never ask past "now": a still-running event's end_date is in the
        // future, and their API answers a future window with whatever the
        // latest snapshot happens to be.
        $delta = $this->wom->gained(
            $standing->username,
            $event->metric,
            $event->metricKind() ?? 'skill',
            $start,
            $end->isFuture() ? Carbon::now() : $end,
        );

        if ($delta === null) {
            $standing->forceFill([
                'sync_error' => 'not_tracked',
                'synced_at' => Carbon::now(),
            ])->save();

            return;
        }

        $standing->forceFill([
            'start_value' => $delta['start'],
            'end_value' => $delta['end'],
            'gained' => $delta['gained'],
            'sync_error' => null,
            'synced_at' => Carbon::now(),
        ])->save();

        // A successful gains read is proof the account exists, so clear the
        // "we can't find you" notice without spending a second lookup on it.
        // Guarded on the names still matching: the standing keeps the name its
        // numbers came from, which after a blocked rename is no longer the
        // one on the account.
        if ($standing->user !== null
            && $standing->user->osrs_username === $standing->username
            && $standing->user->osrs_verified_at === null) {
            $standing->user->forceFill(['osrs_verified_at' => Carbon::now()])->save();
        }
    }
}
