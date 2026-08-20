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

#[Fillable(['discord_id', 'discord_username', 'nickname', 'avatar_url', 'email', 'password', 'onboarding_completed_at'])]
#[Hidden(['remember_token', 'password'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasUuids, Notifiable;

    /**
     * Laravel's auto-hashing cast — any ->create()/->update() with a raw
     * 'password' value hashes it before it touches the database, and
     * Auth::attempt()'s Hash::check() comparison stays correct either way.
     * Removes any chance of accidentally persisting a plaintext password.
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'onboarding_completed_at' => 'datetime',
        ];
    }

    /**
     * Display name shown throughout the app. Falls all the way through to
     * email because an email/password account has no discord_username at
     * all — the declared `string` return would have been a TypeError on any
     * account where both were null, which became reachable the moment
     * non-Discord signup shipped.
     */
    public function displayName(): string
    {
        return $this->nickname ?? $this->discord_username ?? $this->email ?? '';
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

    public function isAdmin(): bool
    {
        return $this->hasRole('ADMIN');
    }

    /** Mirrors the old usePermissions.ts: ADMIN bypasses every granular check. */
    public function hasPermission(string $key): bool
    {
        return $this->isAdmin() || $this->userPermissions()->where('permission_key', $key)->exists();
    }

    public function canEditBoard(Board $board): bool
    {
        return $this->isAdmin() || $board->authors()->where('user_id', $this->id)->exists();
    }

    /** Ported from InvitesService::assertOwnerOrAdmin() — owner (not just any co-author) or admin. */
    public function isBoardOwnerOrAdmin(Board $board): bool
    {
        return $this->isAdmin() || $board->authors()->where(['user_id' => $this->id, 'is_owner' => true])->exists();
    }
}
