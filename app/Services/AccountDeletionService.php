<?php

namespace App\Services;

use App\Models\BingoCompletion;
use App\Models\BoardAuthor;
use App\Models\BoardInvite;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\EventStanding;
use App\Models\PlayerBoard;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Closing an account, and everything that has to be decided first.
 *
 * The hard part is not the delete — it is that one account can be the only
 * person able to run things other people are still in. An event with thirty
 * players has an owner; a team has an owner; and "delete my account" cannot
 * mean "and take everyone's evening with it" by default, nor can it mean
 * "you may not leave until you find a replacement". So the flow is: **say what
 * is attached, make the unavoidable choices explicit, then act.**
 *
 * Four outcomes for the things attached to an account:
 *
 *  - **Handed over.** Anything still running that somebody else can take.
 *  - **Ended.** A live event with nobody to take it (or whose owner would
 *    rather stop it than hand it off), frozen in place rather than hidden.
 *    Its `end_date` is pulled to now if it hasn't already passed, so every
 *    rule that already refuses a move on an ended event (`Event::isEnded()`)
 *    starts refusing this one too — but the row itself, its board, and
 *    everyone's progress on it all stay exactly as they were. The event
 *    keeps its place in every listing; only the ownership row goes with it,
 *    explicitly — not left to the account-deletion cascade, since this
 *    outcome is also reachable on its own (see `settleOneEvent()`) with the
 *    account never actually being deleted at all. This is what "Kept,
 *    anonymised" below already does for an event that finished on its own;
 *    this outcome is choosing that ending early for one that hadn't.
 *  - **Deleted.** For when ending it in place isn't enough and the owner
 *    wants the event and everyone's progress on it actually gone — not
 *    merely frozen. Every `PlayerBoard`/`CompletedTile` for its board, every
 *    `EventStanding`, every `BingoCompletion` on its card, and its
 *    `EventParticipant` rows are deleted outright; the event ROW itself is
 *    still soft-deleted afterwards rather than hard-deleted, so an admin
 *    restoring it from `/admin/events` gets back an empty shell — its
 *    settings, not a resurrection of what it just destroyed. **Genuinely
 *    irreversible for everyone who played it**, which is why it is offered
 *    as a distinct, separately-labelled choice from Ended rather than being
 *    what "delete" quietly does by default.
 *  - **Kept, anonymised.** Anything already finished. A race that ended in July
 *    had a winner and still does; the row keeps its OSRS name and loses its
 *    link to the account. On screen that reads as a deleted player, which is
 *    what actually happened.
 *
 * Everything genuinely personal — sessions, push subscriptions, Discord server
 * cache, roles, event access — goes, via the cascades already on those tables.
 */
class AccountDeletionService
{
    /**
     * What the account still holds that somebody has to decide about.
     *
     * Read-only, and safe to call as often as the page renders. The shape is
     * what the settings page needs: for each thing, who could take it and what
     * it would cost to end it instead.
     *
     * @return array{events: array<int, array<string, mixed>>, teams: array<int, array<string, mixed>>, keptEvents: int}
     */
    public function preflight(User $user): array
    {
        return [
            'events' => $this->ownedLiveEvents($user)->map(fn (Event $event) => [
                'id' => $event->id,
                'title' => $event->title,
                'type' => $event->type,
                'endsAt' => $event->end_date?->toDateString(),
                'participants' => $event->participants()->count(),
                'candidates' => $this->eventCandidates($event, $user),
            ])->values()->all(),

            'teams' => $this->ownedTeams($user)->map(fn (Team $team) => [
                'id' => $team->id,
                'name' => $team->name,
                'members' => $team->members()->where('user_id', '!=', $user->id)->count(),
                'candidates' => $this->teamCandidates($team, $user),
            ])->values()->all(),

            // Informational: how much stays behind as anonymised history, so
            // "everything related is deleted" is not read as a promise the
            // next paragraph quietly breaks.
            'keptEvents' => $this->finishedEvents($user)->count(),
        ];
    }

