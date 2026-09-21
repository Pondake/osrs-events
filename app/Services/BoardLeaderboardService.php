<?php

namespace App\Services;

use App\Models\Event;
use App\Models\PlayerBoard;

/**
 * The Snakes & Ladders ranking: who is furthest along the track, and whether
 * a snake or a ladder is coming. Ported from PlayersService::getLeaderboard().
 */
class BoardLeaderboardService
{
    public function __construct(private EventFinishService $finishes) {}

    /**
     * Null for an event with no board — it has no such ranking, and its
     * standings live on the event page.
     *
     * `$named` is the caller's answer to whether this reader may see who is
     * playing. Progress is public on any listed event; identity is not.
     *
     * @return array{totalTiles: int, entries: array<int, array<string, mixed>>}|null
     */
    public function for(Event $event, bool $named): ?array
    {
        if ($event->board === null) {
            return null;
        }

        $tiles = $event->board->tiles()->orderBy('position')->get();

        // From the board size, not the tile rows: only configured tiles have a row.
        $maxPosition = max($event->board->tileCount() - 1, 0);

        // `provisional` rides along because a place can still move: while a
        // claim submitted earlier than this one is unreviewed, approving it
        // pushes everyone below it down. Asked once rather than per row —
        // see EventFinishService::contenderCutoff().
        $contenderAt = $this->finishes->contenderCutoff($event);

        // Keyed by whichever id the event's mode makes the competitor.
        $finishByCompetitor = $event->finishes()->get()
            ->mapWithKeys(fn ($finish, $index) => [
                ($finish->team_id ?? $finish->user_id) => [
                    'place' => $index + 1,
                    'at' => $finish->finished_at,
                    'provisional' => $contenderAt !== null
                        && $finish->finished_at !== null
                        && $contenderAt->lt($finish->finished_at),
                ],
            ]);

        // Finishers first, in the order they got home; everyone else behind
        // them by how far along they are. Position alone put everybody parked
        // on the final tile in a heap at the top, finished or not.
        $ordered = $event->playerBoards()
            ->with(['user', 'team'])
            // Qualified — playerBoards() joins boards.
            ->orderByDesc('player_boards.current_position')
            ->get()
            ->sortBy(function (PlayerBoard $pb) use ($event, $finishByCompetitor) {
                $finish = $finishByCompetitor[$this->competitorKey($event, $pb)] ?? null;

                return $finish !== null
                    ? $finish['place']
                    : PHP_INT_MAX - $pb->current_position;
            })
            ->values();

        $entries = $ordered->map(function (PlayerBoard $pb, int $index) use ($event, $tiles, $maxPosition, $finishByCompetitor, $named) {
            $pathTiles = $tiles->filter(fn ($t) => $t->position > $pb->current_position && $t->position <= $maxPosition);
            $finish = $finishByCompetitor[$this->competitorKey($event, $pb)] ?? null;

            return [
                'rank' => $index + 1,
                'playerId' => $pb->id,
                // Named fields, not the models: the whole User row carries
                // the email address.
                'user' => ! $named || $pb->user === null ? null : [
                    'nickname' => $pb->user->nickname,
                    'discord_username' => $pb->user->discord_username,
                    'avatar_url' => $pb->user->avatar_url,
                ],
                'team' => ! $named || $pb->team === null ? null : [
                    'name' => $pb->team->name,
                    'icon_url' => $pb->team->icon_url,
                    'guild_icon_url' => $pb->team->guild_icon_url,
                ],
                'currentPosition' => $pb->current_position,
                'tilesRemaining' => $maxPosition - $pb->current_position,
                // Separate from `rank`: rank is where the row sits in this
                // list, place is a result.
                'finishPlace' => $finish['place'] ?? null,
                // Sent apart from the place so the page can say "home"
                // without saying "first" while the queue is not clear.
                'finishProvisional' => (bool) ($finish['provisional'] ?? false),
                'finishedAt' => $finish === null ? null : $finish['at']?->toIso8601String(),
                'pathHasLadder' => $pathTiles->contains(fn ($t) => $t->type === 'LADDER' && $t->target_position !== null),
                'pathHasSnake' => $pathTiles->contains(fn ($t) => $t->type === 'SNAKE' && $t->target_position !== null),
            ];
        });

        return [
            'totalTiles' => $tiles->count(),
            'entries' => $entries->all(),
        ];
    }

    /** The team on a TEAM event, the player on a SOLO one. */
    private function competitorKey(Event $event, PlayerBoard $playerBoard): ?string
    {
        return $event->mode === 'TEAM' ? $playerBoard->team_id : $playerBoard->user_id;
    }
}
