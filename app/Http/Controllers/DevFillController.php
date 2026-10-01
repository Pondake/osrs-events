<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Task;
use App\Models\Tile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Local only: fill every empty square or tile with a random existing task,
 * so a test event is playable without setting it up by hand.
 */
class DevFillController extends Controller
{
    public function __invoke(Request $request, Event $event): RedirectResponse
    {
        abort_unless(app()->environment('local'), 404);
        $this->assertCanEditEvent($request->user(), $event);

        $taskIds = Task::query()->inRandomOrder()->pluck('id');

        if ($taskIds->isEmpty()) {
            return back()->with('board-save-error', trans('tile_list.fill_random_no_tasks'));
        }

        $next = fn (int $i) => $taskIds[$i % $taskIds->count()];
        $filled = 0;

        if ($event->type === 'BINGO' && $event->bingoCard) {
            $event->bingoCard->squares()
                ->whereNull('task_id')
                ->whereNull('title_override')
                ->where('is_wildcard', false)
                ->orderBy('position')
                ->get()
                ->each(function ($square) use ($next, &$filled) {
                    $square->update(['task_id' => $next($filled++)]);
                });
        }

        if ($event->type === 'SNAKES_LADDERS' && $event->board) {
            $board = $event->board;
            $existing = $board->tiles()->get()->keyBy('position');

            foreach (range(0, $board->tileCount() - 1) as $position) {
                $tile = $existing->get($position);

                if ($tile === null) {
                    Tile::create(['board_id' => $board->id, 'position' => $position, 'type' => 'NORMAL', 'task_id' => $next($filled++)]);
                } elseif ($tile->type === 'NORMAL' && $tile->task_id === null && $tile->title_override === null) {
                    $tile->update(['task_id' => $next($filled++)]);
                }
            }
        }

        return back()->with('board-save', trans('tile_list.fill_random_done', ['count' => $filled]));
    }
}
