<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Services\BoardAccessService;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/** Ported from PlayersService::getLeaderboard(). */
class LeaderboardController extends Controller
{
    public function show(Board $board, BoardAccessService $access): Response
    {
        abort_unless($access->hasAccess(Auth::user(), $board), 403);

        $tiles = $board->tiles()->orderBy('position')->get();
        $maxPosition = $tiles->count() - 1;

        $playerBoards = $board->playerBoards()
            ->with(['user', 'team'])
            ->orderByDesc('current_position')
            ->get();

        $entries = $playerBoards->values()->map(function ($pb, $index) use ($tiles, $maxPosition) {
            $pathTiles = $tiles->filter(fn ($t) => $t->position > $pb->current_position && $t->position <= $maxPosition);

            return [
                'rank' => $index + 1,
                'playerId' => $pb->id,
                'user' => $pb->user,
                'team' => $pb->team,
                'currentPosition' => $pb->current_position,
                'tilesRemaining' => $maxPosition - $pb->current_position,
                'pathHasLadder' => $pathTiles->contains(fn ($t) => $t->type === 'LADDER' && $t->target_position !== null),
                'pathHasSnake' => $pathTiles->contains(fn ($t) => $t->type === 'SNAKE' && $t->target_position !== null),
            ];
        });

        return Inertia::render('Boards/Leaderboard', [
            'board' => $board->only(['id', 'title', 'mode']),
            'totalTiles' => $tiles->count(),
            'entries' => $entries,
        ]);
    }
}
