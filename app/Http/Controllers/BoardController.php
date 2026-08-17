<?php

namespace App\Http\Controllers;

use App\Models\Board;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class BoardController extends Controller
{
    /**
     * Deliberately small slice of frontend/app/pages/boards/[id]/index.vue —
     * grid + own progress only, no edit mode / settings modal / dice rolling
     * / other-players toggle. Scoped to answer the actual evaluation
     * question (does an authenticated, session-dependent, Eloquent-backed
     * page survive Inertia SSR — see resources/js/ssr.js's own comment on
     * why this is the harder case than the static landing page), not to
     * reach feature parity.
     */
    public function show(Board $board): Response
    {
        $tiles = $board->tiles()->get(['id', 'position', 'title_override', 'type', 'target_position']);

        $playerBoard = Auth::check()
            ? $board->playerBoards()
                ->where('user_id', Auth::id())
                ->with('completedTiles:id,player_board_id,tile_id')
                ->first()
            : null;

        return Inertia::render('BoardShow', [
            'board' => $board->only(['id', 'title', 'description', 'size', 'mode']),
            'tiles' => $tiles,
            'playerBoard' => $playerBoard === null ? null : [
                ...$playerBoard->only(['current_position', 'dice_rolls_today']),
                'completedTileIds' => $playerBoard->completedTiles->pluck('tile_id'),
            ],
        ]);
    }
}
