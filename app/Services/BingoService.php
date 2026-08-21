<?php

namespace App\Services;

use App\Models\BingoCard;
use App\Models\BingoCompletion;
use App\Models\BingoSquare;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Bingo's rules: who has claimed what, what has been approved, and what
 * counts as winning.
 *
 * All of it server-side, because the server has to be able to agree with what
 * a player was shown — a line drawn only in the browser is a line the
 * leaderboard cannot verify.
 *
 * **Only APPROVED completions score.** A pending claim is visible to the
 * person who made it so they can see it is in the queue, and invisible to the
 * standings until a host has looked at it. That distinction is the difference
 * between a bingo tracker and a shared checklist — see
 * docs/bingo-research.md.
 */
class BingoService
{
    /**
     * Which competitor a completion belongs to, for this event's mode.
     *
     * A TEAM event scores per team and a SOLO event per user, so the same
     * click writes a different column. Returns null when a TEAM event's
     * player is on no assigned team — they cannot claim anything, and saying
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
     * This competitor's claims on this card, keyed by square position.
     *
     * Returns every status, not just approved: a player needs to see their
     * own pending claim sitting in the queue, or they will submit it again.
     *
     * @return Collection<int, BingoCompletion>
     */
    public function claimsFor(BingoCard $card, array $competitor): Collection
    {
        return BingoCompletion::query()
            ->join('bingo_squares', 'bingo_squares.id', '=', 'bingo_completions.bingo_square_id')
            ->where('bingo_squares.bingo_card_id', $card->id)
            ->where('bingo_completions.team_id', $competitor['team_id'])
            ->where('bingo_completions.user_id', $competitor['user_id'])
            ->get(['bingo_completions.*', 'bingo_squares.position as square_position'])
            ->keyBy(fn (BingoCompletion $c) => (int) $c->square_position);
    }

    /**
     * Positions this competitor has had **approved** — the only ones that
     * count toward a line or a score.
     *
     * @return array<int, int>
     */
    public function approvedPositions(BingoCard $card, array $competitor): array
    {
        return $this->claimsFor($card, $competitor)
            ->filter(fn (BingoCompletion $c) => $c->isApproved())
            ->keys()
            ->map(fn ($p) => (int) $p)
            ->sort()
            ->values()
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
     * Points for a set of approved positions: each square's own weight, plus
     * the card's line bonus for every completed row, column or diagonal.
     *
     * This is how clan events are actually scored — counting squares treats a
     * Zulrah pet and a bucket of sand as equal.
     *
     * @param  array<int, int>  $completed
     * @param  array<int, int>  $pointsByPosition
     */
    public function score(BingoCard $card, array $completed, array $pointsByPosition): int
    {
        $tilePoints = array_sum(array_map(
            fn (int $position) => $pointsByPosition[$position] ?? 1,
            $completed,
        ));

        return $tilePoints + count($this->completedLines($card->size, $completed)) * $card->line_bonus;
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
     * The standings for a bingo event, ranked by points then lines.
     *
     * Built from **approved** completions only. A competitor whose claims are
     * all pending has nothing on the board yet, which is the honest position
     * — showing them ahead of someone whose work was checked would make
     * review meaningless.
     */
    public function standings(Event $event, BingoCard $card): Collection
    {
        $points = $card->squares()->pluck('points', 'position')
            ->map(fn ($p) => (int) $p)
            ->all();

        $rows = BingoCompletion::query()
            ->join('bingo_squares', 'bingo_squares.id', '=', 'bingo_completions.bingo_square_id')
            ->where('bingo_squares.bingo_card_id', $card->id)
            ->where('bingo_completions.status', 'APPROVED')
            ->with(['team:id,name,icon_url', 'user:id,discord_username,nickname,avatar_url'])
            ->get(['bingo_completions.*', 'bingo_squares.position as square_position']);

        return $rows
            ->groupBy(fn (BingoCompletion $c) => $c->team_id ?? $c->user_id)
            ->map(function (Collection $group) use ($card, $points) {
                $first = $group->first();
                $positions = $group->pluck('square_position')->map(fn ($p) => (int) $p)->all();

                return [
                    'id' => $first->team_id ?? $first->user_id,
                    'name' => $first->team?->name ?? ($first->user?->nickname ?: $first->user?->discord_username),
                    'avatarUrl' => $first->team?->icon_url ?? $first->user?->avatar_url,
                    'squares' => count($positions),
                    'points' => $this->score($card, $positions, $points),
                    'lines' => count($this->completedLines($card->size, $positions)),
                    'won' => $this->hasWon($card, $positions),
                ];
            })
            ->sortByDesc(fn ($row) => [$row['points'], $row['lines'], $row['squares']])
            ->values();
    }

    /**
     * Claims waiting on a host, oldest first.
     *
     * Oldest first because a review queue is a queue: someone who submitted
     * an hour ago should not sit behind a claim made a minute ago.
     */
    public function pendingQueue(BingoCard $card): Collection
    {
        return BingoCompletion::query()
            ->join('bingo_squares', 'bingo_squares.id', '=', 'bingo_completions.bingo_square_id')
            ->where('bingo_squares.bingo_card_id', $card->id)
            ->where('bingo_completions.status', 'PENDING')
            ->with([
                'team:id,name',
                'user:id,discord_username,nickname',
                'markedBy:id,discord_username,nickname',
                'square:id,position,title_override,task_id',
                'square.task:id,title',
            ])
            ->orderBy('bingo_completions.created_at')
            ->get(['bingo_completions.*', 'bingo_squares.position as square_position'])
            ->map(fn (BingoCompletion $c) => [
                'id' => $c->id,
                'position' => (int) $c->square_position,
                'label' => $c->square?->label(),
                'competitor' => $c->team?->name ?? ($c->user?->nickname ?: $c->user?->discord_username),
                'submittedBy' => $c->markedBy?->nickname ?: $c->markedBy?->discord_username,
                'proofUrl' => $c->proof_url,
                'note' => $c->note,
                'submittedAt' => $c->created_at?->toIso8601String(),
            ]);
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
