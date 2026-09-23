<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One OSRS character on an account. Position 0 is the main, and is mirrored
 * onto users.osrs_username by OsrsIdentityService — the only writer.
 */
class OsrsAccount extends Model
{
    use HasUuids;

    protected $fillable = ['user_id', 'username', 'position', 'osrs_verified_at', 'osrs_proven_at', 'osrs_proven_via'];

    protected $casts = [
        'position' => 'integer',
        'osrs_verified_at' => 'datetime',
        'osrs_proven_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isMain(): bool
    {
        return $this->position === 0;
    }
}
