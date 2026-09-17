<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One event the plugin reported, kept so a retry answers the same thing instead of claiming twice. */
class PluginCompletion extends Model
{
    use HasUuids;

    public const KINDS = ['item', 'npc_kill'];

    protected $fillable = ['user_id', 'client_event_id', 'kind', 'name', 'quantity', 'rsn', 'occurred_at', 'claims'];

    protected $casts = [
        'quantity' => 'integer',
        'occurred_at' => 'datetime',
        'claims' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
