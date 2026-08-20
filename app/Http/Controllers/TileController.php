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
        abort_unless($request->user()->canEditEvent($event), 403);

        $data = $request->validate([
            'position' => ['required', 'integer', 'min:0'],
            'task_id' => ['nullable', 'uuid', 'exists:tasks,id'],
            'title_override' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'in:NORMAL,SNAKE,LADDER'],
            'target_position' => ['nullable', 'integer', 'min:0'],
        ]);

        Tile::updateOrCreate(
            // A tile belongs to the BOARD — `tiles` has no event_id column
            // at all, so this identified nothing.
            ['board_id' => $event->board?->id, 'position' => $data['position']],
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
        abort_unless(Auth::user()->canEditEvent($event), 403);
        // Against the BOARD's id, not the event's. Those coincide for rows
        // the split migration created, which is exactly why this needed
        // catching — on a newly created event they differ.
        abort_unless($tile->board_id === $event->board?->id, 404);

        $tile->delete();

        return back()->with('board-save', trans('admin.tile_cleared'));
    }

    /**
     * Lightweight task search for the tile editor's autocomplete —
     * deliberately NOT gated by canCreateTiles like Admin\TaskController:
     * assigning an existing task to a tile is a different action from
     * creating new task templates, and any board editor needs it.
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
