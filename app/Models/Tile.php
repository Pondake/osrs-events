<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tile extends Model
{
    use HasUuids;

    protected $fillable = ['board_id', 'position', 'task_id', 'title_override', 'min_quantity', 'type', 'target_position'];

    protected $casts = ['position' => 'integer', 'min_quantity' => 'integer', 'target_position' => 'integer'];

    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function completedTiles(): HasMany
    {
        return $this->hasMany(CompletedTile::class);
    }
}
