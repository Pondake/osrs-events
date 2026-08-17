<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BoardAccess extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = ['board_id', 'user_id', 'invite_id', 'access_mode'];

    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function invite(): BelongsTo
    {
        return $this->belongsTo(BoardInvite::class, 'invite_id');
    }
}
