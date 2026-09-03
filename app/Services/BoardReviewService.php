<?php

namespace App\Services;

use App\Models\Board;
use App\Models\CompletedTile;
use Illuminate\Support\Collection;

/**
 * The review half of Snakes & Ladders — a tile claim's queue, mirroring
 * BingoService's pendingQueue() for the same reason bingo has one: a claim
 * under review needs somewhere for a host to actually see the proof, not
 * just a boolean the player set themselves.
 */
class BoardReviewService
{
    /**
     * Claims waiting on a host, oldest first — see BingoService::pendingQueue
     * for why the ordering matters.
     *
     * @return Collection<int, array>
     */
    public function pendingQueue(Board $board): Collection
    {
        // Which tile ends the run — the board's SIZE, not how many tiles a
        // host filled in, same rule EventFinishService uses.
        $lastPosition = max($board->tileCount() - 1, 0);

        // Every pending claim on that tile, oldest submission first. A host
        // ruling on a race for the finish has to be able to see that it IS a
        // race, and who got in first: the podium is ordered by submission, so
        // approving the second one first does not hand it the win — but a
        // host who cannot see that will believe it does, and will agonise
        // over an order that does not matter.
        $contenders = CompletedTile::query()
            ->join('tiles', 'tiles.id', '=', 'completed_tiles.tile_id')
            ->where('tiles.board_id', $board->id)
            ->where('tiles.position', $lastPosition)
            ->where('completed_tiles.status', 'PENDING')
            ->orderBy('completed_tiles.completed_at')
            ->pluck('completed_tiles.id')
            ->values();

        return CompletedTile::query()
            ->join('tiles', 'tiles.id', '=', 'completed_tiles.tile_id')
            ->join('player_boards', 'player_boards.id', '=', 'completed_tiles.player_board_id')
            ->where('tiles.board_id', $board->id)
            ->where('completed_tiles.status', 'PENDING')
            ->with([
                'playerBoard.user:id,discord_username,nickname,avatar_url',
                'playerBoard.team:id,name,icon_url,guild_id,guild_icon',
                // osrs_username too, same reason as bingo's queue: the RSN in
                // the screenshot is what a host actually matches against.
                'markedBy:id,discord_username,nickname,avatar_url,osrs_username',
                'tile.task:id,title,icon_url',
            ])
            ->orderBy('completed_tiles.completed_at')
            ->get(['completed_tiles.*', 'tiles.position as tile_position', 'tiles.title_override as tile_title_override'])
            ->map(fn (CompletedTile $c) => [
                'id' => $c->id,
                'position' => (int) $c->tile_position,
                'label' => $c->tile_title_override ?? $c->tile?->task?->title,
                'iconUrl' => $c->tile?->task?->icon_url,
                'competitor' => $c->playerBoard?->team?->name
                    ?? ($c->playerBoard?->user?->nickname ?: $c->playerBoard?->user?->discord_username)
                    ?: trans('common.deleted_user'),
                'competitorAvatar' => $c->playerBoard?->team?->icon_url ?? $c->playerBoard?->team?->guild_icon_url ?? $c->playerBoard?->user?->avatar_url,
                'submittedBy' => $c->markedBy?->nickname ?: $c->markedBy?->discord_username,
                'submittedByAvatar' => $c->markedBy?->avatar_url,
                'submittedByOsrs' => $c->markedBy?->osrs_username,
                'proofUrl' => $c->proof_url,
                'note' => $c->note,
                'submittedAt' => $c->completed_at?->toIso8601String(),
                // Approving this one ends somebody's run — worth saying
                // before the click rather than after it, especially on a
                // STOP event where it may also end everybody else's.
                'finishesBoard' => (int) $c->tile_position === $lastPosition,
                // Where this claim sits among the pending claims for that
                // tile, and how many there are. Both null unless there is
                // actually a contest, so the page has nothing to draw in the
                // ordinary case of one person getting home.
                'raceOrder' => $contenders->count() > 1 && ($index = $contenders->search($c->id)) !== false
                    ? $index + 1
                    : null,
                'raceTotal' => $contenders->count() > 1 ? $contenders->count() : null,
            ]);
    }

    /**
     * A cheap fingerprint of every claim's competitor, tile and verdict —
     * the fingerprint half of what BingoChannel calls claimsVersion(),
     * pulled out here so SnakesLaddersChannel's fingerprint() and payload()
     * (which needs the same value to tell a viewer "go re-fetch your own
     * claims") never drift apart the way the two bingo eager-loads once did.
     */
    public function claimsVersion(Board $board): string
    {
        $rows = CompletedTile::query()
            ->join('tiles', 'tiles.id', '=', 'completed_tiles.tile_id')
            ->where('tiles.board_id', $board->id)
            ->orderBy('tiles.position')
            ->orderBy('completed_tiles.player_board_id')
            ->get(['completed_tiles.player_board_id', 'completed_tiles.status', 'tiles.position']);

        return md5($rows->map(fn ($r) => "{$r->position}:{$r->player_board_id}:{$r->status}")->implode('|'));
    }
}
