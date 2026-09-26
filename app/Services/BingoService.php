<?php

namespace App\Services;

use App\Models\BingoCard;
use App\Models\BingoCompletion;
use App\Models\BingoSquare;
use App\Models\Event;
use App\Models\User;
use App\Support\RuneliteName;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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
            ->with(['pluginCompletion', 'reviewedBy:id,discord_username,nickname'])
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
        // A lockout card is won on the count when it closes, not by the
        // first line: every square one team takes is a square the others
        // can never have, so a line is just part of the score.
        if ($card->usesLockout()) {
            return false;
        }

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
            ->values()
            ->pipe(fn (Collection $rows) => $this->lockoutWinners($event, $card, $rows));
    }

    /**
     * On a lockout card the win is whoever leads when the card closes, so
     * the trophy goes on the top row then. Every team level with it on
     * points shares it, rather than a tie being broken by something nobody
     * was told counted.
     */
    private function lockoutWinners(Event $event, BingoCard $card, Collection $rows): Collection
    {
        if (! $card->usesLockout() || ! $event->isEnded() || $rows->isEmpty() || $rows->first()['points'] <= 0) {
            return $rows;
        }

        $best = $rows->first()['points'];

        return $rows->map(fn ($row) => [...$row, 'won' => $row['points'] === $best]);
    }

    /**
     * Claims waiting on a host, oldest first.
     *
     * Oldest first because a review queue is a queue: someone who submitted
     * an hour ago should not sit behind a claim made a minute ago.
     */
    public function pendingQueue(BingoCard $card): Collection
    {
        // Which of these waiting claims would actually win the card, and
        // which of those got in first. A host ruling on a race for the win
        // has to be able to see that it IS a race: places go by when a claim
        // was submitted, not by when it was signed off, so the order they
        // work through the queue cannot change who won — but a host who
        // cannot see that will believe it does.
        $winning = $this->winningClaims($card);

        // On a lockout card, where each claim stands in the line for its
        // square. A claim on a square another team already holds is left
        // out altogether: it is not a question for the host, it is a place
        // held in case that team's claim is ever overturned.
        $lockout = $card->usesLockout() ? $this->lockoutLines($card) : null;

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
                'square:id,position,title_override,task_id,min_quantity,required_count',
                'square.task:id,title,icon_url',
                'pluginCompletion',
            ])
            ->orderBy('bingo_completions.created_at')
            ->get(['bingo_completions.*', 'bingo_squares.position as square_position'])
            ->reject(fn (BingoCompletion $c) => $lockout !== null && $lockout['held']->has($c->bingo_square_id))
            ->values()
            ->map(fn (BingoCompletion $c) => [
                'id' => $c->id,
                'position' => (int) $c->square_position,
                'label' => $c->square?->label(),
                // The bar the square sets, so a manual claim is judged by
                // the same one the plugin is held to.
                'minQuantity' => $c->square?->min_quantity ?? 1,
                // And how many times it asked for, so a host judging a
                // manual claim knows what the square actually demands.
                'requiredCount' => $c->square?->required_count ?? 1,
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
                // The character the claim was made with, which is what the
                // screenshot shows — an alt is not the account's main.
                'submittedByOsrs' => $c->rsn ?? $c->markedBy?->osrs_username,
                'submittedByAlt' => filled($c->rsn) && ! RuneliteName::sameRsn($c->rsn, $c->markedBy?->osrs_username),
                'completedVia' => $c->completed_via,
                'proofUrl' => $c->proof_url,
                'note' => $c->note,
                // What the plugin actually saw, for a claim with no
                // screenshot — null on a manual claim, and on a RUNELITE
                // claim that predates this field.
                'runeliteContext' => $c->pluginCompletion?->reviewContext(),
                'submittedAt' => $c->created_at?->toIso8601String(),
                // Approving this one wins the card for its competitor — and
                // on a STOP event, ends the whole thing.
                'winsCard' => $winning->has($c->id),
                // Where it sits among the waiting claims that would win, and
                // how many there are. Both null unless there is a contest,
                // so an ordinary claim has nothing extra drawn on it.
                'raceOrder' => $winning->count() > 1 ? $winning->get($c->id) : null,
                'raceTotal' => $winning->count() > 1 ? $winning->count() : null,
                // Lockout: the team ahead of this claim for the square. Null
                // when it is first in line, the only claim a host can
                // approve. See lockoutRefusal().
                'lockoutAhead' => $lockout === null ? null : $this->aheadName($lockout['lines']->get($c->bingo_square_id), $c),
            ]);
    }

    /** The team first in line for a square, when that is not this claim. */
    private function aheadName(?Collection $line, BingoCompletion $claim): ?string
    {
        $first = $line?->first();

        return $first === null || $first->id === $claim->id
            ? null
            : ($first->team?->name ?? trans('common.deleted_user'));
    }

    /**
     * Every square on a lockout card that a team holds, and the line of
     * pending claims for each of the rest.
     *
     * @return array{held: Collection<string, BingoCompletion>, lines: Collection<string, Collection<int, BingoCompletion>>}
     */
    private function lockoutLines(BingoCard $card): array
    {
        $claims = BingoCompletion::query()
            ->whereIn('bingo_square_id', $card->squares()->select('id'))
            ->whereIn('status', ['APPROVED', 'PENDING'])
            ->with('team:id,name')
            ->orderBy('claimed_at')
            ->orderBy('created_at')
            ->get();

        return [
            'held' => $claims->where('status', 'APPROVED')->keyBy('bingo_square_id'),
            'lines' => $claims->where('status', 'PENDING')->groupBy('bingo_square_id')->map->values(),
        ];
    }

    /**
     * The claim that holds a square on a lockout card, or null while it is
     * still open.
     *
     * The oldest approved one, should there ever be two. A card cannot be
     * switched to lockout once it has claims, so that is a safety net rather
     * than a rule anyone plays by.
     */
    public function lockHolder(BingoSquare $square): ?BingoCompletion
    {
        return BingoCompletion::where('bingo_square_id', $square->id)
            ->where('status', 'APPROVED')
            ->with('team:id,name')
            ->orderBy('claimed_at')
            ->orderBy('created_at')
            ->first();
    }

    /**
     * Pending claims for a square, first in line first.
     *
     * The line goes by when a claim was made, not when a host gets to it:
     * two teams getting the same drop minutes apart is a race, and the team
     * that got there first should not lose it to the order of a queue.
     *
     * @return Collection<int, BingoCompletion>
     */
    public function lockoutLine(BingoSquare $square): Collection
    {
        return BingoCompletion::where('bingo_square_id', $square->id)
            ->where('status', 'PENDING')
            ->with('team:id,name')
            ->orderBy('claimed_at')
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Write a claim, obeying the card's lockout.
     *
     * Returns null when another team already holds the square. Otherwise the
     * claim is written as asked, except that one which would have been
     * approved on the spot waits instead when another team's claim for the
     * square was made before it. Reaching the queue first is not being first.
     *
     * The square row is locked throughout, so two claims landing in the same
     * instant are decided one after the other rather than both approved.
     */
    public function createClaim(BingoCard $card, BingoSquare $square, array $attributes): ?BingoCompletion
    {
        if (! $card->usesLockout()) {
            return BingoCompletion::create($attributes);
        }

        return DB::transaction(function () use ($square, $attributes) {
            BingoSquare::whereKey($square->id)->lockForUpdate()->first();

            if ($this->lockHolder($square) !== null) {
                return null;
            }

            $attributes['claimed_at'] ??= now();

            $ahead = $this->lockoutLine($square)
                ->contains(fn (BingoCompletion $c) => $c->team_id !== $attributes['team_id'] && $c->claimed_at->lte($attributes['claimed_at']));

            if ($attributes['status'] === 'APPROVED' && $ahead) {
                $attributes['status'] = 'PENDING';
            }

            return BingoCompletion::create($attributes);
        });
    }

    /**
     * Why a host may not approve this claim on a lockout card, or null.
     *
     * Another team already holds the square, or another team's claim for it
     * was made first and has not been ruled on.
     */
    public function lockoutRefusal(BingoCard $card, BingoCompletion $claim): ?string
    {
        if (! $card->usesLockout()) {
            return null;
        }

        $square = $claim->square;
        $holder = $this->lockHolder($square);

        if ($holder !== null && $holder->id !== $claim->id) {
            return trans('bingo.lockout_taken', ['team' => $holder->team?->name ?? trans('common.deleted_user')]);
        }

        $first = $this->lockoutLine($square)
            ->reject(fn (BingoCompletion $c) => $c->id === $claim->id)
            ->first(fn (BingoCompletion $c) => $c->claimed_at->lt($claim->claimed_at)
                || ($c->claimed_at->eq($claim->claimed_at) && $c->created_at->lt($claim->created_at)));

        return $first === null
            ? null
            : trans('bingo.lockout_not_first', ['team' => $first->team?->name ?? trans('common.deleted_user')]);
    }

    /**
     * Hand a square that has just come free to the next claim in line.
     *
     * Only as far as that claim would have gone on its own: one that would
     * have been approved on the spot, had nobody been ahead of it, is
     * approved now; one that needs a host stays in the queue, now first.
     * Returns the claim it approved, if any.
     */
    public function advanceLine(BingoCard $card, BingoSquare $square): ?BingoCompletion
    {
        if (! $card->usesLockout()) {
            return null;
        }

        return DB::transaction(function () use ($card, $square) {
            BingoSquare::whereKey($square->id)->lockForUpdate()->first();

            if ($this->lockHolder($square) !== null) {
                return null;
            }

            $next = $this->lockoutLine($square)->first();

            if ($next === null) {
                return null;
            }

            $next->load('markedBy', 'pluginCompletion');

            if ($card->initialClaimStatus($next->completed_via, $next->markedBy, filled($next->pluginCompletion?->doubts), $next->rsn) !== 'APPROVED') {
                return null;
            }

            $next->update(['status' => 'APPROVED']);

            return $next;
        });
    }

    /**
     * Why lockout cannot be switched to this value, or null.
     *
     * Only on a team event, and only before the first claim: switching it
     * mid-event would hand squares that two teams both have to one of them,
     * or open squares a team already took.
     */
    public function lockoutChangeRefusal(string $mode, BingoCard $card, ?bool $lockout): ?string
    {
        if ($lockout === null || $lockout === (bool) $card->lockout) {
            return null;
        }

        if ($lockout && $mode !== 'TEAM') {
            return trans('bingo.lockout_team_only');
        }

        $hasClaims = BingoCompletion::whereIn('bingo_square_id', $card->squares()->select('id'))->exists();

        return $hasClaims ? trans('bingo.lockout_locked') : null;
    }

    /**
     * Square ids another team holds, for a competitor on a lockout card.
     *
     * @return Collection<int, string>
     */
    public function lockedSquareIds(BingoCard $card, array $competitor): Collection
    {
        if (! $card->usesLockout()) {
            return collect();
        }

        return BingoCompletion::query()
            ->whereIn('bingo_square_id', $card->squares()->select('id'))
            ->where('status', 'APPROVED')
            ->where('team_id', '!=', $competitor['team_id'])
            ->pluck('bingo_square_id');
    }

    /**
     * The pending claims whose approval would complete the card, mapped to
     * their place in submission order.
     *
     * Grouped by competitor so this costs one claimsFor() per person with
     * something waiting, not one per claim. A competitor's whole pending set
     * is tried at once — two squares submitted together can complete a line
     * that neither would on its own, and a host looking at the first of them
     * should still be told what it is part of.
     *
     * @return Collection<string, int> claim id => 1-based place
     */
    private function winningClaims(BingoCard $card): Collection
    {
        $pending = BingoCompletion::query()
            ->join('bingo_squares', 'bingo_squares.id', '=', 'bingo_completions.bingo_square_id')
            ->where('bingo_squares.bingo_card_id', $card->id)
            ->where('bingo_completions.status', 'PENDING')
            ->orderBy('bingo_completions.created_at')
            ->get(['bingo_completions.*', 'bingo_squares.position as square_position']);

        $winners = collect();

        foreach ($pending->groupBy(fn (BingoCompletion $c) => $c->team_id ?? $c->user_id) as $claims) {
            $competitor = ['team_id' => $claims->first()->team_id, 'user_id' => $claims->first()->user_id];

            $approved = $this->approvedPositions($card, $competitor);

            // Already won without any of these: nothing waiting can be the
            // claim that wins it.
            if ($this->hasWon($card, $approved)) {
                continue;
            }

            $positions = $approved;

            // In submission order, so the FIRST claim that tips them over is
            // the one marked — the later ones are riding on a card that was
            // already complete.
            foreach ($claims as $claim) {
                $positions[] = (int) $claim->square_position;

                if ($this->hasWon($card, $positions)) {
                    $winners->push($claim);
                    break;
                }
            }
        }

        return $winners
            ->sortBy(fn (BingoCompletion $c) => $c->created_at)
            ->values()
            ->mapWithKeys(fn (BingoCompletion $c, int $index) => [$c->id => $index + 1]);
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
