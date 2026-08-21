<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A bingo card — the payload of a BINGO event, the way Board is the payload
 * of a Snakes & Ladders one.
 */
class BingoCard extends Model
{
    use HasUuids;

    /** How a card is won. */
    public const WIN_CONDITIONS = ['LINE', 'FULL_HOUSE'];

    /**
     * Which shapes count as a line — see the add_win_lines_to_bingo_cards
     * migration for why this is a list rather than three columns.
     */
    public const LINE_KINDS = ['ROW', 'COLUMN', 'DIAGONAL'];

    /**
     * Square grids; the value is the side length.
     *
     * Up to 10 because that is what competitive clan events actually run —
     * the guides put 5x5 at two to three days, 7x7 at a week and 10x10 at a
     * fortnight (docs/bingo-research.md).
     */
    public const SIZES = [3, 4, 5, 6, 7, 8, 9, 10];

    protected $fillable = ['event_id', 'size', 'win_condition', 'line_bonus', 'requires_approval', 'win_lines'];

    protected $casts = [
        'size' => 'integer',
        'line_bonus' => 'integer',
        'requires_approval' => 'boolean',
        'win_lines' => 'array',
    ];

    /**
     * The line kinds this card counts, defaulted rather than nullable at the
     * call site.
     *
     * Null means "never set" — a card created before the column existed — and
     * the honest reading of that is the behaviour it already had, which was
     * all three. An empty array is a different thing (somebody unticked
     * everything) and the form refuses it, so it is not represented here.
     *
     * @return array<int, string>
     */
    public function winLines(): array
    {
        $lines = $this->win_lines;

        return is_array($lines) && $lines !== [] ? $lines : self::LINE_KINDS;
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function squares(): HasMany
    {
        return $this->hasMany(BingoSquare::class);
    }

    /** Total squares on the card — the grid is always square. */
    public function squareCount(): int
    {
        return $this->size * $this->size;
    }
}
