<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\BingoCard;
use App\Models\BingoCompletion;
use App\Models\CompletedTile;
use App\Models\Event;
use App\Models\EventFinish;
use App\Models\PlayerBoard;
use App\Models\User;
use App\Support\NotificationCategory;
use App\Support\Ordinal;
use App\Support\PushMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * "Somebody finished." The one place that decides what that means.
 *
 * Before this, finishing was a browser-side guess: BoardShow.vue compared the
 * ticked tile's position against the tile count and popped a modal that a
 * refresh erased. Nothing was stored, so nobody else heard about it, the
 * finisher kept a live dice button that could only move them nowhere, and
 * second place was not merely unshown — it was unknowable, because no
 * timestamp existed to sort by.
 *
 * Two event types ask the question in two different vocabularies, so both ask
 * it here:
 *
 *  - **Snakes & Ladders** — the last tile of the board is ticked off and
 *    approved. Position on the track is not the test: a player can stand on
 *    the final tile for days without completing it, and standing there is not
 *    finishing.
 *  - **Bingo** — the card's own win condition, whatever the host set it to,
 *    via BingoService::hasWon().
 *
 * Both go through evaluate*(), which is **idempotent in both directions**: it
 * stamps a finish that has become true and removes one that has stopped being
 * true. The second half is not hypothetical — a host rejecting a claim they
 * had approved, or a snake swallowing a completed tile, both un-finish a
 * competitor, and a podium that cannot be corrected is worse than none.
 *
 * The one thing it will not undo by itself is a **closed event**. Under the
 * STOP rule the first finish stops the event for everyone; if the claim
 * behind it is later rejected, reopening is a host's deliberate action, not
 * something that happens quietly while they are looking elsewhere.
 *
 * **A finish is timestamped when it was submitted, never when it was
 * approved.** On a board that reviews claims, two people can get home minutes
 * apart and both send a screenshot; the host opens the queue that evening and
 * works down it in whatever order it happens to be in. If approval decided
 * the order, the winner would be whoever got clicked first, and reviewing the
 * same two claims in the other order would produce a different result from
 * the same night's play. Ranks are derived from that submission time, so they
 * correct themselves as the queue is worked through — see settleClosure() for
 * the half of this that the announcement needs.
 */
class EventFinishService
{
    public function __construct(
        private readonly BingoService $bingo,
        private readonly PushNotifier $push,
        private readonly DiscordAnnouncer $discord,
    ) {}

    /**
     * Has this Snakes & Ladders player finished — and record it either way.
     *
     * Called after anything that can change the answer: ticking the last
     * tile, a host ruling on it, and a snake eating it again on the next
     * roll.
     */
    public function evaluateSnakesLadders(Event $event, ?PlayerBoard $playerBoard): ?EventFinish
    {
        if ($playerBoard === null || $event->board === null) {
            return null;
        }

        $claim = $this->lastTileClaim($event, $playerBoard->id, 'APPROVED');

        return $this->record(
            $event,
            userId: $event->mode === 'TEAM' ? null : $playerBoard->user_id,
            teamId: $event->mode === 'TEAM' ? $playerBoard->team_id : null,
            finished: $claim !== null,
            displayName: $playerBoard->team?->name ?? $playerBoard->user?->displayName(),
            // **When they submitted it, not when it was approved.** Two
            // people finish within a minute of each other and both send a
            // screenshot; the host opens the queue an hour later and works
            // through it in whatever order the list happens to be in. If the
            // approval decided the podium, the winner would be whoever the
            // host happened to click first — and reviewing in a different
            // order would produce a different result from the same evening.
            // The player finished when they did the thing and said so; the
            // host's job is to confirm it, not to time it.
            finishedAt: $claim?->completed_at,
        );
    }

