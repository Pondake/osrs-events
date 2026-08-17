<?php

namespace App\Models;

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

    public function boardTeams(): HasMany
    {
        return $this->hasMany(BoardTeam::class);
    }

    public function playerBoards(): HasMany
    {
        return $this->hasMany(PlayerBoard::class);
    }
}