    /**
     * Events this account owns that are still going, or have not started.
     *
     * The line is **whether it has ended**, not how old it is. A finished event
     * needs no owner: nothing about it can change any more, and an admin can
     * still reach it from /admin/events if something has to. One that is still
     * running needs somebody who can pause it, review claims and end it.
     *
     * @return Collection<int, Event>
     */
    public function ownedLiveEvents(User $user): Collection
    {
        return Event::query()
            ->whereHas('authors', fn ($q) => $q->where(['user_id' => $user->id, 'is_owner' => true]))
            ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', Carbon::now()->startOfDay()))
            ->with('participants')
            ->get();
    }

    /** @return Collection<int, Event> */
    private function finishedEvents(User $user): Collection
    {
        return Event::query()
            ->where(fn ($q) => $q
                ->whereHas('authors', fn ($a) => $a->where('user_id', $user->id))
                ->orWhereHas('participants', fn ($p) => $p->where('user_id', $user->id)))
            ->whereNotNull('end_date')
            ->where('end_date', '<', Carbon::now()->startOfDay())
            ->get();
    }

    /** @return Collection<int, Team> */
    public function ownedTeams(User $user): Collection
    {
        return Team::query()
            ->whereHas('members', fn ($q) => $q->where(['user_id' => $user->id, 'role' => TeamMember::OWNER]))
            ->with('members.user')
            ->get();
    }

    /**
     * Who could take this event on.
     *
     * Co-hosts first — they already run it, so handing over changes nothing
     * anybody would notice. Then participants, because on a solo-hosted event
     * they are the only people with any claim to it. A list of everyone on the
     * site would be worse than an empty one: it invites handing your clan's
     * event to a stranger.
     *
     * @return array<int, array<string, string>>
     */
    private function eventCandidates(Event $event, User $user): array
    {
        $coHosts = $event->authors()
            ->where('user_id', '!=', $user->id)
            ->with('user')
            ->get()
            ->pluck('user')
            ->filter();

        $candidates = $coHosts->isNotEmpty()
            ? $coHosts
            : $event->participants()->where('user_id', '!=', $user->id)->with('user')->get()->pluck('user')->filter();

        return $candidates
            ->unique('id')
            ->map(fn (User $candidate) => [
                'id' => $candidate->id,
                'name' => $candidate->displayName(),
                // Named so the person choosing knows whether they are handing
                // it to somebody who already runs it or to a player.
                'role' => $coHosts->contains('id', $candidate->id) ? 'cohost' : 'participant',
            ])
            ->values()
            ->all();
    }

    /**
     * Who could take this team on.
     *
     * Managers first, same reasoning as `eventCandidates()`'s co-hosts: a
     * promoted manager already has the run of the team (rename it, move
     * members around — see `Team::isManagedBy()`), so handing over to one
     * changes nothing anybody would notice. Falls back to every other member
     * only when there is no manager to hand it to.
     *
     * @return array<int, array<string, string>>
     */
    private function teamCandidates(Team $team, User $user): array
    {
        $others = $team->members->where('user_id', '!=', $user->id);

        $managers = $others->whereIn('role', TeamMember::MANAGING_ROLES);

        return ($managers->isNotEmpty() ? $managers : $others)
            ->map(fn (TeamMember $member) => [
                'id' => $member->user_id,
                'name' => $member->user?->displayName() ?? '',
                'role' => strtolower($member->role),
            ])
            ->filter(fn (array $candidate) => $candidate['name'] !== '')
            ->values()
            ->all();
    }

    /**
     * Do it.
     *
     * `$events` and `$teams` map an id to either a user id to hand it to, or
     * the string 'delete'. Anything the account owns and still runs that is
     * missing from those maps is an unanswered question, so this throws rather
     * than guessing — a default here would be either "silently delete other
     * people's event" or "silently hand it to somebody", and both are worse
     * than a failed request.
     *
     * One transaction. A half-deleted account — events handed over, the account
     * still there — is a worse state than either end of the operation.
     *
     * @param  array<string, string>  $events  event id => user id | 'delete'
     * @param  array<string, string>  $teams   team id  => user id | 'delete'
     */
    public function delete(User $user, array $events = [], array $teams = []): void
    {
        $this->assertEveryChoiceMade($user, $events, $teams);

        DB::transaction(function () use ($user, $events, $teams) {
            $this->settleEvents($user, $events);
            $this->settleTeams($user, $teams);
            $this->keepHistory($user);

            // Last, so every decision above is already recorded against rows
            // that still point at a real account.
            $user->delete();
        });
    }

