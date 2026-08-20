<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Board extends Model
{
    use HasUuids;

    protected $fillable = [
        'title',
        'type',
        'description',
        'size',
        'mode',
        'access_mode',
        'required_guild_id',
        'is_listed',
        'start_date',
        'end_date',
        'dice_roll_limit',
    ];

    /**
     * The kinds of event this app runs. Snakes & Ladders was the whole
     * product once, which is why the model is still called Board; see
     * docs/ROADMAP.md phase 5 and the backlog's "Boards → events" section.
     *
     * `available` gates what can actually be created. A type listed but not
     * available shows in the UI as coming soon rather than being absent —
     * hiding planned types entirely just means nobody knows they're coming.
     */
    public const EVENT_TYPES = [
        'SNAKES_LADDERS' => ['icon' => 'i-lucide-dice-6', 'available' => true],
        'BINGO' => ['icon' => 'i-lucide-grid-3x3', 'available' => false],
        'DROP_RACE' => ['icon' => 'i-lucide-swords', 'available' => false],
        'SKILL_RACE' => ['icon' => 'i-lucide-trophy', 'available' => false],
    ];

    /** @return array<int, string> */
    public static function availableEventTypes(): array
    {
        return array_keys(array_filter(self::EVENT_TYPES, fn ($meta) => $meta['available']));
    }

    protected $casts = [
        'is_listed' => 'boolean',
        // Cast explicitly — a missed datetime cast on PlayerBoard.last_roll_date
        // was a real 500 (see CLAUDE.md).
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    public function tiles(): HasMany
    {
        return $this->hasMany(Tile::class)->orderBy('position');
    }

    public function playerBoards(): HasMany
    {
        return $this->hasMany(PlayerBoard::class);
    }

    public function authors(): HasMany
    {
        return $this->hasMany(BoardAuthor::class);
    }

    public function boardTeams(): HasMany
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
}