    /**
     * This player's claim on the finishing tile, in a given state.
     *
     * The board's SIZE decides which tile that is, not the number of tiles a
     * host got round to filling in — the same distinction
     * PlayerBoardController::roll() had to make after counting rows turned
     * the sixth tile of a 5x5 into the finish line.
     */
    private function lastTileClaim(Event $event, string $playerBoardId, string $status): ?CompletedTile
    {
        $lastPosition = max($event->board->tileCount() - 1, 0);

        return CompletedTile::query()
            ->where('completed_tiles.player_board_id', $playerBoardId)
            ->where('completed_tiles.status', $status)
            ->whereHas('tile', fn ($q) => $q
                ->where('board_id', $event->board->id)
                ->where('position', $lastPosition))
            ->first();
    }

    /**
     * The same question for a bingo card, asked of one competitor.
     *
     * @param  array{team_id: ?string, user_id: ?string}  $competitor  from BingoService::competitorFor()
     */
    public function evaluateBingo(Event $event, array $competitor): ?EventFinish
    {
        $card = $event->bingoCard;

        if ($card === null) {
            return null;
        }

        // Approved only, plus the wildcards — exactly the set
        // BingoService::standings() scores on. A card whose free squares were
        // left out here would refuse to acknowledge a line the page is
        // already drawing as complete.
        $approved = $this->bingo->claimsFor($card, $competitor)
            ->filter(fn ($c) => $c->status === 'APPROVED');

        $submittedAt = $approved->mapWithKeys(fn ($c) => [(int) $c->square_position => $c->created_at]);

        $positions = $submittedAt->keys()
            ->merge($this->bingo->wildcardPositions($card))
            ->unique()
            ->values()
            ->all();

        return $this->record(
            $event,
            userId: $competitor['user_id'] ?? null,
            teamId: $competitor['team_id'] ?? null,
            finished: $this->bingo->hasWon($card, $positions),
            displayName: null,
            // Same rule as the board: when the card was actually completed,
            // which is the submission that finished it off — not whenever a
            // host got round to signing that square off.
            finishedAt: $this->bingoWonAt($card, $positions, $submittedAt->all()),
        );
    }

    /**
     * When a won card was actually won.
     *
     * The moment the competitor put in the last piece they needed — so for a
     * LINE card, the latest submission on whichever line they completed
     * first; for FULL_HOUSE, the latest submission on the card. Wildcards
     * have no submission because they belong to everybody at once, so they
     * simply do not move this either way.
     *
     * @param  array<int, int>  $positions  approved positions, wildcards included
     * @param  array<int, \Illuminate\Support\Carbon|null>  $submittedAt  keyed by position
     */
    private function bingoWonAt(BingoCard $card, array $positions, array $submittedAt): ?Carbon
    {
        $latestOf = function (array $line) use ($submittedAt): ?Carbon {
            $times = array_filter(array_map(fn (int $p) => $submittedAt[$p] ?? null, $line));

            // A line made entirely of wildcards is won by everyone the moment
            // the card exists, and has no submission to point at.
            return $times === [] ? null : max($times);
        };

        if ($card->win_condition === 'FULL_HOUSE') {
            return $latestOf($positions);
        }

        $completed = $this->bingo->completedLines($card->size, $positions, $card->winLines());

        $times = array_filter(array_map($latestOf, $completed));

        // The FIRST line they completed, not the last — a competitor who has
        // since gone on to fill in three more rows finished when the first
        // one landed.
        return $times === [] ? null : min($times);
    }