    /** @param array<string, string> $events */
    private function assertEveryChoiceMade(User $user, array $events, array $teams): void
    {
        $missingEvents = $this->ownedLiveEvents($user)->pluck('id')->diff(array_keys($events));
        $missingTeams = $this->ownedTeams($user)->pluck('id')->diff(array_keys($teams));

        if ($missingEvents->isNotEmpty() || $missingTeams->isNotEmpty()) {
            throw new \InvalidArgumentException(
                'Every owned event and team needs a decision before the account can be deleted.'
            );
        }
    }

    /** @param array<string, string> $choices */
    private function settleEvents(User $user, array $choices): void
    {
        foreach ($this->ownedLiveEvents($user) as $event) {
            $this->settleOneEvent($user, $event, $choices[$event->id] ?? 'end');
        }
    }

    /**
     * One event, decided and acted on immediately — the same logic
     * `settleEvents()` runs per item inside the account-delete transaction,
     * exposed on its own for the settings page's per-item "confirm" action.
     * That action settles a single event right away, independent of whether
     * the account is ever actually deleted — asked for explicitly, so
     * somebody with many owned events isn't stuck deciding all of them in
     * one sitting right before an irreversible account close.
     *
     * Callers must have already checked this event is one of the user's own
     * `ownedLiveEvents()` — this does not re-check, since both existing call
     * sites (the bulk delete flow and `AccountController`'s standalone
     * endpoint) already do.
     */
    public function settleOneEvent(User $user, Event $event, string $choice): void
    {
        DB::transaction(function () use ($user, $event, $choice) {
            if ($choice === 'delete') {
                $this->deleteEventAndProgress($event);

                return;
            }

            if ($choice === 'end') {
                $this->endEventInPlace($event, $user);

                return;
            }

            $this->handEventTo($event, $choice, $user);
        });
    }

    /**
     * Freeze it in place rather than hiding it. The event, its board and
     * everyone's progress on it are untouched — only its own end date moves,
     * and only if it hasn't already passed. `Event::isEnded()` already
     * refuses a roll or a tile-complete on anything past its end date (see
     * PlayerBoardController), so this is the whole mechanism: no separate
     * "ended by account deletion" flag to keep in sync with that rule.
     *
     * The ownership row IS explicitly removed here, not left to a later
     * cascade — `settleOneEvent()` is also reachable with the account never
     * actually being deleted, so there is no guaranteed later moment for
     * `board_authors.user_id`'s `cascadeOnDelete` to fire. Harmless when the
     * account IS about to be deleted too: the row would disappear a moment
     * later either way.
     *
     * Note what this does NOT do, since it was asked about directly: there is
     * no "deleted player" placeholder left in the event's editors list the
     * way an anonymised participant/standing gets one. `board_authors.user_id`
     * is `NOT NULL` — there is no nullable slot to anonymise into — so the
     * row is removed outright and the departed owner simply stops appearing
     * as an editor, the same as if they had never been added. A leaderboard
     * row survives because the numbers it carries (an OSRS name, a rank) are
     * still meaningful with no account behind them; an editors list has
     * nothing left to say once someone can no longer edit.
     */
    private function endEventInPlace(Event $event, User $user): void
    {
        if ($event->end_date === null || $event->end_date->isFuture()) {
            $event->end_date = Carbon::now();
            $event->save();
        }

        BoardAuthor::where(['event_id' => $event->id, 'user_id' => $user->id])->delete();
    }

