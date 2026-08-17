<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['discord_id', 'discord_username', 'nickname', 'avatar_url'])]
#[Hidden(['remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasUuids, Notifiable;

    /** Display name shown throughout the app — nickname if set, else the raw Discord username. */
    public function displayName(): string
    {
        return $this->nickname ?? $this->discord_username;
    }

    public function playerBoards(): HasMany
    {
        return $this->hasMany(PlayerBoard::class);
    }

    public function boardAuthors(): HasMany
    {
        return $this->hasMany(BoardAuthor::class);
    }

    public function teamMembers(): HasMany
    {
        return $this->hasMany(TeamMember::class);
    }

    public function userGuilds(): HasMany
    {
        return $this->hasMany(UserGuild::class);
    }

    public function boardAccesses(): HasMany
    {
        return $this->hasMany(BoardAccess::class);
    }

    public function userRoles(): HasMany
    {
        return $this->hasMany(UserRole::class);
    }

    public function userPermissions(): HasMany
    {
        return $this->hasMany(UserPermission::class);
    }

    public function hasRole(string $roleName): bool
    {
        return $this->userRoles()->whereHas('role', fn ($q) => $q->where('name', $roleName))->exists();
    }
}
