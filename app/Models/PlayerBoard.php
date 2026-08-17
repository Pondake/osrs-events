<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlayerBoard extends Model
{
    use HasUuids;

    protected $fillable = ['user_id', 'board_id', 'team_id', 'current_position', 'dice_rolls_today', 'last_roll_date'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }

    public function completedTiles(): HasMany
    {
        return $this->hasMany(CompletedTile::class);
    }
}
