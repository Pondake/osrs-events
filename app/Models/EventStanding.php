<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A participant's standing in a metric event — the stored result of a
 * hiscores lookup, in Wise Old Man's start/end/gained shape.
 *
 * @see \App\Services\WiseOldManService for where the numbers come from.
 */
class EventStanding extends Model
{
    use HasUuids;

    protected $fillable = [
        'event_id',
        'user_id',
        'username',
        'start_value',
        'end_value',
        'gained',
        'synced_at',
        'sync_error',
    ];

    protected $casts = [
        // Cast explicitly, including the obviously-date-shaped one: a raw
        // string here would break any ->diffForHumans() on it downstream,
        // which is exactly how PlayerBoard.last_roll_date caused a 500.
        'synced_at' => 'datetime',
        'start_value' => 'integer',
        'end_value' => 'integer',
        'gained' => 'integer',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
