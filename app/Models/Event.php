<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A competition: what it is, when it runs, and who may take part.
 *
 * How it is *played* lives on a type-specific payload — for Snakes & Ladders
 * that is the Board (grid size, dice limit, tiles, player progress). Bingo
 * and the phase 7 types will bring their own, which is exactly why this split
 * exists; see the split_events_from_boards migration.
 *
 * Ownership, entry and team assignment hang off the event rather than the
 * board, so an event type with no board can still be owned, joined and
 * invited to.
 */
class Event extends Model
{
    use HasUuids;

    protected $fillable = [
        'title',
        'type',
        'metric',
        'description',
        'mode',
        'access_mode',
        'required_guild_id',
        'is_listed',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'is_listed' => 'boolean',
        // Cast explicitly — a missed datetime cast on PlayerBoard.last_roll_date
        // was a real 500 (see CLAUDE.md).
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    /**
     * The kinds of event this app runs, per docs/ROADMAP.md phases 5 and 7.
     *
     * `available` gates what can be created. A type listed but unavailable
     * shows in the UI as coming soon rather than being absent — hiding
     * planned types just means nobody knows they're coming.
     */
    public const EVENT_TYPES = [
        'SNAKES_LADDERS' => ['icon' => 'i-lucide-dice-6', 'available' => true, 'needsMetric' => false],
        'SKILL_RACE' => ['icon' => 'i-lucide-trophy', 'available' => true, 'needsMetric' => true],
        'BINGO' => ['icon' => 'i-lucide-grid-3x3', 'available' => false, 'needsMetric' => false],
        'DROP_RACE' => ['icon' => 'i-lucide-swords', 'available' => false, 'needsMetric' => false],
    ];

    /**
     * The metric a skill event can race on.
     *
     * These are **Wise Old Man's own metric names**, not ours. Their API is
     * the intended source of the XP gains this ranks on, and their
     * competition model is what this whole event type is modelled after —
     * see the credit in the README. Keeping their vocabulary means a metric
     * goes straight into an API call with no translation table to drift.
     *
     * Skills only for now. Wise Old Man also supports boss killcounts and
     * activity metrics, which is where DROP_RACE would eventually point.
     */
    public const SKILL_METRICS = [
        'overall', 'attack', 'defence', 'strength', 'hitpoints', 'ranged',
        'prayer', 'magic', 'cooking', 'woodcutting', 'fletching', 'fishing',
        'firemaking', 'crafting', 'smithing', 'mining', 'herblore', 'agility',
        'thieving', 'slayer', 'farming', 'runecrafting', 'hunter',
        'construction',
    ];

    /** Whether this event's type races on a metric at all. */
    public function needsMetric(): bool
    {
        return self::EVENT_TYPES[$this->type]['needsMetric'] ?? false;
    }

    /** @return array<int, string> */
    public static function availableTypes(): array
    {
        return array_keys(array_filter(self::EVENT_TYPES, fn ($meta) => $meta['available']));
    }

    /**
     * hasOne, not hasMany: a Snakes & Ladders event has exactly one board.
     * Null for any event type that doesn't use one.
     */
    public function board(): HasOne
    {
        return $this->hasOne(Board::class);
    }

    public function authors(): HasMany
    {
        return $this->hasMany(BoardAuthor::class);
    }

    public function eventTeams(): HasMany
    {
        return $this->hasMany(BoardTeam::class);
    }

    public function invites(): HasMany
    {
        return $this->hasMany(BoardInvite::class);
    }

    public function accesses(): HasMany
    {
        return $this->hasMany(BoardAccess::class);
    }

    /**
     * Leaderboard rows for metric events. Empty for types that don't race on
     * one — a Snakes & Ladders event never gets a standing.
     */
    public function standings(): HasMany
    {
        return $this->hasMany(EventStanding::class);
    }

    /**
     * Progress rows live on the board, but callers want them by event.
     * hasManyThrough rather than hopping via ->board->playerBoards, so an
     * event with no board yields an empty set instead of a null dereference.
     */
    public function playerBoards(): HasManyThrough
    {
        return $this->hasManyThrough(PlayerBoard::class, Board::class);
    }
}