    /**
     * Write the answer down, and act on it if it is new.
     *
     * The insert and the close share one transaction with the event row
     * locked, so two competitors finishing in the same second cannot both
     * read "nobody has finished yet" and both be told they are first.
     */
    private function record(Event $event, ?string $userId, ?string $teamId, bool $finished, ?string $displayName, ?Carbon $finishedAt = null): ?EventFinish
    {
        if ($userId === null && $teamId === null) {
            return null;
        }

        $query = fn () => EventFinish::where('event_id', $event->id)
            ->where('user_id', $userId)
            ->where('team_id', $teamId)
            ->first();

        if (! $finished) {
            // Gone again: a rejected claim, or a snake that swallowed the
            // last tile. The event stays closed if it was closed — see the
            // class docblock.
            $query()?->delete();

            // Unconditionally, not only when a row was actually deleted: the
            // commonest way a podium becomes settled is a host rejecting a
            // *pending* contender, which touches no finish at all but is
            // exactly the thing the announcement and the close were waiting
            // on.
            $this->settleAnnouncements($event);
            $this->settleClosure($event);

            return null;
        }

        if ($existing = $query()) {
            // Already recorded, and quietly so. Every later approval on a
            // won card re-enters here, and re-announcing a win each time
            // somebody ticks a square they had already won without is
            // exactly the kind of noise that gets push permission revoked.
            return $existing;
        }

        $finish = EventFinish::create([
            'event_id' => $event->id,
            'user_id' => $userId,
            'team_id' => $teamId,
            'display_name' => $displayName,
            // now() only as a fallback — on a board that reviews nothing, the
            // submission IS now.
            'finished_at' => $finishedAt ?? now(),
        ]);

        $finish->load([
            'user:id,discord_username,nickname,avatar_url',
            'team:id,name,icon_url,guild_id,guild_icon',
        ]);

        AuditLog::record('event.finished', $event, [
            'competitor' => $finish->label(),
            'place' => $this->placeOf($event, $finish),
        ]);

        // NOT announced here. What place this is may still change — see
        // settleAnnouncements(), which is where the telling happens once it
        // cannot.
        $this->settleAnnouncements($event);
        $this->settleClosure($event);

        return $finish;
    }

    /**
     * Tell people about the finishes whose place can no longer change.
     *
     * The podium orders by submission, so a finish approved while an earlier
     * claim is still in the queue is **provisional**: approving that earlier
     * claim will push it down a place. Announcing it anyway is what was
     * reported — the host approved the second submission first, and every
     * player was told that team had got home first while the claim that
     * actually won sat unopened in the queue.
     *
     * So this walks the podium in order and stops at the first finish that
     * is still provisional. Stopping rather than skipping is deliberate:
     * everything below an unsettled place is unsettled too, because the
     * claim that is still waiting will push all of them down.
     *
     * Re-entrant, and called after every approval and every rejection — a
     * rejection settles a place just as surely as an approval does.
     */
    private function settleAnnouncements(Event $event): void
    {
        $finishes = EventFinish::where('event_id', $event->id)
            ->orderBy('finished_at')
            ->orderBy('created_at')
            ->get();

        foreach ($finishes as $index => $finish) {
            if ($this->hasEarlierContender($event, $finish->finished_at)) {
                return;
            }

            if ($finish->announced_at !== null) {
                continue;
            }

            $finish->forceFill(['announced_at' => now()])->save();

            $finish->load([
                'user:id,discord_username,nickname,avatar_url',
                'team:id,name,icon_url,guild_id,guild_icon',
            ]);

            $this->announceFinish($event, $finish, $index + 1);
        }
    }

    /**
     * Is this finish's place still up for grabs?
     *
     * Read by the pages as well as by the announcer: a competitor whose place
     * is provisional is shown that their run is in, and not yet which place
     * it took — telling somebody they came first and correcting it a minute
     * later is worse than making them wait a minute.
     */
    public function isProvisional(Event $event, ?Carbon $finishedAt): bool
    {
        return $this->hasEarlierContender($event, $finishedAt);
    }

