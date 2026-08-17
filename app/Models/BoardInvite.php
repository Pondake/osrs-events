<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BoardInvite extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = ['board_id', 'token', 'short_code', 'label', 'created_by', 'expires_at', 'max_uses', 'use_count'];

    protected $casts = ['expires_at' => 'datetime'];

    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }

    public function accesses(): HasMany
    {
        return $this->hasMany(BoardAccess::class, 'invite_id');
    }
}
