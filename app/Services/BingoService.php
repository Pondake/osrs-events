<?php

namespace App\Services;

use App\Models\BingoCard;
use App\Models\BingoCompletion;
use App\Models\BingoSquare;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Bingo's rules: who has ticked what, and what counts as winning.
 *
 * The line detection is the only real logic in the event type, so it lives
 * here rather than in a controller or — worse — in the Vue page, where the
 * server would have no way to agree with what a player was shown.
 */
class BingoService
{
    /**
     * Which competitor a completion belongs to, for this event's mode.
     *
     * A TEAM event scores per team and a SOLO event per user, so the same
     * click writes a different column. Returns null when a TEAM event's
     * player is on no assigned team — they cannot tick anything, and saying
     * so is better than silently scoring it against nobody.
     *
     * @return array{team_id: string|null, user_id: string|null}|null
     */
    public function competitorFor(Event $event, User $user): ?array
    {
        if ($event->mode !== 'TEAM') {
            return ['team_id' => null, 'user_id' => $user->id];
        }

        $teamId = $event->eventTeams()
            ->whereHas('team.members', fn ($q) => $q->where('user_id', $user->id))
            ->value('team_id');

        return $teamId === null ? null : ['team_id' => $teamId, 'user_id' => null];
    }

    /**
     * Positions this competitor has completed.
     *
     * @return array<int, int>
     */
    public function completedPositions(BingoCard $card, array $competitor): array
    {
        return BingoCompletion::query()
            ->whereIn('bingo_square_id', $card->squares()->select('id'))
            ->where(fn ($q) => $q
                ->where('team_id', $competitor['team_id'])
                ->where('user_id', $competitor['user_id']))
            ->join('bingo_squares', 'bingo_squares.id', '=', 'bingo_completions.bingo_square_id')
            ->orderBy('bingo_squares.position')
            ->pluck('bingo_squares.position')
            ->map(fn ($p) => (int) $p)
            ->all();
    }

    /**
     * Every winning line on a card of this size: each row, each column, and
     * both diagonals, as position lists.
     *
     * Computed rather than stored — it depends only on the size, and a stored
     * copy is a thing that can disagree with the grid it describes.
     *
     * @return array<int, array<int, int>>
     */
    public function lines(int $size): array
    {
        $lines = [];

        for ($row = 0; $row < $size; $row++) {
            $lines[] = range($row * $size, $row * $size + $size - 1);
        }

        for ($col = 0; $col < $size; $col++) {
            $lines[] = array_map(fn ($row) => $row * $size + $col, range(0, $size - 1));
        }

        $lines[] = array_map(fn ($i) => $i * $size + $i, range(0, $size - 1));
        $lines[] = array_map(fn ($i) => $i * $size + ($size - 1 - $i), range(0, $size - 1));

        return $lines;
    }

    /**
     * The lines this competitor has completed, as position lists — so the
     * page can highlight them rather than just announce a win.
     *
     * @param  array<int, int>  $completed
     * @return array<int, array<int, int>>
     */
    public function completedLines(int $size, array $completed): array
    {
        $done = array_flip($completed);

        return array_values(array_filter(
            $this->lines($size),
            fn ($line) => ! array_diff_key(array_flip($line), $done),
        ));
    }

    /**
     * Has this competitor won, under the card's own condition?
     *
     * @param  array<int, int>  $completed
     */
    public function hasWon(BingoCard $card, array $completed): bool
    {
        if ($card->win_condition === 'FULL_HOUSE') {
            // Against the squares that actually exist, not size², so a card
            // that was never fully filled in cannot be unwinnable.
            $positions = $card->squares()->pluck('position')->all();

            return $positions !== [] && ! array_diff($positions, $completed);
        }

        return $this->completedLines($card->size, $completed) !== [];
    }

    /**
     * The standings for a bingo event: every competitor with a completion,
     * ranked by squares completed then by lines.
     *
     * Built from completions rather than from a roster, because a bingo
     * event's roster is whoever has actually done something — an entrant who
     * has ticked nothing has no position to hold.
     */
    public function standings(Event $event, BingoCard $card): Collection
    {
        $rows = BingoCompletion::query()
            ->join('bingo_squares', 'bingo_squares.id', '=', 'bingo_completions.bingo_square_id')
            ->where('bingo_squares.bingo_card_id', $card->id)
            ->with(['team:id,name,icon_url', 'user:id,discord_username,nickname,avatar_url'])
            ->get(['bingo_completions.*', 'bingo_squares.position as square_position']);

        return $rows
            ->groupBy(fn (BingoCompletion $c) => $c->team_id ?? $c->user_id)
            ->map(function (Collection $group) use ($card) {
                $first = $group->first();
                $positions = $group->pluck('square_position')->map(fn ($p) => (int) $p)->all();

                return [
                    'id' => $first->team_id ?? $first->user_id,
                    'name' => $first->team?->name ?? ($first->user?->nickname ?: $first->user?->discord_username),
                    'avatarUrl' => $first->team?->icon_url ?? $first->user?->avatar_url,
                    'squares' => count($positions),
                    'lines' => count($this->completedLines($card->size, $positions)),
                    'won' => $this->hasWon($card, $positions),
                ];
            })
            ->sortByDesc(fn ($row) => [$row['lines'], $row['squares']])
            ->values();
    }

    /**
     * Fill a card out to its full grid.
     *
     * Squares are created up front, unlike Snakes & Ladders tiles which are
     * created on first edit — a bingo card is a fixed grid that has to be
     * clickable from the moment it exists, and a missing row would render as
     * a hole in the card.
     */
    public function ensureSquares(BingoCard $card): void
    {
        $existing = $card->squares()->pluck('position')->all();

        $missing = array_diff(range(0, $card->squareCount() - 1), $existing);

        foreach ($missing as $position) {
            BingoSquare::create(['bingo_card_id' => $card->id, 'position' => $position]);
        }
    }
}