    /**
     * The earliest moment a claim still in the queue could turn out to have
     * finished at — or null when nothing waiting can finish anything.
     *
     * Computed once per read rather than per row. `places()` renders the
     * whole podium and the live channel sends it on every push, so asking
     * "could anyone still beat THIS one?" per finish meant one EXISTS query
     * per competitor per render — thirty of them on a board with thirty
     * finishers, several times a minute across every open browser.
     */
    private function earliestContenderAt(Event $event): ?Carbon
    {
        if ($event->board !== null) {
            $lastPosition = max($event->board->tileCount() - 1, 0);

            $at = CompletedTile::query()
                ->where('completed_tiles.status', 'PENDING')
                ->whereHas('tile', fn ($q) => $q
                    ->where('board_id', $event->board->id)
                    ->where('position', $lastPosition))
                ->min('completed_tiles.completed_at');

            return $at === null ? null : Carbon::parse($at);
        }

        $card = $event->bingoCard;

        if ($card === null) {
            return null;
        }

        $pending = BingoCompletion::query()
            ->join('bingo_squares', 'bingo_squares.id', '=', 'bingo_completions.bingo_square_id')
            ->where('bingo_squares.bingo_card_id', $card->id)
            ->where('bingo_completions.status', 'PENDING')
            ->get(['bingo_completions.*'])
            ->groupBy(fn (BingoCompletion $c) => $c->team_id ?? $c->user_id);

        $earliest = null;

        foreach ($pending as $claims) {
            $competitor = ['team_id' => $claims->first()->team_id, 'user_id' => $claims->first()->user_id];

            $submittedAt = $this->bingo->claimsFor($card, $competitor)
                ->filter(fn ($c) => $c->status === 'APPROVED')
                ->mapWithKeys(fn ($c) => [(int) $c->square_position => $c->created_at]);

            // What the card would look like if every one of their waiting
            // claims were signed off.
            foreach ($claims as $claim) {
                $submittedAt[(int) $claim->square?->position] = $claim->created_at;
            }

            $positions = $submittedAt->keys()
                ->merge($this->bingo->wildcardPositions($card))
                ->unique()
                ->values()
                ->all();

            if (! $this->bingo->hasWon($card, $positions)) {
                continue;
            }

            $wouldWinAt = $this->bingoWonAt($card, $positions, $submittedAt->all());

            if ($wouldWinAt !== null && ($earliest === null || $wouldWinAt->lt($earliest))) {
                $earliest = $wouldWinAt;
            }
        }

        return $earliest;
    }

    /**
     * Close a STOP event — but only once the result cannot still change.
     *
     * The naive version closed on the first approval, which handed the win to
     * whoever the host happened to click first. Ordering by submission fixes
     * the *podium*, but not the announcement: closing on an approval while an
     * earlier claim is still sitting in the queue means telling everybody the
     * wrong person won, and a push notification cannot be taken back.
     *
     * So the close waits for the queue to be clear of anyone who could still
     * beat the current leader. Until then the event stays open, which is the
     * honest state — the result genuinely is not settled yet. Re-entrant on
     * purpose: it is called after every approval AND every rejection, so
     * clearing the last contender either way lets the close through.
     */
    private function settleClosure(Event $event): void
    {
        if ($event->finish_rule !== 'STOP') {
            return;
        }

        $result = DB::transaction(function () use ($event) {
            // Locked so that "is anyone still in contention?" and "close it"
            // are one indivisible answer — two approvals landing together
            // must not both decide they are the last one.
            $locked = Event::whereKey($event->id)->lockForUpdate()->first() ?? $event;

            if ($locked->isClosed()) {
                return null;
            }

            $leader = EventFinish::where('event_id', $locked->id)
                ->orderBy('finished_at')
                ->orderBy('created_at')
                ->first();

            if ($leader === null || $this->hasEarlierContender($locked, $leader->finished_at)) {
                return null;
            }

            $locked->forceFill(['closed_at' => now()])->save();
            // The caller is holding a stale instance and will very likely
            // render from it in the same request — BoardShow reads
            // `closed_at` to decide whether to show a dice at all.
            $event->closed_at = $locked->closed_at;

            return $leader;
        });

        if ($result === null) {
            return;
        }

        AuditLog::record('event.closed', $event, ['reason' => 'finish']);

        $result->load([
            'user:id,discord_username,nickname,avatar_url',
            'team:id,name,icon_url,guild_id,guild_icon',
        ]);

        $this->announceClosed($event, $result);
    }

