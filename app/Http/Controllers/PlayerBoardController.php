<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\CompletedTile;
use App\Models\PlayerBoard;
use App\Models\Tile;
use App\Services\BoardAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Ported from the old PlayersService — SOLO mode only (getOrCreatePlayerBoard's
 * TEAM-mode branch, which shares one PlayerBoard per team via BoardTeam/TeamMember,
 * is not ported yet since Team pages aren't built — see docs/backlog.md).
 */
class PlayerBoardController extends Controller
{
    private function playerBoardFor(Board $board): PlayerBoard
    {
        return PlayerBoard::firstOrCreate(
            ['user_id' => Auth::id(), 'board_id' => $board->id],
            ['id' => (string) str()->uuid(), 'current_position' => 0],
        );
    }

    /**
     * Roll a d6, move the player, apply snake/ladder jumps, enforce the
     * board's daily roll limit. Landing on a snake un-completes every tile
     * between the snake's head and its target — same as the old
     * rollDice()'s "slide back down" behavior.
     */
    public function roll(Board $board, BoardAccessService $access): RedirectResponse
    {
        abort_unless($access->hasAccess(Auth::user(), $board), 403);

        $tiles = $board->tiles()->orderBy('position')->get();
        $maxPosition = $tiles->count() - 1;

        $playerBoard = $this->playerBoardFor($board);

        if ($board->dice_roll_limit !== null) {
            $isToday = $playerBoard->last_roll_date?->isToday() ?? false;
            $rollsToday = $isToday ? $playerBoard->dice_rolls_today : 0;

            if ($rollsToday >= $board->dice_roll_limit) {
                return back()->with('board-save-error', "You've reached today's roll limit ({$board->dice_roll_limit}/day).");
            }
        }

        $rolled = random_int(1, 6);
        $previousPosition = $playerBoard->current_position;
        $newPosition = min($previousPosition + $rolled, $maxPosition);
        $landedOn = $newPosition;

        $tile = $tiles->firstWhere('position', $newPosition);
        $jump = null;

        if ($tile && $tile->target_position !== null) {
            if ($tile->type === 'SNAKE') {
                $newPosition = $tile->target_position;
                $jump = 'snake';
            } elseif ($tile->type === 'LADDER') {
                $newPosition = $tile->target_position;
                $jump = 'ladder';
            }
        }

        DB::transaction(function () use ($playerBoard, $newPosition, $jump, $landedOn, $tiles) {
            $isToday = $playerBoard->last_roll_date?->isToday() ?? false;

            $playerBoard->update([
                'current_position' => $newPosition,
                'dice_rolls_today' => $isToday ? $playerBoard->dice_rolls_today + 1 : 1,
                'last_roll_date' => now(),
            ]);

            if ($jump === 'snake') {
                $tileIdsToUncomplete = $tiles
                    ->filter(fn ($t) => $t->position >= $newPosition && $t->position <= $landedOn)
                    ->pluck('id');

                CompletedTile::where('player_board_id', $playerBoard->id)
                    ->whereIn('tile_id', $tileIdsToUncomplete)
                    ->delete();
            }
        });

        return back()->with('board-save', "Rolled a {$rolled}" . ($jump ? " and hit a {$jump}!" : '.'));
    }

    public function toggleTile(Board $board, Tile $tile, BoardAccessService $access): RedirectResponse
    {
        abort_unless($access->hasAccess(Auth::user(), $board), 403);

        $playerBoard = $this->playerBoardFor($board);

        $existing = CompletedTile::where('player_board_id', $playerBoard->id)
            ->where('tile_id', $tile->id)
            ->first();

        if ($existing) {
            $existing->delete();
        } else {
            CompletedTile::create([
                'id' => (string) str()->uuid(),
                'player_board_id' => $playerBoard->id,
                'tile_id' => $tile->id,
                'completed_via' => 'MANUAL',
            ]);
        }

        return back();
    }
}
