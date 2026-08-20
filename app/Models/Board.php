<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Board extends Model
{
    use HasUuids;

    protected $fillable = ['event_id', 'size', 'dice_roll_limit'];

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
