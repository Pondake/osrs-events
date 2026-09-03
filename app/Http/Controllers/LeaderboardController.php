<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\Event;
use App\Services\BoardAccessService;
use App\Services\EventFinishService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/** Ported from PlayersService::getLeaderboard(). */
class LeaderboardController extends Controller
{
    public function show(Event $event, BoardAccessService $access, EventFinishService $finishes): Response|RedirectResponse
    {
        // canView, not hasAccess: a ranking is part of reading the event,
        // and a listed event is readable by anyone. Taking part is still
        // gated everywhere it is actually done.
        abort_unless($access->canView(Auth::user(), $event), 403);

        // This page IS the Snakes & Ladders ranking — who is furthest along
        // the track, and whether a snake or a ladder is coming. An event with
        // no board has no such thing, and rendering it anyway produced "No
        // players yet" on a bingo card with five people scoring on it. Their
        // standings live on the event page, so that is where this goes.
        if ($event->board === null) {
            return redirect()->route('events.show', $event);
        }

        // Tiles live on the board; an event without one has none.
        $tiles = $event->board?->tiles()->orderBy('position')->get() ?? collect();

        // Floored at zero, so a board whose grid is not filled in yet reports
        // nothing left rather than minus one tile left.
        $maxPosition = max($tiles->count() - 1, 0);

        // Who got home, and when. Keyed by whichever id the event's mode
        // makes the competitor, so a row can look itself up in one go.
        $finishRows = $event->finishes()->get();
        $finishByCompetitor = $finishRows
            ->mapWithKeys(fn ($finish, $index) => [
                ($finish->team_id ?? $finish->user_id) => [
                    'place' => $index + 1,
                    'at' => $finish->finished_at,
                ],
            ]);

        $playerBoards = $event->playerBoards()
            ->with(['user', 'team'])
            // Qualified — playerBoards() joins boards, so a bare
            // column name is ambiguous.
            ->orderByDesc('player_boards.current_position')
            ->get();

        // Two tiers, because one was wrong. Ordering by position alone put
        // everybody parked on the final tile in a heap at the top with no
        // tiebreak at all — including people who had merely walked onto it
        // and never completed it, ranked level with whoever had actually
        // finished hours earlier. Finishers first, in the order they got
        // home; everyone else behind them, still by how far along they are.
        $ordered = $playerBoards
            ->sortBy(function ($pb) use ($event, $finishByCompetitor) {
                $finish = $finishByCompetitor[$this->competitorKey($event, $pb)] ?? null;

                // One sortable key rather than a chain of comparators: a
                // finisher sorts on its place (1, 2, 3…), everyone else on a
                // number that is always larger and grows as their remaining
                // distance does. Ties inside each half keep the order the
                // query returned them in, which is already position-desc.
                return $finish !== null
                    ? $finish['place']
                    : PHP_INT_MAX - $pb->current_position;
            })
            ->values();

        // Progress is public on any listed event; who is making it is not,
        // unless the event is OPEN or this reader is in it. The same rule
        // BoardController::show() applies to the pieces on the board — and
        // this page went without it entirely, which made it the worse of the
        // two leaks: the board at least anonymised, while the ranking of the
        // same event published every player's Discord name and avatar to
        // anyone who could open it. On a listed invite-only event that is the
        // clan roster, on the one page whose whole subject is the roster.
        $namesArePublic = $access->canSeeParticipants(Auth::user(), $event);

        $entries = $ordered->map(function ($pb, $index) use ($event, $tiles, $maxPosition, $finishByCompetitor, $namesArePublic) {
            $pathTiles = $tiles->filter(fn ($t) => $t->position > $pb->current_position && $t->position <= $maxPosition);
            $finish = $finishByCompetitor[$this->competitorKey($event, $pb)] ?? null;

            return [
                'rank' => $index + 1,
                'playerId' => $pb->id,
                // Named fields, not the models.
                //
                // This handed the browser the whole User row. Only password
                // and remember_token are marked hidden, so the email address
                // went out with it — and any account can open the leaderboard
                // of any open event, which made this an email directory for
                // everyone playing. Whatever a page needs about a person, it
                // should have to name.
                'user' => ! $namesArePublic || $pb->user === null ? null : [
                    'nickname' => $pb->user->nickname,
                    'discord_username' => $pb->user->discord_username,
                    'avatar_url' => $pb->user->avatar_url,
                ],
                'team' => ! $namesArePublic || $pb->team === null ? null : [
                    'name' => $pb->team->name,
                    'icon_url' => $pb->team->icon_url,
                    'guild_icon_url' => $pb->team->guild_icon_url,
                ],
                'currentPosition' => $pb->current_position,
                'tilesRemaining' => $maxPosition - $pb->current_position,
                // The place they finished in, or null for still playing.
                // Separate from `rank` on purpose: rank is where this row
                // sits in this list, and place is a result that does not
                // change when somebody else joins.
                'finishPlace' => $finish['place'] ?? null,
                // Guarded on `$finish` itself, not only on the key: `null['at']`
                // is a fatal "trying to access array offset on null", and every
                // player who has not finished takes this branch — which is most
                // of them, for most of an event's life.
                'finishedAt' => $finish === null ? null : $finish['at']?->toIso8601String(),
                'pathHasLadder' => $pathTiles->contains(fn ($t) => $t->type === 'LADDER' && $t->target_position !== null),
                'pathHasSnake' => $pathTiles->contains(fn ($t) => $t->type === 'SNAKE' && $t->target_position !== null),
            ];
        });

        return Inertia::render('Boards/Leaderboard', [
            'board' => $event->only(['id', 'title', 'mode', 'closed_at', 'finish_rule']),
            'totalTiles' => $tiles->count(),
            'entries' => $entries,
            // Said explicitly rather than inferred from a null user: a row
            // with no user is otherwise indistinguishable from a deleted
            // account, and the page would label every anonymous player gone.
            // Same prop, same reason, as BoardShow.
            'namesArePublic' => $namesArePublic,
            'finishes' => $finishes->places($event, $namesArePublic),
        ]);
    }

    /**
     * What a finish is recorded against for this event: the team on a TEAM
     * event, the player on a SOLO one — the same either/or every other
     * competitor-keyed table in the app uses.
     */
    private function competitorKey(Event $event, $playerBoard): ?string
    {
        return $event->mode === 'TEAM' ? $playerBoard->team_id : $playerBoard->user_id;
    }
}
