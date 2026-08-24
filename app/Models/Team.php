<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    use HasUuids;

    protected $fillable = ['name', 'icon_url', 'guild_id', 'guild_name'];

    public function members(): HasMany
    {
        return $this->hasMany(TeamMember::class);
    }

    /**
     * The teams a user may see at all.
     *
     * The old rule was "your Discord guilds, **plus any team with no
     * guild_id**" — and since a guild is optional, that second clause made
     * every private team on the site visible to every account. Reported from
     * staging: the event form offered teams that had nothing to do with the
     * person creating the event.
     *
     * Three ways in now, and no catch-all:
     *
     *   - you are in it (any role);
     *   - it belongs to a Discord server you are also in;
     *   - you are an admin.
     *
     * A team with no server, created by someone else, that you are not in,
     * is nobody's business but theirs. That is the whole point of letting a
     * team exist without a server — it is a private group, not an
     * unclaimed one.
     */
    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if ($user?->isAdmin()) {
            return $query;
        }

        if ($user === null) {
            return $query->whereRaw('1 = 0');
        }

        $guildIds = UserGuild::where('user_id', $user->id)->pluck('guild_id');

        return $query->where(fn (Builder $q) => $q
            ->whereHas('members', fn (Builder $m) => $m->where('user_id', $user->id))
            ->orWhere(fn (Builder $g) => $g
                ->whereNotNull('guild_id')
                ->whereIn('guild_id', $guildIds)));
    }

    public function boardTeams(): HasMany
    {
        return $this->hasMany(BoardTeam::class);
    }

    public function playerBoards(): HasMany
    {
        return $this->hasMany(PlayerBoard::class);
    }

    /**
     * This user's role in THIS team, or null if they aren't in it.
     *
     * Reads the already-loaded members collection when there is one, so the
     * teams index — which renders a can-manage flag per team per row — costs
     * the one eager load it already does rather than a query per card.
     */
    public function roleFor(?User $user): ?string
    {
        if (! $user) {
            return null;
        }

        if ($this->relationLoaded('members')) {
            return $this->members->firstWhere('user_id', $user->id)?->role;
        }

        return $this->members()->where('user_id', $user->id)->value('role');
    }

    /** Rename the team, and add/remove/promote its members. */
    public function isManagedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        // Admin bypasses, same rule as User::hasPermission(). Everything else
        // is per-team and always was, once OWNER/MANAGER/MEMBER existed: the
        // global TEAM_MANAGER role used to grant this over every team on the
        // site and was retired in retire_global_team_manager_role — the reason
        // it survived that long, and the reason it is gone, are both there.
        return $user->isAdmin()
            || in_array($this->roleFor($user), TeamMember::MANAGING_ROLES, true);
    }

    /**
     * Deleting is the owner's alone — a promoted manager can do everything
     * to a team except destroy it and everyone's history in it.
     */
    public function isOwnedBy(?User $user): bool
    {
        return $user && ($user->isAdmin() || $this->roleFor($user) === TeamMember::OWNER);
    }
}
