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

    protected $fillable = ['user_id', 'client_event_id', 'kind', 'name', 'quantity', 'rsn', 'occurred_at', 'context', 'claims'];

    protected $casts = [
        'quantity' => 'integer',
        'occurred_at' => 'datetime',
        'context' => 'array',
        'claims' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * What a host can actually judge a claim by. Only what the plugin sent —
     * no chat, no other players, no coordinates, see docs/runelite-plugin.md.
     * Null when this report carried no context (predates the field, or the
     * client had none to send).
     */
    public function reviewContext(): ?array
    {
        $context = $this->context;

        if (blank($context)) {
            return null;
        }

        return [
            'source' => $context['source'] ?? null,
            'npcName' => $context['npc_name'] ?? null,
            'npcLevel' => $context['npc_level'] ?? null,
            'killCount' => $context['kill_count'] ?? null,
            'items' => collect($context['items'] ?? [])
                ->map(fn (array $item) => ['name' => $item['name'] ?? null, 'quantity' => $item['quantity'] ?? null])
                ->all(),
            'occurredAt' => $this->occurred_at?->toIso8601String(),
        ];
    }
}
