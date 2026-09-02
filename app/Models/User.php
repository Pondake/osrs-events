<?php

namespace App\Models;

use App\Support\NotificationCategory;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['discord_id', 'discord_username', 'nickname', 'osrs_username', 'osrs_verified_at', 'avatar_url', 'email', 'password', 'onboarding_completed_at', 'notification_preferences', 'display_preferences', 'push_opted_out_at'])]
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
            'notification_preferences' => 'array',
            'display_preferences' => 'array',
            'push_opted_out_at' => 'datetime',
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

    /**
     * What somebody types to confirm closing their account.
     *
     * The OSRS name where there is one — it is the thing every playing account
     * has and a passer-by at an unlocked laptop does not. An account that never
     * got that far still has to be able to leave, so it falls back to whatever
     * does identify it; see RequireOsrsUsername for the gate that made this
     * necessary.
     */
    public function deletionPhrase(): string
    {
        return $this->osrs_username ?: $this->displayName();
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

    public function pushSubscriptions(): HasMany
    {
        return $this->hasMany(PushSubscription::class);
    }

    /**
     * Does this person want to be told about this kind of thing?
     *
     * Three gates, cheapest first, and each means something different: the
     * explicit off switch, then the stored per-category answer, then the
     * category's own default for somebody who has never expressed one. The
     * defaults live in the catalogue rather than here so that adding a
     * category needs no backfill — see NotificationCategory.
     */
    public function wantsNotification(string $category): bool
    {
        if ($this->push_opted_out_at !== null) {
            return false;
        }

        $stored = $this->notification_preferences[$category] ?? null;

        return $stored === null
            ? NotificationCategory::defaultFor($category)
            : (bool) $stored;
    }

    /**
     * Every known category with the value actually in force, defaults filled
     * in — what the settings page renders switches from.
     *
     * @return array<string, bool>
     */
    public function notificationPreferences(): array
    {
        return collect(NotificationCategory::keys())
            ->mapWithKeys(fn (string $key) => [
                $key => $this->notification_preferences[$key] ?? NotificationCategory::defaultFor($key),
            ])
            ->all();
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

    /**
     * Authorship, and nothing else — an admin is an ordinary user here.
     *
     * Admins may edit any event, but only from the admin section, on routes
     * of its own (see Admin\BoardController). On the public side of the app
     * an admin has exactly the rights their authorship gives them. The point
     * is that using the power is a deliberate act in a place built for it,
     * rather than an invisible extra permission attached to every page an
     * admin happens to open.
     *
     * Reading is a separate question and deliberately unchanged:
     * BoardAccessService::canBypass() still lets an admin OPEN any event,
     * because you cannot moderate what you cannot see.
     */
    public function canEditEvent(Event $event): bool
    {
        return $event->authors()->where('user_id', $this->id)->exists();
    }

    /**
     * Ported from InvitesService::assertOwnerOrAdmin() — the owner, not just
     * any co-author. Same rule as canEditEvent() on the admin question: an
     * admin gets this through the admin section or not at all.
     */
    public function isEventOwner(Event $event): bool
    {
        return $event->authors()->where(['user_id' => $this->id, 'is_owner' => true])->exists();
    }
}
