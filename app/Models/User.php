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
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['discord_id', 'discord_username', 'nickname', 'osrs_username', 'osrs_verified_at', 'avatar_url', 'email', 'password', 'onboarding_completed_at'])]
#[Hidden(['remember_token', 'password'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    // HasRoles brings roles()/permissions() relations, hasRole(), and
    // registers everything with Laravel's Gate — so `$user->can('key')` works
    // alongside the explicit checks below.
    use HasFactory, HasRoles, HasUuids, Notifiable;

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
            'osrs_verified_at' => 'datetime',
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

    public function isAdmin(): bool
    {
        return $this->hasRole('ADMIN');
    }

    /**
     * Mirrors the old usePermissions.ts: ADMIN bypasses every granular check.
     *
     * Kept as a named method rather than letting call sites use spatie's
     * `hasPermissionTo()` directly, for two reasons. The admin bypass is a
     * rule of this app, not of the package — spatie would answer "no" for an
     * admin who was never explicitly granted the key. And
     * `hasPermissionTo('nope')` *throws* PermissionDoesNotExist for a key with
     * no row, where every caller here wants a plain false; a permission that
     * has not been seeded is not an exception, it is simply not granted —
     * hence checkPermissionTo(), which is spatie's non-throwing variant.
     */
    public function hasPermission(string $key): bool
    {
        return $this->isAdmin() || $this->checkPermissionTo($key);
    }

    public function canEditEvent(Event $event): bool
    {
        return $this->isAdmin() || $event->authors()->where('user_id', $this->id)->exists();
    }

    /** Ported from InvitesService::assertOwnerOrAdmin() — owner (not just any co-author) or admin. */
    public function isEventOwnerOrAdmin(Event $event): bool
    {
        return $this->isAdmin() || $event->authors()->where(['user_id' => $this->id, 'is_owner' => true])->exists();
    }
}
