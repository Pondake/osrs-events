<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One browser that agreed to be notified.
 *
 * Not one user: a phone and a desktop are two rows, and the settings page
 * lists them separately so "it works on my laptop but not my phone" is a
 * thing somebody can actually see rather than infer.
 */
class PushSubscription extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id', 'endpoint', 'public_key', 'auth_token',
        'content_encoding', 'user_agent', 'vapid_key', 'expired_at', 'last_used_at',
    ];

    /**
     * Encryption material for one device, and of no use to any client.
     * Hidden here rather than remembered at each call site — the settings
     * page returns these rows, and one forgotten `->makeVisible()`-shaped
     * mistake would hand a device's keys to anything that could read the
     * page props.
     */
    protected $hidden = ['public_key', 'auth_token'];

    protected $casts = [
        // Cast explicitly, like every other datetime here — see CLAUDE.md on
        // PlayerBoard.last_roll_date.
        'expired_at' => 'datetime',
        'last_used_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** The ones still worth sending to. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('expired_at');
    }

    /**
     * A rough name for the device, for the settings list.
     *
     * Deliberately crude — the goal is only for somebody to recognise which
     * of their own devices a row is, and a full UA parser is a dependency
     * plus a signature database to keep current for that.
     */
    public function deviceLabel(): string
    {
        $agent = $this->user_agent ?? '';

        $platform = match (true) {
            str_contains($agent, 'iPhone') => 'iPhone',
            str_contains($agent, 'iPad') => 'iPad',
            str_contains($agent, 'Android') => 'Android',
            str_contains($agent, 'Macintosh') => 'Mac',
            str_contains($agent, 'Windows') => 'Windows',
            str_contains($agent, 'Linux') => 'Linux',
            default => trans('notifications.device_unknown'),
        };

        $browser = match (true) {
            // Edge and Opera both carry "Chrome" in their UA, so they have to
            // be tested first or every browser on earth reads as Chrome.
            str_contains($agent, 'Edg/') => 'Edge',
            str_contains($agent, 'OPR/') => 'Opera',
            str_contains($agent, 'Firefox') => 'Firefox',
            str_contains($agent, 'Chrome') => 'Chrome',
            str_contains($agent, 'Safari') => 'Safari',
            default => null,
        };

        return $browser === null ? $platform : "{$platform} · {$browser}";
    }
}
