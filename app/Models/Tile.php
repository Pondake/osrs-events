<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tile extends Model
{
    use HasUuids;

    protected $fillable = ['board_id', 'position', 'title_override', 'type', 'target_position'];

    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }

    public function completedTiles(): HasMany
    {
        return $this->hasMany(CompletedTile::class);
    }
}
