<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\Event;
use App\Models\CompletedTile;
use App\Models\Tile;
use App\Services\BoardAccessService;
use App\Services\PlayerBoardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/** Ported from the old PlayersService — see PlayerBoardService for the SOLO/TEAM split. */
class PlayerBoardController extends Controller
{
    /**
     * Roll a d6, move the player, apply snake/ladder jumps, enforce the
     * board's daily roll limit. Landing on a snake un-completes every tile
     * between the snake's head and its target — same as the old
     * rollDice()'s "slide back down" behavior.
     */
    public function roll(Event $event, BoardAccessService $access, PlayerBoardService $playerBoards): RedirectResponse
    {
        abort_unless($access->hasAccess(Auth::user(), $event), 403);

        // Tiles hang off the board, not the event — an event type without a
        // board has none.
        $tiles = $event->board?->tiles()->orderBy('position')->get() ?? collect();
        $maxPosition = $tiles->count() - 1;

        $playerBoard = $playerBoards->getOrCreate($event, Auth::user());
        if ($playerBoard === null) {
            return back()->with('board-save-error', "You don't have a team on this board yet.");
        }

        // Roll limit is board mechanics, so it moved with the board.
        $rollLimit = $event->board?->dice_roll_limit;

        if ($rollLimit !== null) {
            $isToday = $playerBoard->last_roll_date?->isToday() ?? false;
            $rollsToday = $isToday ? $playerBoard->dice_rolls_today : 0;

            if ($rollsToday >= $rollLimit) {
                return back()->with('board-save-error', "You've reached today's roll limit ({$rollLimit}/day).");
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

        // Separate from the board-save flash text — DiceRoller.vue needs the
        // raw number to pick which face to render, not a pre-formatted
        // sentence to parse back apart.
        return back()
            ->with('board-save', "Rolled a {$rolled}" . ($jump ? " and hit a {$jump}!" : '.'))
            ->with('last-roll', $rolled);
    }

    public function toggleTile(Event $event, Tile $tile, BoardAccessService $access, PlayerBoardService $playerBoards): RedirectResponse
    {
        abort_unless($access->hasAccess(Auth::user(), $event), 403);

        $playerBoard = $playerBoards->getOrCreate($event, Auth::user());
        if ($playerBoard === null) {
            return back()->with('board-save-error', "You don't have a team on this board yet.");
        }

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
