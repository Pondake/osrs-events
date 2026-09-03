<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\BoardTeam;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Team;
use App\Models\TeamMember;
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
     * @param  string|null  $teamId       a team of theirs to bring in with
     *                                    them, for a team event they may
     *                                    join but no team of theirs is in
     *
     * @throws ValidationException when they may not join, when a team event
     *                             is not theirs to play, or when a race
     *                             cannot enter them
     */
    public function join(User $user, Event $event, ?string $tokenOrCode = null, ?string $teamId = null): void
    {
        // A paused event takes no new entries. Leaving one is still allowed
        // — being stuck in something that has stopped, with no way out until
        // a host comes back, is the worse half of the trade.
        if ($event->isPaused()) {
            throw ValidationException::withMessages(['participation' => trans('events.paused_notice')]);
        }

        // Throws when the event is not theirs to join. Idempotent for
        // somebody who already has access.
        $this->access->joinEvent($user, $event, $tokenOrCode);

        // A team event is played per team, so joining without one buys
        // nothing: no board is created, no score is kept, and every control
        // on the page then refuses. This used to be allowed and answered with
        // "you are in, a host has to put you on a team" — but hosts add
        // teams, not people, so that was advice nobody could act on. The team
        // is the way in, and it comes first.
        // Brought in on the way past, so the guard below sees it. Only ever
        // a team this person runs — see enterTeam().
        if ($teamId !== null) {
            $this->enterTeam($user, $event, $teamId);
        }

        if ($this->needsTeam($user, $event)) {
            throw ValidationException::withMessages(['participation' => trans('events.team_required_notice')]);
        }

        // A race enters before the row is written, because entering can fail
        // — no RSN, or a name somebody else already races under — and being
        // listed as a participant of a race you are not competing in is a
        // worse state than not having joined at all.
        if ($event->needsMetric()) {
            $this->enterRace($user, $event);
        }

        EventParticipant::firstOrCreate(['event_id' => $event->id, 'user_id' => $user->id]);

        // A board to play on, from the same place a roll would have created
        // one. Never null past the team guard above: a solo event makes one
        // per player, a team event one per team.
        $this->playerBoards->getOrCreate($event, $user);
    }

    /** Whether this user is on one of the teams taking part. */
    private function hasTeam(User $user, Event $event): bool
    {
        return $event->eventTeams()
            ->whereHas('team.members', fn ($q) => $q->where('user_id', $user->id))
            ->exists();
    }

    /** A team event with no team of theirs in it — nothing to play as. */
    public function needsTeam(User $user, Event $event): bool
    {
        return $event->mode === 'TEAM' && ! $this->hasTeam($user, $event);
    }

    /**
     * The teams this person could bring into the event themselves.
     *
     * Only the ones they own or manage, and only those not already taking
     * part. Entering a team commits that whole team's score to the event, so
     * it is the team's own call — a plain member is told to ask rather than
     * handed a button that speaks for everybody else. Hosts keep the
     * assignment tab for the curated case.
     *
     * @return list<array{id: string, name: string, iconUrl: ?string, guildIconUrl: ?string}>
     */
    public function enterableTeams(User $user, Event $event): array
    {
        if ($event->mode !== 'TEAM') {
            return [];
        }

        return Team::query()
            ->whereHas('members', fn ($q) => $q
                ->where('user_id', $user->id)
                ->whereIn('role', TeamMember::MANAGING_ROLES))
            ->whereNotIn('id', $event->eventTeams()->pluck('team_id'))
            ->orderBy('name')
            ->get()
            ->map(fn (Team $team) => [
                'id' => $team->id,
                'name' => $team->name,
                'iconUrl' => $team->icon_url,
                'guildIconUrl' => $team->guild_icon_url,
            ])
            ->all();
    }

    /**
     * Adds one of the user's own teams to the event.
     *
     * Managing the team is the authority here, not editing the event: the
     * access check above already established that this person may join, and
     * what is being decided is whether a team takes part — which belongs to
     * whoever runs it. Recorded under the same audit action a host's own
     * assignment uses, because it is the same fact.
     *
     * @throws ValidationException when the team is not theirs to enter
     */
    private function enterTeam(User $user, Event $event, string $teamId): void
    {
        $team = Team::find($teamId);

        if ($event->mode !== 'TEAM' || $team === null || ! $team->isManagedBy($user)) {
            throw ValidationException::withMessages(['team_id' => trans('events.team_not_yours')]);
        }

        BoardTeam::firstOrCreate(
            ['event_id' => $event->id, 'team_id' => $team->id],
            ['id' => (string) str()->uuid()],
        );

        AuditLog::record('board.team_added', $event, [], $team);
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
