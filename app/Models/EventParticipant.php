<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Somebody has joined an event.
 *
 * The one record that means the same thing for every event type. Progress
 * still lives where it always did — a standing, a player board, a claim — but
 * "am I in this" used to be inferred from whichever of those tables the type
 * happened to write, which gave bingo no answer at all until a square was
 * claimed and made a passing look at a Snakes & Ladders board count as
 * playing it.
 */
class EventParticipant extends Model
{
    use HasUuids;

    protected $fillable = ['event_id', 'user_id'];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
