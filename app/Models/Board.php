<?php

namespace App\Models;

use App\Models\Concerns\ReviewsClaims;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Board extends Model
{
    use HasUuids, ReviewsClaims;

    protected $fillable = ['event_id', 'size', 'dice_roll_limit', 'requires_approval', 'trust_runelite_completions'];

    protected $casts = [
        'requires_approval' => 'boolean',
        'trust_runelite_completions' => 'boolean',
    ];

    /**
     * How many tiles each size has.
     *
     * Was inlined in three places (two controllers and the tile editor's own
     * validation, which did not have it at all) — and a board's tile count
     * is not something three copies should be free to disagree about. The
     * frontend's own copy lives in Support/board.js; these two are the pair
     * that has to match.
     */
    public const TILE_COUNTS = [
        'SIZE_5X5' => 25,
        'SIZE_7X7' => 49,
        'SIZE_9X9' => 81,
    ];

    /** Tiles on this board. Falls back to 7x7, the default size. */
    public function tileCount(): int
    {
        return self::TILE_COUNTS[$this->size] ?? 49;
    }

    /**
     * Why a snake or ladder at this spot would not work, or null if it does.
     *
     * The first tile is where everybody starts, so nobody lands on it; a jump
     * on the last tile would turn the finish into a move. A target is
     * required because a jump without one does nothing, and the direction is
     * the whole difference between the two.
     */
    public function jumpError(string $type, int $position, ?int $target): ?string
    {
        if ($type === 'NORMAL') {
            return null;
        }

        $last = $this->tileCount() - 1;

        return match (true) {
            $position === 0 => trans('validation.jump_on_first_tile'),
            $position === $last => trans('validation.jump_on_last_tile'),
            $target === null => trans('validation.jump_target_required'),
            $type === 'SNAKE' && $target >= $position => trans('validation.snake_must_go_down'),
            $type === 'LADDER' && $target <= $position => trans('validation.ladder_must_go_up'),
            default => null,
        };
    }

    /**
     * The Snakes & Ladders payload of an Event: the grid and the progress on
     * it. Everything about what the competition IS — title, dates, who may
     * join, which teams play — moved to Event when the second event type
     * appeared on the roadmap. See the split_events_from_boards migration.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function tiles(): HasMany
    {
        return $this->hasMany(Tile::class)->orderBy('position');
    }

    public function playerBoards(): HasMany
    {
        return $this->hasMany(PlayerBoard::class);
    }
}