    /**
     * Is there a claim still waiting on a host that would finish the event
     * earlier than this moment?
     *
     * Exact rather than conservative: on a bingo card "any pending claim at
     * all" would mean a busy card never settles, so a square only counts if
     * approving it would actually win the card for whoever submitted it.
     */
    private function hasEarlierContender(Event $event, ?Carbon $at): bool
    {
        if ($at === null) {
            return false;
        }

        $earliest = $this->earliestContenderAt($event);

        return $earliest !== null && $earliest->lt($at);
    }

    /**
     * A host stopping the event by hand, or starting it again.
     *
     * The manual twin of what the first finish does on its own under the
     * STOP rule — same `closed_at` column, same audit action — which is the
     * whole argument for the button existing: it is a handle on a state
     * machine that had to be built anyway, not a second way to stop an event.
     */
    public function close(Event $event, bool $closed): void
    {
        $event->forceFill(['closed_at' => $closed ? now() : null])->save();

        AuditLog::record($closed ? 'event.closed' : 'event.reopened', $event, ['reason' => 'host']);
    }

    /**
     * The podium: every finish in the order it was earned, shaped for the
     * pages.
     *
     * `rank` is the index, computed here and nowhere else — see the
     * event_finishes migration for why it is not a column.
     *
     * @return array<int, array<string, mixed>>
     */
    public function places(Event $event, bool $namesArePublic = true): array
    {
        // Asked once for the whole podium rather than once per row — see
        // earliestContenderAt(). This is rendered on every page load AND sent
        // on every live push, so a per-row query here is a per-row query
        // several times a minute for every open browser.
        $contenderAt = $this->earliestContenderAt($event);

        return $event->finishes()
            ->with([
                'user:id,discord_username,nickname,avatar_url',
                'team:id,name,icon_url,guild_id,guild_icon',
            ])
            ->get()
            ->values()
            ->map(fn (EventFinish $finish, int $index) => [
                'id' => $finish->id,
                'rank' => $index + 1,
                'finishedAt' => $finish->finished_at?->toIso8601String(),
                // Whether this place can still move. A page must not
                // celebrate a competitor who is only provisionally first —
                // that is the reported bug, where approving the second
                // submission told everyone the wrong team had got home first
                // while the winning claim sat unopened in the queue.
                'provisional' => $contenderAt !== null
                    && $finish->finished_at !== null
                    && $contenderAt->lt($finish->finished_at),
                // Identity only where it is public — a stranger on a listed
                // invite-only event sees that somebody finished and not who,
                // exactly as BoardController::show() treats the pieces on the
                // board. An id is an identity to anyone who can look it up,
                // so it goes too.
                'userId' => $namesArePublic ? $finish->user_id : null,
                'teamId' => $namesArePublic ? $finish->team_id : null,
                'label' => $namesArePublic ? $finish->label() : trans('events.anonymous_player'),
                'avatarUrl' => $namesArePublic ? ($finish->team?->icon_url ?? $finish->user?->avatar_url) : null,
            ])
            ->all();
    }

    /**
     * A cheap summary of the podium, for a live channel's fingerprint.
     *
     * Ids and the closed stamp, not the rows: a fingerprint runs every few
     * seconds for every connected viewer, so it must stay a small query —
     * see the split EventChannel documents between fingerprint() and
     * payload().
     */
    public function version(Event $event): string
    {
        $ids = EventFinish::where('event_id', $event->id)
            ->orderBy('finished_at')
            ->orderBy('created_at')
            ->pluck('id')
            ->implode(',');

        // Read back off the database rather than the instance: a channel is
        // asked about an event it loaded when the connection opened, and a
        // close that happened thirty seconds into it is precisely what this
        // is here to notice.
        $closedAt = Event::whereKey($event->id)->value('closed_at');

        // The contender cutoff too, so that a claim being approved or
        // rejected — which settles places without adding or removing a
        // finish — still wakes every open page. Without it a provisional
        // podium would stay provisional on screen until the next reload.
        $contenderAt = $this->earliestContenderAt($event);

        return $ids.'#'.($closedAt ?? '').'#'.($contenderAt?->getTimestamp() ?? '');
    }

