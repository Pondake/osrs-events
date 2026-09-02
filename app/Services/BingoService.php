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
     * Positions this competitor has had **approved**, plus every wildcard on
     * the card — the only ones that count toward a line or a score.
     *
     * Wildcards are merged in here rather than written as completion rows
     * because a completion belongs to one competitor and a free square
     * belongs to all of them at once. That also means turning a square into
     * a wildcard, or back, takes effect immediately for everybody with no
     * data to migrate either way.
     *
     * @return array<int, int>
     */
    public function approvedPositions(BingoCard $card, array $competitor): array
    {
        $approved = $this->claimsFor($card, $competitor)
            ->filter(fn (BingoCompletion $c) => $c->isApproved())
            ->keys()
            ->map(fn ($p) => (int) $p);

        return $approved
            ->merge($this->wildcardPositions($card))
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * The free squares on a card.
     *
     * @return array<int, int>
     */
    public function wildcardPositions(BingoCard $card): array
    {
        return $card->squares()
            ->where('is_wildcard', true)
            ->pluck('position')
            ->map(fn ($p) => (int) $p)
            ->all();
    }

    /**
     * Every winning line on a card of this size, as position lists.
     *
     * Computed rather than stored — it depends only on the size and the
     * card's chosen kinds, and a stored copy is a thing that can disagree
     * with the grid it describes.
     *
     * `$kinds` is which shapes count. A card that says rows-only has no
     * column or diagonal lines at all, so nothing downstream — the win
     * check, the line bonus, the hover hint — has to know about the setting
     * separately.
     *
     * @param  array<int, string>|null  $kinds
     * @return array<int, array<int, int>>
     */
    public function lines(int $size, ?array $kinds = null): array
    {
        $kinds ??= BingoCard::LINE_KINDS;
        $lines = [];

        if (in_array('ROW', $kinds, true)) {
            for ($row = 0; $row < $size; $row++) {
                $lines[] = range($row * $size, $row * $size + $size - 1);
            }
        }

        if (in_array('COLUMN', $kinds, true)) {
            for ($col = 0; $col < $size; $col++) {
                $lines[] = array_map(fn ($row) => $row * $size + $col, range(0, $size - 1));
            }
        }

        if (in_array('DIAGONAL', $kinds, true)) {
            $lines[] = array_map(fn ($i) => $i * $size + $i, range(0, $size - 1));
            $lines[] = array_map(fn ($i) => $i * $size + ($size - 1 - $i), range(0, $size - 1));
        }

        return $lines;
    }

    /**
     * The lines this competitor has completed, as position lists — so the
     * page can highlight them rather than just announce a win.
     *
     * @param  array<int, int>  $completed
     * @param  array<int, string>|null  $kinds
     * @return array<int, array<int, int>>
     */
    public function completedLines(int $size, array $completed, ?array $kinds = null): array
    {
        $done = array_flip($completed);

        return array_values(array_filter(
            $this->lines($size, $kinds),
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

        return $tilePoints + count($this->completedLines($card->size, $completed, $card->winLines())) * $card->line_bonus;
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

        return $this->completedLines($card->size, $completed, $card->winLines()) !== [];
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
            ->with(['team:id,name,icon_url,guild_id,guild_icon', 'user:id,discord_username,nickname,avatar_url'])
            ->get(['bingo_completions.*', 'bingo_squares.position as square_position']);

        $wildcards = $this->wildcardPositions($card);

        return $rows
            ->groupBy(fn (BingoCompletion $c) => $c->team_id ?? $c->user_id)
            ->map(function (Collection $group) use ($card, $points, $wildcards) {
                $first = $group->first();
                // Free squares count for everyone, so they belong in every
                // competitor's set — otherwise the card shows a line the
                // standings do not credit.
                $positions = $group->pluck('square_position')
                    ->map(fn ($p) => (int) $p)
                    ->merge($wildcards)
                    ->unique()
                    ->values()
                    ->all();

                return [
                    'id' => $first->team_id ?? $first->user_id,
                    'name' => $first->team?->name ?? ($first->user?->nickname ?: $first->user?->discord_username) ?: trans('common.deleted_user'),
                    'avatarUrl' => $first->team?->icon_url ?? $first->team?->guild_icon_url ?? $first->user?->avatar_url,
                    'squares' => count($positions),
                    'points' => $this->score($card, $positions, $points),
                    'lines' => count($this->completedLines($card->size, $positions, $card->winLines())),
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
                'team:id,name,icon_url,guild_id,guild_icon',
                // osrs_username too: the review modal shows both names, so a
                // host judging a screenshot can match the RSN in it to the
                // account that submitted it. That is the whole check.
                'user:id,discord_username,nickname,avatar_url,osrs_username',
                'markedBy:id,discord_username,nickname,avatar_url,osrs_username',
                'square:id,position,title_override,task_id',
                'square.task:id,title,icon_url',
            ])
            ->orderBy('bingo_completions.created_at')
            ->get(['bingo_completions.*', 'bingo_squares.position as square_position'])
            ->map(fn (BingoCompletion $c) => [
                'id' => $c->id,
                'position' => (int) $c->square_position,
                'label' => $c->square?->label(),
                'iconUrl' => $c->square?->task?->icon_url,
                'competitor' => $c->team?->name ?? ($c->user?->nickname ?: $c->user?->discord_username) ?: trans('common.deleted_user'),
                'competitorAvatar' => $c->team?->icon_url ?? $c->team?->guild_icon_url ?? $c->user?->avatar_url,
                // Both identities, because they are how a host checks a
                // claim: the Discord name is who is asking, the OSRS name is
                // what the screenshot will show. Either can be missing — an
                // email account has no Discord name — so the modal falls back
                // rather than rendering a gap.
                'submittedBy' => $c->markedBy?->nickname ?: $c->markedBy?->discord_username,
                'submittedByAvatar' => $c->markedBy?->avatar_url,
                'submittedByOsrs' => $c->markedBy?->osrs_username,
                'proofUrl' => $c->proof_url,
                'note' => $c->note,
                'submittedAt' => $c->created_at?->toIso8601String(),
            ]);
    }

    /**
     * Who has had each square approved, keyed by position.
     *
     * Turns a card from a grid of ticks into a record of who did what: a
     * square somebody on your team already got looks different from one
     * nobody has, and on a solo event you can see who beat you to it.
     *
     * Capped at three faces per square with a count for the rest — a 10x10
     * card in a clan of forty would otherwise ship four hundred avatar rows
     * to render as 16px circles, and the fourth face tells you nothing the
     * "+37" does not.
     *
     * @return array<int, array{holders: array<int, array{name: ?string, avatarUrl: ?string}>, total: int}>
     */
    public function approvedBy(BingoCard $card): array
    {
        return BingoCompletion::query()
            ->join('bingo_squares', 'bingo_squares.id', '=', 'bingo_completions.bingo_square_id')
            ->where('bingo_squares.bingo_card_id', $card->id)
            ->where('bingo_completions.status', 'APPROVED')
            ->with(['team:id,name,icon_url,guild_id,guild_icon', 'user:id,discord_username,nickname,avatar_url'])
            ->orderBy('bingo_completions.created_at')
            ->get(['bingo_completions.*', 'bingo_squares.position as square_position'])
            ->groupBy(fn (BingoCompletion $c) => (int) $c->square_position)
            ->map(fn (Collection $group) => [
                'holders' => $group->take(3)->map(fn (BingoCompletion $c) => [
                    // A team event credits the team, a solo one the player —
                    // the same competitor split competitorFor() makes.
                    'name' => $c->team?->name ?? ($c->user?->nickname ?: $c->user?->discord_username) ?: trans('common.deleted_user'),
                    'avatarUrl' => $c->team?->icon_url ?? $c->team?->guild_icon_url ?? $c->user?->avatar_url,
                ])->values()->all(),
                'total' => $group->count(),
            ])
            ->all();
    }

    /**
     * Apply a card's settings, growing or shrinking the grid to match.
     *
     * Lives here rather than in BingoController because two places set these
     * now: the card's own endpoint, and the event settings modal in edit
     * mode — a bingo event's win condition is one of the "relevant options"
     * that modal is expected to hold, and having it write through a second
     * copy of this logic is how the shrink guard below gets forgotten in one
     * of them.
     *
     * Returns false, rather than throwing, when a shrink would drop squares
     * that carry completions: the caller decides how to say so, and both
     * callers say it differently (a flash vs. a validation error).
     */
    public function applyCardSettings(BingoCard $card, array $data): bool
    {
        if (isset($data['size']) && $data['size'] < $card->size) {
            $hasProgress = BingoCompletion::whereIn(
                'bingo_square_id',
                $card->squares()->where('position', '>=', $data['size'] ** 2)->select('id'),
            )->exists();

            // Growing a card adds squares; shrinking one refuses rather than
            // dropping squares that carry other people's completions.
            // Deleting somebody's progress is not something a size dropdown
            // should be able to do silently.
            if ($hasProgress) {
                return false;
            }

            $card->squares()->where('position', '>=', $data['size'] ** 2)->delete();
        }

        $card->update($data);
        $this->ensureSquares($card->fresh());

        return true;
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
