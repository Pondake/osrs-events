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
