<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Append-only record of admin actions. See the migration for why actor and
 * target are stored as both an id and a text label.
 *
 * Writes go through record() only — nothing constructs one directly, so the
 * label-capturing can't be skipped by accident.
 */
class AuditLog extends Model
{
    use HasUuids;

    /**
     * Audit rows are never updated, and the migration doesn't create an
     * updated_at column for them to write to. Without this, every insert
     * fails on the missing column.
     */
    public const UPDATED_AT = null;

    protected $fillable = [
        'actor_id',
        'actor_label',
        'action',
        'target_type',
        'target_id',
        'target_label',
        'metadata',
        'ip_address',
    ];

    /**
     * created_at is cast explicitly rather than relying on it being an
     * obviously date-shaped name — the same missed cast on
     * PlayerBoard.last_roll_date was a real 500 (see CLAUDE.md).
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Action keys. Grouped by subject prefix, which is also what the admin
     * page's filter groups on — so the list stays the single source of
     * truth for "what can be logged" rather than being reconstructed from
     * whatever distinct values happen to be in the table.
     */
    public const ACTIONS = [
        'user.role_granted',
        'user.role_revoked',
        'user.permission_granted',
        'user.permission_revoked',
        'user.deleted',
        'task.deleted',
        'settings.updated',
    ];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * Records one action by the currently authenticated user.
     *
     * $target is the model acted upon, if any. Its label is resolved now,
     * while the record still exists — passing the model rather than an id is
     * what makes that possible, and is why deletions must call this BEFORE
     * the delete, not after.
     *
     * @param  array<string, mixed>  $metadata
     */
    public static function record(string $action, ?Model $target = null, array $metadata = []): void
    {
        $actor = Auth::user();

        static::create([
            'actor_id' => $actor?->id,
            // Falls back to a marker rather than an empty string: a row whose
            // actor column is blank reads as data loss, when in fact some
            // actions can legitimately originate outside a session.
            'actor_label' => $actor?->displayName() ?: 'system',
            'action' => $action,
            'target_type' => $target ? class_basename($target) : null,
            'target_id' => $target?->getKey(),
            'target_label' => $target ? self::labelFor($target) : null,
            'metadata' => $metadata ?: null,
            'ip_address' => Request::ip(),
        ]);
    }

    /**
     * Best available human-readable name for a target. Falls back to the key
     * so a model without any of these still logs something identifiable.
     */
    private static function labelFor(Model $target): string
    {
        if ($target instanceof User) {
            return $target->displayName();
        }

        return $target->title ?? $target->name ?? (string) $target->getKey();
    }
}
