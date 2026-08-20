<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BoardInvite extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = ['event_id', 'token', 'short_code', 'label', 'created_by', 'expires_at', 'max_uses', 'use_count'];

    /**
     * created_at is cast even though $timestamps is false — the column has a
     * useCurrent() default so the database fills it, and without the cast it
     * comes back as a raw string that blows up on any date method (the same
     * class of bug as PlayerBoard.last_roll_date; see CLAUDE.md).
     */
    protected $casts = [
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /** Declared because the admin invites overview eager-loads it. */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function accesses(): HasMany
    {
        return $this->hasMany(BoardAccess::class, 'invite_id');
    }
}
