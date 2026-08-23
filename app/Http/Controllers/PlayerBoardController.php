<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\CompletedTile;
use App\Models\Event;
use App\Models\EventParticipant;
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

        // Paused is not ended: the board stays readable, the standings stay
        // up, and nothing anyone did is lost — but nobody moves until the
        // host says so. Checked here rather than in the service because the
        // answer a player needs is a toast on the page they are already on.
        if ($event->isPaused()) {
            return back()->with('board-save-error', trans('events.paused_notice'));
        }

        // Tiles hang off the board, not the event — an event type without a
        // board has none.
        $tiles = $event->board?->tiles()->orderBy('position')->get() ?? collect();

        // Floored at zero: on a board whose grid has not been filled in yet,
        // `count() - 1` is -1, and min() then walked the player to position
        // -1 — off the front of a board they had not started.
        $maxPosition = max($tiles->count() - 1, 0);

        // Playing is joining. The button is the deliberate way in, but
        // somebody who rolls has said the same thing more plainly, and a
        // player missing from the participant list because they never pressed
        // it would be a worse kind of wrong than an unasked-for row.
        EventParticipant::firstOrCreate(['event_id' => $event->id, 'user_id' => Auth::id()]);

        $playerBoard = $playerBoards->getOrCreate($event, Auth::user());
        if ($playerBoard === null) {
            return back()->with('board-save-error', trans('events.no_team_yet'));
        }

        // Roll limit is board mechanics, so it moved with the board.
        $rollLimit = $event->board?->dice_roll_limit;

        if ($rollLimit !== null) {
            $isToday = $playerBoard->last_roll_date?->isToday() ?? false;
            $rollsToday = $isToday ? $playerBoard->dice_rolls_today : 0;

            if ($rollsToday >= $rollLimit) {
                return back()->with('board-save-error', trans('board.roll_limit_reached', ['limit' => $rollLimit]));
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
            ->with('board-save', $jump
                ? trans('board.rolled_with_jump', ['n' => $rolled, 'jump' => $jump])
                : trans('board.rolled', ['n' => $rolled]))
            ->with('last-roll', $rolled);
    }

    public function toggleTile(Event $event, Tile $tile, BoardAccessService $access, PlayerBoardService $playerBoards): RedirectResponse
    {
        abort_unless($access->hasAccess(Auth::user(), $event), 403);

        // The tile is bound by id alone, so nothing about the route says it
        // belongs to the board being played. Without this, a tile id from
        // any other board ticked off here — and counted towards progress
        // here, which on a competitive board is a way to win without
        // playing. Written with an explicit null check rather than
        // `$event->board?->id`, because null === null is true and a
        // board-less event would match every board-less tile.
        abort_unless($event->board !== null && $tile->board_id === $event->board->id, 404);

        // On hold, same as roll().
        if ($event->isPaused()) {
            return back()->with('board-save-error', trans('events.paused_notice'));
        }

        // Same as roll(): ticking a tile off is playing, so it joins.
        EventParticipant::firstOrCreate(['event_id' => $event->id, 'user_id' => Auth::id()]);

        $playerBoard = $playerBoards->getOrCreate($event, Auth::user());
        if ($playerBoard === null) {
            return back()->with('board-save-error', trans('events.no_team_yet'));
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
