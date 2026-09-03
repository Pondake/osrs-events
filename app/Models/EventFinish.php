<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One competitor's finish: the row that makes a podium possible.
 *
 * Written by EventFinishService and by nothing else — the two things that
 * make a finish true (an approved last tile, a won bingo card) are decided
 * there so that both event types answer the question the same way.
 *
 * @see \App\Services\EventFinishService
 */
class EventFinish extends Model
{
    use HasUuids;

    protected $fillable = ['event_id', 'user_id', 'team_id', 'display_name', 'finished_at'];

    // `announced_at` is deliberately absent above: telling everybody is an
    // action the service takes when a place is settled, not a field that can
    // ride along with the row that created it.

    protected $casts = [
        // Cast explicitly. This one is read back as an ordering key and
        // printed with ->toIso8601String(), both of which fail quietly or
        // loudly on a raw string — the same missed cast on
        // PlayerBoard.last_roll_date was a real 500.
        'finished_at' => 'datetime',
        'announced_at' => 'datetime',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * What to print next to the rank.
     *
     * Falls back to the name stored at the time, which is the whole point of
     * keeping it: a podium outlives the account that earned it, and a rank
     * with nothing next to it is worse than a rank next to a name that has
     * since been deleted.
     */
    public function label(): string
    {
        return $this->team?->name
            ?? $this->user?->displayName()
            ?? $this->display_name
            ?? trans('common.unknown');
    }
}
