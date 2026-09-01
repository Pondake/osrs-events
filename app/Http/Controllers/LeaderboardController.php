<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\Event;
use App\Services\BoardAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/** Ported from PlayersService::getLeaderboard(). */
class LeaderboardController extends Controller
{
    public function show(Event $event, BoardAccessService $access): Response|RedirectResponse
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

        $playerBoards = $event->playerBoards()
            ->with(['user', 'team'])
            // Qualified — playerBoards() joins boards, so a bare
            // column name is ambiguous.
            ->orderByDesc('player_boards.current_position')
            ->get();

        $entries = $playerBoards->values()->map(function ($pb, $index) use ($tiles, $maxPosition) {
            $pathTiles = $tiles->filter(fn ($t) => $t->position > $pb->current_position && $t->position <= $maxPosition);

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
                'user' => $pb->user === null ? null : [
                    'nickname' => $pb->user->nickname,
                    'discord_username' => $pb->user->discord_username,
                    'avatar_url' => $pb->user->avatar_url,
                ],
                'team' => $pb->team === null ? null : [
                    'name' => $pb->team->name,
                    'icon_url' => $pb->team->icon_url,
                ],
                'currentPosition' => $pb->current_position,
                'tilesRemaining' => $maxPosition - $pb->current_position,
                'pathHasLadder' => $pathTiles->contains(fn ($t) => $t->type === 'LADDER' && $t->target_position !== null),
                'pathHasSnake' => $pathTiles->contains(fn ($t) => $t->type === 'SNAKE' && $t->target_position !== null),
            ];
        });

        return Inertia::render('Boards/Leaderboard', [
            'board' => $event->only(['id', 'title', 'mode']),
            'totalTiles' => $tiles->count(),
            'entries' => $entries,
        ]);
    }
}
