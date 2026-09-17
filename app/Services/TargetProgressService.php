<?php

namespace App\Services;

use App\Models\BingoCard;
use App\Models\PlayerBoard;
use App\Models\PluginCompletion;
use App\Models\TargetProgress;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * "Do this N times": how far a competitor is toward a square or a tile.
 *
 * Counting is by **distinct kill count**, not by report. Three Zalcano kills
 * arrive as three reports carrying kill counts 204, 205 and 206, so the kill
 * count is what says they were three different kills — a report replayed or
 * duplicated by the plugin carries the same one and adds nothing. A report
 * with no kill count (a plain item drop has none) falls back to its own id,
 * which makes every such report its own event.
 *
 * Both spellings live in `dedupe_key` and the unique index enforces them, so
 * the counting rule is one column and one constraint rather than a query that
 * has to remember it.
 */
class TargetProgressService
{
    /** The competitor a bingo claim is made by — the split competitorFor() makes. */
    public static function bingoKey(array $competitor): string
    {
        return $competitor['team_id'] !== null
            ? "team:{$competitor['team_id']}"
            : "user:{$competitor['user_id']}";
    }

    /** A tile is claimed against a player board, so that is the competitor here. */
    public static function boardKey(PlayerBoard $playerBoard): string
    {
        return "board:{$playerBoard->id}";
    }

    /**
     * Count one report toward a target and answer how far the competitor now
     * is, or null when this report was already counted.
     *
     * Idempotent by construction: a second report of the same kill collides
     * with the unique index and is swallowed, so a retrying plugin cannot
     * walk a square to five on one kill. Null rather than the unchanged
     * total, so a caller can tell a kill that moved the number from a resend
     * that did not — the plugin announces the first and stays quiet on the
     * second.
     */
    public function record(string $kind, string $targetId, string $competitorKey, PluginCompletion $completion): ?int
    {
        $killCount = $completion->context['kill_count'] ?? null;

        try {
            TargetProgress::create([
                'kind' => $kind,
                'target_id' => $targetId,
                'competitor_key' => $competitorKey,
                'dedupe_key' => $killCount === null ? "report:{$completion->id}" : "kc:{$killCount}",
                'plugin_completion_id' => $completion->id,
            ]);
        } catch (UniqueConstraintViolationException) {
            // Already counted. Not an error: the plugin resends.
            return null;
        }

        return $this->count($kind, $targetId, $competitorKey);
    }

    public function count(string $kind, string $targetId, string $competitorKey): int
    {
        return TargetProgress::where('kind', $kind)
            ->where('target_id', $targetId)
            ->where('competitor_key', $competitorKey)
            ->count();
    }

    /**
     * This competitor's progress on a whole card, keyed by square position.
     *
     * Keyed by position rather than id to match the `claims` and `completed`
     * props the bingo page already indexes its grid by.
     *
     * @return array<int, int>
     */
    public function forCard(BingoCard $card, ?array $competitor): array
    {
        if ($competitor === null) {
            return [];
        }

        $positions = $card->squares()->where('required_count', '>', 1)->pluck('position', 'id');

        if ($positions->isEmpty()) {
            return [];
        }

        return TargetProgress::where('kind', 'bingo_square')
            ->whereIn('target_id', $positions->keys())
            ->where('competitor_key', self::bingoKey($competitor))
            ->selectRaw('target_id, count(*) as total')
            ->groupBy('target_id')
            ->pluck('total', 'target_id')
            ->mapWithKeys(fn ($total, $id) => [(int) $positions[$id] => (int) $total])
            ->all();
    }

    /**
     * This player board's progress, keyed by tile id — the same key the board
     * page's own `claims` map uses.
     *
     * @return array<string, int>
     */
    public function forPlayerBoard(?PlayerBoard $playerBoard): array
    {
        if ($playerBoard === null) {
            return [];
        }

        return TargetProgress::where('kind', 'tile')
            ->where('competitor_key', self::boardKey($playerBoard))
            ->selectRaw('target_id, count(*) as total')
            ->groupBy('target_id')
            ->pluck('total', 'target_id')
            ->map(fn ($total) => (int) $total)
            ->all();
    }
}
