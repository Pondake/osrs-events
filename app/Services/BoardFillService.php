<?php

namespace App\Services;

use App\Models\Board;
use App\Models\Task;
use App\Models\Tile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Fills the rest of a Snakes & Ladders board in one go.
 *
 * Hosts on production stopped after one to three tiles of 25 to 81. This puts
 * a task on every empty tile and, on a board that has none yet, lays out
 * snakes and ladders that follow Board::jumpError(). What the host already
 * set is never touched.
 */
class BoardFillService
{
    /** Snakes and ladders each, per board size: roughly one per row. */
    private const JUMPS = [
        'SIZE_5X5' => 2,
        'SIZE_7X7' => 4,
        'SIZE_9X9' => 6,
    ];

    /**
     * @return array{tasks: int, snakes: int, ladders: int}
     */
    public function fill(Board $board): array
    {
        return DB::transaction(function () use ($board) {
            $total = $board->tileCount();
            $tiles = $board->tiles()->get()->keyBy('position');

            [$snakes, $ladders] = $this->shouldPlaceJumps($board, $tiles)
                ? $this->placeJumps($board, $tiles, $total)
                : [0, 0];

            $tiles = $board->tiles()->get()->keyBy('position');
            $empty = collect(range(0, $total - 1))->filter(fn (int $position) => $this->isEmpty($tiles->get($position)));

            $taskIds = $this->taskIdsFor($empty->count(), $tiles);

            foreach ($empty->values() as $i => $position) {
                if (! isset($taskIds[$i])) {
                    break;
                }

                Tile::updateOrCreate(
                    ['board_id' => $board->id, 'position' => $position],
                    ['task_id' => $taskIds[$i], 'type' => 'NORMAL', 'target_position' => null],
                );
            }

            return ['tasks' => min($empty->count(), count($taskIds)), 'snakes' => $snakes, 'ladders' => $ladders];
        });
    }

    /**
     * Only on a board with no jumps yet, and before anybody has moved: a
     * host who placed their own wants to keep them, and a snake appearing
     * under someone mid-run changes a game already being played.
     */
    private function shouldPlaceJumps(Board $board, Collection $tiles): bool
    {
        return $tiles->doesntContain(fn (Tile $tile) => $tile->type !== 'NORMAL')
            && ! $board->playerBoards()->where('current_position', '>', 0)->exists();
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function placeJumps(Board $board, Collection $tiles, int $total): array
    {
        $cols = (int) sqrt($total);
        $count = self::JUMPS[$board->size] ?? 4;

        // Every tile already in a jump, as its start or its end. Keeping
        // them apart means a ladder never lands a player on a snake's head.
        $taken = [0 => true, $total - 1 => true];
        $placed = ['SNAKE' => 0, 'LADDER' => 0];

        foreach (['LADDER', 'SNAKE'] as $type) {
            $starts = collect(range(1, $total - 2))
                ->filter(fn (int $p) => $this->isEmpty($tiles->get($p)))
                ->shuffle();

            foreach ($starts as $start) {
                if ($placed[$type] >= $count) {
                    break;
                }

                if (isset($taken[$start])) {
                    continue;
                }

                // One to two rows up or down, so a jump is felt but never
                // decides the whole race on its own.
                $distance = random_int($cols, 2 * $cols);
                $target = $type === 'LADDER' ? $start + $distance : $start - $distance;

                if ($target < 0 || $target > $total - 1 || isset($taken[$target])) {
                    continue;
                }

                if ($board->jumpError($type, $start, $target) !== null) {
                    continue;
                }

                Tile::updateOrCreate(
                    ['board_id' => $board->id, 'position' => $start],
                    ['type' => $type, 'target_position' => $target, 'task_id' => null, 'title_override' => null, 'min_quantity' => 1, 'required_count' => 1],
                );

                $taken[$start] = true;
                $taken[$target] = true;
                $placed[$type]++;
            }
        }

        return [$placed['SNAKE'], $placed['LADDER']];
    }

    /**
     * Tasks not on the board yet, in random order. Once the library runs
     * out, the least-used come round again rather than leaving tiles empty.
     *
     * @return array<int, string>
     */
    private function taskIdsFor(int $needed, Collection $tiles): array
    {
        if ($needed === 0) {
            return [];
        }

        $library = Task::query()->pluck('id');

        if ($library->isEmpty()) {
            return [];
        }

        $used = $tiles->pluck('task_id')->filter()->countBy();
        $picked = [];

        while (count($picked) < $needed) {
            $round = $library
                ->shuffle()
                ->sortBy(fn (string $id) => $used->get($id, 0))
                ->take($needed - count($picked));

            foreach ($round as $id) {
                $picked[] = $id;
                $used->put($id, $used->get($id, 0) + 1);
            }
        }

        return $picked;
    }

    private function isEmpty(?Tile $tile): bool
    {
        return $tile === null
            || ($tile->type === 'NORMAL' && $tile->task_id === null && blank($tile->title_override));
    }
}
