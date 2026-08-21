<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A square ticked off by one competitor.
 *
 * Exactly one of team_id / user_id is set: a TEAM event scores per team, a
 * SOLO event per user. `marked_by` is who clicked, which on a team card is
 * not the same question as who it counts for.
 */
class BingoCompletion extends Model
{
    use HasUuids;

    protected $fillable = ['bingo_square_id', 'team_id', 'user_id', 'marked_by'];

    public function square(): BelongsTo
    {
        return $this->belongsTo(BingoSquare::class, 'bingo_square_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function markedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by');
    }
}