    /**
     * The destructive one. Deletes every row that IS somebody's progress —
     * not the event's own settings, which stay behind on the soft-deleted
     * shell exactly as every other event deletion here leaves them.
     */
    private function deleteEventAndProgress(Event $event): void
    {
        if ($event->board !== null) {
            // CompletedTile cascades off PlayerBoard's own FK — see
            // create_completed_tiles_table — so deleting these is deleting both.
            PlayerBoard::where('board_id', $event->board->id)->delete();
        }

        if ($event->bingoCard !== null) {
            BingoCompletion::whereIn('bingo_square_id', $event->bingoCard->squares()->pluck('id'))->delete();
        }

        EventStanding::where('event_id', $event->id)->delete();
        EventParticipant::where('event_id', $event->id)->delete();

        // Soft-deleted, like every other event deletion here: it drops out of
        // every list and 404s, and an admin can restore it — but everything
        // above is already gone by the time that happens, on purpose. What
        // comes back is the event's own settings, not what it did to everyone
        // else's progress.
        $event->delete();
    }

    private function handEventTo(Event $event, string $newOwnerId, User $user): void
    {
        // updateOrCreate rather than create: the new owner is usually already
        // an author, and a second row would break the unique (board, user).
        BoardAuthor::updateOrCreate(
            ['event_id' => $event->id, 'user_id' => $newOwnerId],
            ['is_owner' => true],
        );

        // The outgoing owner's authorship goes here rather than being left to
        // the cascade, so the event is never momentarily ownerless and the
        // unique index is never asked to hold two owners.
        BoardAuthor::where(['event_id' => $event->id, 'user_id' => $user->id])->delete();
    }

    /** @param array<string, string> $choices */
    private function settleTeams(User $user, array $choices): void
    {
        foreach ($this->ownedTeams($user) as $team) {
            $this->settleOneTeam($user, $team, $choices[$team->id] ?? 'delete');
        }
    }

    /** One team, decided and acted on immediately — see `settleOneEvent()`'s own note. */
    public function settleOneTeam(User $user, Team $team, string $choice): void
    {
        DB::transaction(function () use ($user, $team, $choice) {
            if ($choice === 'delete') {
                $team->members()->delete();
                $team->delete();

                return;
            }

            TeamMember::where(['team_id' => $team->id, 'user_id' => $choice])
                ->update(['role' => TeamMember::OWNER]);

            TeamMember::where(['team_id' => $team->id, 'user_id' => $user->id])->delete();
        });
    }

    /**
     * An admin removing somebody else's account.
     *
     * Deliberately **not** the same operation. The owner's flow asks who should
     * take over what, and an admin has no standing to answer that on their
     * behalf — so this makes no decisions: history is preserved exactly as
     * above, and anything the account still owns simply loses its owner. An
     * ownerless event stays reachable from /admin/events, which is where the
     * person running this already is.
     *
     * It exists at all because the delete crashed without it. `created_by` on
     * board_invites was NOT NULL with a RESTRICT foreign key, so removing any
     * account that had ever made an invite link failed outright — and until
     * self-serve deletion shipped, this was the *only* way an account could be
     * deleted at all.
     */
    public function deleteAsAdmin(User $user): void
    {
        DB::transaction(function () use ($user) {
            $this->keepHistory($user);

            $user->delete();
        });
    }

    /**
     * Cut the link, keep the record.
     *
     * Done here rather than by four `nullOnDelete` constraints so that "what
     * survives an account" is one readable list instead of a property of
     * scattered migrations. Everything not named here is personal to the
     * account and goes with it through the cascades already on those tables:
     * sessions, push subscriptions, the Discord server cache, roles, event
     * access, participation.
     */
    public function keepHistory(User $user): void
    {
        // A finished race keeps its leaderboard: `username` is the OSRS name it
        // was scored on, and it stays.
        EventStanding::where('user_id', $user->id)->update(['user_id' => null]);

        // A bingo card keeps its squares. `marked_by` and `reviewed_by` are
        // already nullOnDelete, so only the scoring link needs cutting.
        BingoCompletion::where('user_id', $user->id)->update(['user_id' => null]);

        // A board keeps the space somebody occupied on it.
        PlayerBoard::where('user_id', $user->id)->update(['user_id' => null]);

        // Not history so much as a foreign key that would otherwise refuse the
        // delete outright — see the migration. An invite outliving its author
        // is also simply true: a host can still see the link was handed out.
        BoardInvite::where('created_by', $user->id)->update(['created_by' => null]);
    }
}