    /**
     * Where a given finish sits, 1-based.
     *
     * Counted with the same tiebreak the podium is ordered by, rather than a
     * plain `<=`: two competitors can submit inside the same second, and a
     * count that treated them as level would report two second places and no
     * first.
     */
    private function placeOf(Event $event, EventFinish $finish): int
    {
        return EventFinish::where('event_id', $event->id)
            ->where(fn ($q) => $q
                ->where('finished_at', '<', $finish->finished_at)
                ->orWhere(fn ($tie) => $tie
                    ->where('finished_at', $finish->finished_at)
                    ->where('created_at', '<=', $finish->created_at)))
            ->count();
    }

    /**
     * Tell the people who are not looking at the page.
     *
     * Push and Discord only — deliberately no mail. A finish is news, but on
     * a CONTINUE event there is one of these per competitor, and a mailshot
     * per person who gets home is how an event's participants learn to filter
     * the whole app into a folder. The closing announcement under STOP is the
     * one that mails, and it goes out through EventNotificationService like
     * every other status change.
     */
    private function announceFinish(Event $event, EventFinish $finish, int $place): void
    {
        $name = $finish->label();

        $this->discord->announce($event, trans(
            'notifications.discord_event_finished',
            ['event' => $event->title, 'name' => $name, 'place' => Ordinal::of($place)],
        ).' '.route('events.show', $event));

        $this->push->toParticipants(
            $event,
            new PushMessage(
                title: trans('notifications.push_finish_title', ['event' => $event->title]),
                body: trans(
                    'notifications.push_finish_body',
                    ['name' => $name, 'place' => Ordinal::of($place)],
                ),
                path: "/events/{$event->id}",
                // The category that already exists for exactly this — how it
                // ended — rather than EVENT_STATUS, which is about a host
                // pausing or cancelling something.
                category: NotificationCategory::EVENT_RESULT,
                // Per event, so a card where four teams finish in one evening
                // collapses to one line on a lock screen instead of four.
                tag: 'finish:'.$event->id,
            ),
            // The finisher already knows: they are looking at the page that
            // just told them. Only a solo finish has one person to leave out
            // — a team's finish is news to every member, including whoever
            // ticked the square.
            except: $finish->user,
        );
    }

    /**
     * "It is over, and X won" — said once, when the result is actually
     * settled rather than when the first approval happened to land.
     */
    private function announceClosed(Event $event, EventFinish $winner): void
    {
        $name = $winner->label();

        $this->discord->announce($event, trans(
            'notifications.discord_event_won',
            ['event' => $event->title, 'name' => $name],
        ).' '.route('events.show', $event));

        $this->push->toParticipants(
            $event,
            new PushMessage(
                title: trans('notifications.push_finish_title', ['event' => $event->title]),
                body: trans('notifications.push_finish_won_body', ['name' => $name]),
                path: "/events/{$event->id}",
                category: NotificationCategory::EVENT_RESULT,
                tag: 'finish:'.$event->id,
            ),
        );
    }

    /**
     * Every finish on this event, models rather than the display shape.
     *
     * @return Collection<int, EventFinish>
     */
    public function finishers(Event $event): Collection
    {
        return $event->finishes()->get();
    }

    /** Has this user (or their team) finished this event? */
    public function finishFor(Event $event, ?User $user): ?EventFinish
    {
        if ($user === null) {
            return null;
        }

        $competitor = $this->bingo->competitorFor($event, $user);

        if ($competitor === null) {
            return null;
        }

        return EventFinish::where('event_id', $event->id)
            ->where('user_id', $competitor['user_id'])
            ->where('team_id', $competitor['team_id'])
            ->first();
    }
}
