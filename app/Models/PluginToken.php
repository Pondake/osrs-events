<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * The code a player pastes into the RuneLite plugin. One per account; only
 * the hash is stored, so a lost code is replaced, never shown again.
 */
class PluginToken extends Model
{
    use HasUuids;

    public const PREFIX = 'ose_';

    protected $fillable = ['user_id', 'token_hash', 'hint', 'last_used_at'];

    protected $hidden = ['token_hash'];

    protected $casts = ['last_used_at' => 'datetime'];

    /** Replaces any existing code and returns the new one in plain text. */
    public static function issueFor(User $user): string
    {
        $plain = self::PREFIX.Str::random(40);

        static::updateOrCreate(['user_id' => $user->id], [
            'token_hash' => hash('sha256', $plain),
            'hint' => substr($plain, -4),
            'last_used_at' => null,
        ]);

        return $plain;
    }

    public static function findByPlain(string $plain): ?self
    {
        if (! str_starts_with($plain, self::PREFIX)) {
            return null;
        }

        return static::where('token_hash', hash('sha256', $plain))->first();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
