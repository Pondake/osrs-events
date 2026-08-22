<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\Event;
use App\Models\Task;
use App\Models\Tile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Ported from TilesService::upsert() — a tile is identified by (board_id,
 * position), not a pre-existing row: the old app never auto-generated a
 * full grid on board creation, GameBoard.vue just rendered placeholder
 * "empty-{position}" tiles for any position with no Tile row yet, and
 * clicking one created it via this same upsert. Ported that model exactly
 * rather than pre-seeding every position on board creation.
 */
class TileController extends Controller
{
    public function upsert(Request $request, Event $event): RedirectResponse
    {
        $this->assertCanEditEvent($request->user(), $event);

        // A bingo card and a race have no board, so there is no tile here to
        // upsert. Without this the identifying pair below is (null,
        // position) — which is not "no match", it is a row belonging to no
        // board, and the insert died on the NOT NULL constraint with a 500
        // rather than a 404.
        $board = $event->board;
        abort_unless($board !== null, 404);

        $lastPosition = $board->tileCount() - 1;

        $data = $request->validate([
            // Bounded by the board, not just by zero. A tile at position 99
            // on a 5x5 renders nowhere and counts in every query that asks
            // how many tiles a board has.
            'position' => ['required', 'integer', 'min:0', "max:{$lastPosition}"],
            'task_id' => ['nullable', 'uuid', 'exists:tasks,id'],
            'title_override' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'in:NORMAL,SNAKE,LADDER'],
            // Same bound: a snake pointing off the end of the board sends a
            // player somewhere that does not exist.
            'target_position' => ['nullable', 'integer', 'min:0', "max:{$lastPosition}"],
        ]);

        Tile::updateOrCreate(
            // A tile belongs to the BOARD — `tiles` has no event_id column
            // at all, so this identified nothing.
            ['board_id' => $board->id, 'position' => $data['position']],
            [
                'id' => (string) str()->uuid(),
                'task_id' => $data['task_id'] ?? null,
                'title_override' => $data['title_override'] ?? null,
                'type' => $data['type'],
                'target_position' => $data['target_position'] ?? null,
            ],
        );

        return back()->with('board-save', trans('admin.tile_saved'));
    }

    public function destroy(Event $event, Tile $tile): RedirectResponse
    {
        $this->assertCanEditEvent(Auth::user(), $event);

        // Against the BOARD's id, not the event's. Those coincide for rows
        // the split migration created, which is exactly why this needed
        // catching — on a newly created event they differ.
        //
        // The null check is separate on purpose: `null === null` is true, so
        // comparing straight against `$event->board?->id` would have let a
        // board-less event delete any tile that somehow had no board either.
        abort_unless($event->board !== null && $tile->board_id === $event->board->id, 404);

        $tile->delete();

        return back()->with('board-save', trans('admin.tile_cleared'));
    }

    /**
     * Lightweight task search, still used by Admin > Tasks.
     *
     * No longer offered in the tile and bingo-square pickers: those search
     * the OSRS Wiki only now, and the task table behind them is a cache of
     * it rather than a second place to look. Fourteen seeded rows presented
     * as a peer of the whole wiki was a choice with an obvious answer, and
     * making people make it was the cost.
     *
     * Deliberately NOT gated by canCreateTiles like Admin\TaskController:
     * assigning an existing task is a different action from creating one.
     */
    public function searchTasks(Request $request): JsonResponse
    {
        $search = $request->string('search')->toString();

        $tasks = Task::when($search, fn ($q) => $q->where('title', 'like', "%{$search}%"))
            ->orderBy('title')
            ->limit(20)
            ->get(['id', 'title', 'icon_url', 'description']);

        return response()->json($tasks);
    }
}
