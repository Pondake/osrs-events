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

    /** Square grids; the value is the side length. */
    public const SIZES = [3, 4, 5, 6, 7];

    protected $fillable = ['event_id', 'size', 'win_condition'];

    protected $casts = ['size' => 'integer'];

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
