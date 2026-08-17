<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompletedTile extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = ['player_board_id', 'tile_id', 'completed_at', 'completed_via'];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    public function playerBoard(): BelongsTo
    {
        return $this->belongsTo(PlayerBoard::class);
    }

    public function tile(): BelongsTo
    {
        return $this->belongsTo(Tile::class);
    }
}
