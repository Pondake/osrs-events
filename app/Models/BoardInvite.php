<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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

    /**
     * Invites that could still let somebody in right now.
     *
     * "Open" is the only count worth capping: a link that expired last week
     * or has burned its last use is history, not clutter, and refusing a new
     * one because of it would be a limit the host cannot clear.
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query
            ->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->where(fn (Builder $q) => $q->whereNull('max_uses')->orWhereColumn('use_count', '<', 'max_uses'));
    }

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
