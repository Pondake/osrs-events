<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserGuild extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = ['user_id', 'guild_id', 'guild_name', 'guild_icon', 'synced_at'];

    protected $casts = ['synced_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
