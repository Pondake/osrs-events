<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Joining and leaving an event — one action, every type.
 *
 * There used to be two half-answers. A race had "enter", which recorded a
 * standing; every other type had nothing, so taking part was inferred from
 * whatever you happened to write while playing, and a bingo player was not in
 * the event until they claimed a square. Asked for repeatedly, and fairly:
 * without this there is no honest list of what somebody is playing.
 *
 * The record is EventParticipant. What each type does on top of it stays
 * here, because it is the same decision in three shapes: a race baselines a
 * standing, a board hands out a player board, bingo needs nothing beyond the
 * row itself.
 *
 * Access is checked first and separately. Being allowed to look at an event
 * and choosing to play it are different questions, and the first one already
 * had an owner — BoardAccessService.
 */
class EventParticipationService
{
    public function __construct(
        private readonly BoardAccessService $access,
        private readonly PlayerBoardService $playerBoards,
        private readonly EventStandingsService $standings,
    ) {}

    public function has(?User $user, Event $event): bool
    {
        if ($user === null) {
            return false;
        }

        return EventParticipant::where(['event_id' => $event->id, 'user_id' => $user->id])->exists();
    }

    /**
     * @param  string|null  $tokenOrCode  an invite, for events that need one
     *
     * @throws ValidationException when they may not join, or a race cannot
     *                             enter them
     */
    public function join(User $user, Event $event, ?string $tokenOrCode = null): void
    {
        // Throws when the event is not theirs to join. Idempotent for
        // somebody who already has access.
        $this->access->joinEvent($user, $event, $tokenOrCode);

        // A race enters before the row is written, because entering can fail
        // — no RSN, or a name somebody else already races under — and being
        // listed as a participant of a race you are not competing in is a
        // worse state than not having joined at all.
        if ($event->needsMetric()) {
            $this->enterRace($user, $event);
        }

        EventParticipant::firstOrCreate(['event_id' => $event->id, 'user_id' => $user->id]);

        if ($event->board !== null) {
            // A board to play on, from the same place a roll would have
            // created one. TEAM mode returns null for somebody with no team,
            // which the board page has its own empty state for.
            $this->playerBoards->getOrCreate($event, $user);
        }
    }

    /**
     * @throws ValidationException when there is progress that leaving would
     *                             throw away
     */
    public function leave(User $user, Event $event): void
    {
        $this->guardProgress($user, $event);

        DB::transaction(function () use ($user, $event) {
            EventParticipant::where(['event_id' => $event->id, 'user_id' => $user->id])->delete();

            if ($event->needsMetric()) {
                $this->standings->leave($event, $user);
            }

            // The player board goes with them, but only because guardProgress
            // has already established there is nothing on it. A board still
            // at square one holds no history worth keeping.
            $this->playerBoards->find($event, $user)?->delete();
        });
    }

    /**
     * Leaving must not quietly delete something somebody did.
     *
     * A race is exempt: its standing is a baseline and a number pulled from
     * Wise Old Man, both of which rebuild themselves on the next entry, so
     * leaving and rejoining costs nothing but the gains since. Everything
     * else is a record of play.
     */
    private function guardProgress(User $user, Event $event): void
    {
        $playerBoard = $this->playerBoards->find($event, $user);

        if ($playerBoard !== null && ($playerBoard->current_position > 0 || $playerBoard->completedTiles()->exists())) {
            throw ValidationException::withMessages(['participation' => trans('events.leave_has_progress')]);
        }

        if ($event->type === 'BINGO' && $this->hasClaims($user, $event)) {
            throw ValidationException::withMessages(['participation' => trans('events.leave_has_progress')]);
        }
    }

    private function hasClaims(User $user, Event $event): bool
    {
        $card = $event->bingoCard;

        if ($card === null) {
            return false;
        }

        return $card->squares()
            ->whereHas('completions', fn ($q) => $q->where('user_id', $user->id))
            ->exists();
    }

    /** @throws ValidationException */
    private function enterRace(User $user, Event $event): void
    {
        $standing = $this->standings->enter($event, $user);

        if ($standing === null) {
            // The missing piece is over on the profile page, and the message
            // says so — there is no field on this page to hang it under.
            throw ValidationException::withMessages(['participation' => trans('events.enter_needs_rsn')]);
        }

        // Baseline now rather than leaving the row blank until the scheduled
        // sync gets to it: joining and then seeing "waiting for first sync"
        // for ten minutes reads as broken, and it is the first thing anyone
        // does on a race.
        //
        // Failure is deliberately not fatal — refresh() records the problem on
        // the row itself, the standings page renders that, and the scheduled
        // sync retries. Losing the entry because a third-party API blinked
        // would be the worse outcome.
        try {
            $this->standings->refresh($event, $standing);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
