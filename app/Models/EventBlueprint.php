<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A reusable starting point for an event — see the create_event_blueprints
 * migration for what it is and why it isn't a Task.
 *
 * It carries two kinds of thing. The columns are what the blueprint IS: its
 * name, the type of event it makes, whether it is still offered. `settings`
 * is what it fills in: the form fields a host would otherwise type out. The
 * split matters because the second list differs per event type and the first
 * does not.
 */
class EventBlueprint extends Model
{
    use HasUuids;

    protected $fillable = ['title', 'type', 'metric', 'description', 'is_active', 'settings', 'created_by', 'guild_id'];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
    ];

    /**
     * Everything a blueprint is allowed to fill in, and nothing else.
     *
     * An allow-list rather than "whatever was in the JSON", because applying
     * a blueprint writes into a form that then posts to the create endpoint.
     * A stored key nobody vetted would be a stored field nobody vetted.
     *
     * Absent from the list on purpose: `title`, `type`, `metric` and
     * `description` have columns of their own, and the dates — a format is
     * reusable precisely because it does not carry last month's window.
     */
    public const APPLICABLE = [
        // The event itself.
        'mode',
        'access_mode',
        'is_listed',
        // Snakes & Ladders.
        'size',
        'dice_roll_limit',
        // Bingo.
        'bingo_size',
        'win_condition',
        'win_lines',
        'line_bonus',
        'requires_approval',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** What the create form offers: active, by title. */
    public function scopeSuggestable(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('title');
    }

    /**
     * Whose blueprints a person may start an event from.
     *
     * The same rule teams use: the global set that ships with the app (no
     * owner, no server), your own, and your clan's. A format somebody wrote
     * for their clan is not obviously public — it carries their event's
     * settings and their clan's name in the title as often as not.
     */
    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if ($user === null) {
            return $query->whereNull('created_by')->whereNull('guild_id');
        }

        if ($user->isAdmin()) {
            return $query;
        }

        $guildIds = UserGuild::where('user_id', $user->id)->pluck('guild_id');

        return $query->where(fn (Builder $q) => $q
            // The set that belongs to nobody.
            ->where(fn (Builder $global) => $global->whereNull('created_by')->whereNull('guild_id'))
            ->orWhere('created_by', $user->id)
            ->orWhere(fn (Builder $clan) => $clan
                ->whereNotNull('guild_id')
                ->whereIn('guild_id', $guildIds)));
    }

    /** The stored settings, filtered to what may actually be applied. */
    public function applicableSettings(): array
    {
        return collect($this->settings ?? [])
            ->only(self::APPLICABLE)
            ->reject(fn ($value) => $value === null)
            ->all();
    }

    /**
     * A snapshot of an event, as a set of settings.
     *
     * A copy, not a link — decided 2026-08-22. Editing the event afterwards
     * leaves the template alone, and editing the template leaves the event
     * alone; the alternative lets somebody's template change under another
     * host's hands without either of them noticing.
     */
    public static function settingsFrom(Event $event): array
    {
        $settings = [
            'mode' => $event->mode,
            'access_mode' => $event->access_mode,
            'is_listed' => $event->is_listed,
        ];

        if ($event->board !== null) {
            $settings['size'] = $event->board->size;
            $settings['dice_roll_limit'] = $event->board->dice_roll_limit;
        }

        if ($event->bingoCard !== null) {
            $settings['bingo_size'] = $event->bingoCard->size;
            $settings['win_condition'] = $event->bingoCard->win_condition;
            $settings['win_lines'] = $event->bingoCard->winLines();
            $settings['line_bonus'] = $event->bingoCard->line_bonus;
            $settings['requires_approval'] = $event->bingoCard->requires_approval;
        }

        return collect($settings)->reject(fn ($value) => $value === null)->all();
    }
}
