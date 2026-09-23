<?php

namespace App\Services;

use App\Exceptions\WiseOldManRateLimited;
use App\Models\Event;
use App\Models\EventStanding;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Throwable;

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
            ->with(['user:id,discord_username,nickname,avatar_url', 'osrsAccount:id,position'])
            // Anyone we have no measurement for sorts to the bottom and is
            // left unranked below. Their gained is 0, so without this they
            // tie with everyone who genuinely gained nothing and take a rank
            // off the people who are actually competing.
            // A row the plugin has counted kills for is measured too, even if
            // Wise Old Man has never answered for it — otherwise the one
            // player actually killing the boss sorts below everyone who has
            // done nothing, for as long as their first sync takes.
            ->orderByRaw('case when live_gained > 0 then 0 when sync_error is not null or synced_at is null then 1 else 0 end')
            ->orderByDesc('gained')
            ->orderBy('username')
            ->get();

        $rank = 0;
        $seen = 0;
        $previous = null;

        // One line per osrs-events account, carried by its best character:
        // the rows are already best-first, so a group's first row is its
        // best. The others fold in under it. Never the sum — two characters
        // are not twice the player. A row whose account was closed has no
        // user and stands alone.
        return $rows->groupBy(fn (EventStanding $row) => $row->user_id ?? $row->id)->values()->map(function (Collection $group) use (&$rank, &$seen, &$previous) {
            $row = $group->first();
            $measured = self::measured($row);

            if ($measured) {
                $seen++;

                if ($row->gained !== $previous) {
                    $rank = $seen;
                    $previous = $row->gained;
                }
            }

            return [
                ...self::character($row),
                'rank' => $measured ? $rank : null,
                'displayName' => $row->user?->nickname ?: $row->user?->discord_username,
                'avatarUrl' => $row->user?->avatar_url,
                'characters' => $group->slice(1)->map(fn (EventStanding $other) => self::character($other))->values()->all(),
            ];
        });
    }

    private static function measured(EventStanding $row): bool
    {
        return ($row->sync_error === null && $row->synced_at !== null) || $row->live_gained > 0;
    }

    /** One character's line, as the leaderboard shows it. */
    private static function character(EventStanding $row): array
    {
        return [
            'id' => $row->id,
            'name' => $row->username,
            // An alt, or a character since removed from the account.
            'alt' => $row->osrsAccount?->position !== 0,
            'gained' => $row->gained,
            // How much of that number the plugin reported live. Shown so
            // a leaderboard can say a count is ahead of the hiscores
            // rather than looking like it disagrees with them.
            'live' => $row->live_gained,
            'start' => $row->start_value,
            'end' => $row->end_value,
            // Null synced_at is "never looked up", which the page shows as
            // pending rather than as a real zero.
            'syncedAt' => $row->synced_at?->toIso8601String(),
            'error' => $row->sync_error,
        ];
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
            ->get(['username', 'gained', 'live_gained', 'sync_error']);

        return md5($rows->map(fn ($r) => "{$r->username}:{$r->gained}:{$r->live_gained}:{$r->sync_error}")->implode('|'));
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
        $characters = $user->charactersFor($event);

        if ($characters->isEmpty()) {
            return null;
        }

        // Someone else already entered under the main's name. The database
        // enforces it too (see the unique index), but hitting a constraint
        // gives the user a 500 where this gives them a message. An alt with
        // the same clash simply does not get a row: it is not what they
        // asked to enter with.
        if ($this->nameTaken($event, $user, $characters->first()->username)) {
            throw ValidationException::withMessages(['osrs_username' => trans('events.rsn_already_entered')]);
        }

        $this->syncUser($event, $user);

        return EventStanding::where(['event_id' => $event->id, 'osrs_account_id' => $characters->first()->id])->first();
    }

    private function nameTaken(Event $event, User $user, string $username): bool
    {
        return EventStanding::where('event_id', $event->id)
            ->where('username', $username)
            ->where(fn ($q) => $q->where('user_id', '!=', $user->id)->orWhereNull('user_id'))
            ->exists();
    }

    /**
     * Give each of the user's characters in this event a row, and bring the
     * rows in line with the account.
     *
     * A character whose name changed re-baselines: the stored start value is
     * a different account's history. A character no longer allowed (removed,
     * or alts switched off before the start) loses its row while the event
     * has not started, and keeps it — unlinked, counting nothing new from the
     * plugin — once it has. Standings are a record.
     */
    public function syncUser(Event $event, User $user): void
    {
        $characters = $user->charactersFor($event)->keyBy('id');
        $rows = EventStanding::where(['event_id' => $event->id, 'user_id' => $user->id])->get();

        foreach ($rows as $row) {
            $character = $row->osrs_account_id ? $characters->get($row->osrs_account_id) : null;

            if ($character === null) {
                if ($row->osrs_account_id === null) {
                    continue;
                }

                $event->isUpcoming()
                    ? $row->delete()
                    : $row->forceFill(['osrs_account_id' => null])->save();

                continue;
            }

            if ($row->username === $character->username) {
                continue;
            }

            // enter() refuses a name someone else already races under, but
            // nothing stops a rename in settings afterwards to a name that is
            // taken here. Writing it anyway violates the unique index — and
            // inside the scheduled sync that exception killed the whole
            // command, freezing every other race with it. So the row keeps
            // the name its numbers came from and says why it is stuck.
            if ($this->nameTaken($event, $user, $character->username)) {
                $row->forceFill(['sync_error' => 'duplicate_username'])->save();

                continue;
            }

            // The kills the plugin counted belong to the name that reported
            // them, so a re-baseline drops them with the rest of the numbers.
            $row->kills()->delete();

            $row->fill([
                'username' => $character->username,
                'start_value' => null,
                'end_value' => null,
                'gained' => 0,
                'live_gained' => 0,
                'sync_error' => null,
                'synced_at' => null,
            ])->save();
        }

        $linked = $rows->pluck('osrs_account_id')->filter();

        foreach ($characters as $character) {
            if ($linked->contains($character->id) || $this->nameTaken($event, $user, $character->username)) {
                continue;
            }

            // A row left behind under this very name (an alt removed and
            // added back) takes it up again with its numbers.
            $orphan = $rows->first(fn (EventStanding $row) => $row->osrs_account_id === null && $row->username === $character->username);

            if ($orphan !== null) {
                $orphan->forceFill(['osrs_account_id' => $character->id])->save();

                continue;
            }

            EventStanding::create([
                'event_id' => $event->id,
                'user_id' => $user->id,
                'osrs_account_id' => $character->id,
                'username' => $character->username,
            ]);
        }
    }

    public function leave(Event $event, User $user): void
    {
        EventStanding::where(['event_id' => $event->id, 'user_id' => $user->id])->delete();

        // The last measured row leaving takes the staleness warning with it.
        // The flag means "what is on screen was read against a different
        // question", and once nothing is on screen there is no such claim
        // left to make — the banner otherwise sat above an empty table on an
        // event nobody was in, which is exactly how it was reported: join,
        // change the boss, leave, and the warning stays behind.
        if ($event->standingsAreStale() && ! $event->hasReadStandings()) {
            $event->forceFill(['standings_stale_since' => null])->save();
        }
    }

    /**
     * Bring every entrant's rows in line with their characters — see
     * syncUser(). Runs before a sync rather than on save in settings: the
     * standing knows which name its numbers came from, and this is the one
     * place that has to care.
     */
    public function syncUsernames(Event $event): void
    {
        $userIds = EventStanding::where('event_id', $event->id)->whereNotNull('user_id')->distinct()->pluck('user_id');

        User::whereIn('id', $userIds)->get()->each(fn (User $user) => $this->syncUser($event, $user));
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
    /**
     * Refresh every row on one event, now, and say what came back.
     *
     * The scheduled command (SyncEventStandings) walks every live race on a
     * timer; this is the same work aimed at one event because somebody asked
     * for it — a host who has just changed the dates or the metric and wants
     * to know whether the numbers under it are still true, rather than
     * finding out when the next cron run happens.
     *
     * The failures come back by name. "Updated 5 of 6" is a status; "Not A
     * Player is not tracked on Wise Old Man" is something a host can act on,
     * and acting on it is the whole reason to press the button.
     *
     * Paced the same way the command is: this is somebody else's public API,
     * and a host with forty entrants must not spend the site's whole minute
     * budget in one click.
     *
     * @return array{synced: int, failed: int, failures: array<int, array{name: string, error: string}>}
     */
    public function syncAll(Event $event): array
    {
        $this->syncUsernames($event);

        $delay = $this->wom->shouldThrottle()
            ? intdiv(60 * 1_000_000, $this->wom->requestsPerMinute())
            : 0;

        $rows = EventStanding::where('event_id', $event->id)->orderBy('username')->get();
        $synced = 0;
        $failures = [];

        foreach ($rows as $row) {
            try {
                $this->refresh($event, $row);
            } catch (Throwable $error) {
                // One entrant must never stop the rest, exactly as in the
                // scheduled command — with the difference that somebody is
                // watching this one, so it is reported rather than only logged.
                report($error);
                $failures[] = ['name' => $row->username, 'error' => 'failed'];

                continue;
            }

            $fresh = $row->refresh();

            if ($fresh->sync_error === null && $fresh->synced_at !== null) {
                $synced++;
            } else {
                $failures[] = ['name' => $row->username, 'error' => $fresh->sync_error ?? 'pending'];
            }

            usleep($delay);
        }

        // Caught up: every row has now been asked the current question, even
        // the ones that came back empty. A name Wise Old Man cannot measure is
        // not a reason to keep telling everyone the table is out of date.
        $event->forceFill(['standings_stale_since' => null])->save();

        return ['synced' => $synced, 'failed' => count($failures), 'failures' => $failures];
    }

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
        try {
            $delta = $this->wom->gained(
                $standing->username,
                $event->metric,
                $event->metricKind() ?? 'skill',
                $start,
                $end->isFuture() ? Carbon::now() : $end,
            );
        } catch (WiseOldManRateLimited) {
            // Deliberately NOT stamping synced_at. Every other outcome here
            // is an answer of some kind, so the row is done being asked; this
            // one is us not having asked yet, and a row that looks synced is a
            // row the next run has no reason to prioritise (refreshAll orders
            // never-synced and failing rows first). Leaving it unstamped is
            // what makes the retry happen on its own.
            $standing->forceFill(['sync_error' => 'rate_limited'])->save();

            return;
        }

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
            // The higher of the two, never the newer. A drop race counts boss
            // kills the RuneLite plugin has already reported (RaceKillService),
            // and the hiscores lag by hours — so a sync that lands between two
            // kills would otherwise walk the number backwards on the one
            // player who is doing the killing. Their API can only be behind,
            // so once it catches up it wins on its own.
            'gained' => max($delta['gained'], $standing->live_gained),
            'sync_error' => null,
            'synced_at' => Carbon::now(),
        ])->save();

        // A successful gains read is proof the account exists, so clear the
        // "we can't find you" notice without spending a second lookup on it.
        // Guarded on the names still matching: the standing keeps the name its
        // numbers came from, which after a blocked rename is no longer the
        // one on the account.
        $character = $standing->osrsAccount;

        if ($character !== null && $character->username === $standing->username && $character->osrs_verified_at === null) {
            // The main through the account, so the mirror follows.
            $character->isMain() && $standing->user !== null
                ? $standing->user->forceFill(['osrs_verified_at' => Carbon::now()])->save()
                : $character->forceFill(['osrs_verified_at' => Carbon::now()])->save();
        }
    }
}
