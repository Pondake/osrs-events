<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompletedTile extends Model
{
    use HasUuids;

    public $timestamps = false;

    /**
     * A claim under review, not a fact — same three states as
     * BingoCompletion, for the same reason. PENDING is the default on a
     * board that requires approval; APPROVED is what unlocks the next roll.
     * REJECTED is kept rather than deleted so a player can see why.
     */
    public const STATUSES = ['PENDING', 'APPROVED', 'REJECTED'];

    protected $fillable = [
        'player_board_id', 'tile_id', 'completed_at', 'completed_via',
        'status', 'proof_url', 'note', 'marked_by', 'reviewed_by', 'reviewed_at', 'review_note',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function isApproved(): bool
    {
        return $this->status === 'APPROVED';
    }

    public function playerBoard(): BelongsTo
    {
        return $this->belongsTo(PlayerBoard::class);
    }

    public function tile(): BelongsTo
    {
        return $this->belongsTo(Tile::class);
    }

    public function markedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
