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

    protected $fillable = ['title', 'type', 'metric', 'description', 'is_active', 'settings', 'layout', 'created_by', 'guild_id'];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
        'layout' => 'array',
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

    /**
     * What the create form offers.
     *
     * A filter and nothing else — the ordering lives with the caller. It used
     * to sort by title here, which silently won over the caller's own
     * ordering because it was applied first: the gallery asked for "yours
     * first" and got alphabetical.
     */
    public function scopeSuggestable(Builder $query): Builder
    {
        return $query->where('is_active', true);
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

    /** Whether this blueprint carries a board and not only its settings. */
    public function hasLayout(): bool
    {
        return is_array($this->layout) && $this->layout !== [];
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

    /**
     * A snapshot of the board itself — every tile, or every square.
     *
     * Each entry carries the task id AND the title it had at the time. The id
     * keeps a tile linked to the shared Task while that still exists; the
     * title survives the Task being renamed or deleted, so a year-old
     * blueprint still describes itself rather than turning into a grid of
     * blanks.
     *
     * Empty for an event with nothing on its board yet — there is no point
     * saving a layout that is only a grid size, and `hasLayout()` is what the
     * picker keys off.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function layoutFrom(Event $event): array
    {
        if ($event->board !== null) {
            return $event->board->tiles()
                ->with('task:id,title')
                ->orderBy('position')
                ->get()
                ->map(fn (Tile $tile) => [
                    'position' => $tile->position,
                    'type' => $tile->type,
                    'target_position' => $tile->target_position,
                    'task_id' => $tile->task_id,
                    'title' => $tile->title_override ?: $tile->task?->title,
                ])
                ->all();
        }

        if ($event->bingoCard !== null) {
            return $event->bingoCard->squares()
                ->with('task:id,title')
                ->orderBy('position')
                ->get()
                // A square nobody has filled in carries nothing worth saving.
                ->reject(fn (BingoSquare $square) => $square->task_id === null
                    && $square->title_override === null
                    && ! $square->is_wildcard
                    && $square->points === 1)
                ->map(fn (BingoSquare $square) => [
                    'position' => $square->position,
                    'task_id' => $square->task_id,
                    'title' => $square->title_override ?: $square->task?->title,
                    'points' => $square->points,
                    'is_wildcard' => $square->is_wildcard,
                ])
                ->values()
                ->all();
        }

        return [];
    }

    /**
     * Pour this blueprint's board into a freshly created event.
     *
     * Positions outside the new grid are dropped rather than clamped. A
     * layout is tied to the size it was saved at — the picker filters on that
     * — but a size can still be changed on the Format step after a template
     * is applied, and silently stacking three tiles onto the last square
     * would be worse than leaving them out.
     *
     * A task that no longer exists loses its id and keeps its title, so the
     * tile still says what it asks for.
     */
    public function applyLayoutTo(Event $event): void
    {
        if (! $this->hasLayout()) {
            return;
        }

        $liveTasks = Task::whereIn('id', collect($this->layout)->pluck('task_id')->filter()->all())
            ->pluck('id')
            ->all();

        $taskFor = fn (?string $id) => in_array($id, $liveTasks, true) ? $id : null;

        if ($event->board !== null) {
            $last = $event->board->tileCount() - 1;

            foreach ($this->layout as $entry) {
                if (($entry['position'] ?? null) === null || $entry['position'] > $last) {
                    continue;
                }

                Tile::updateOrCreate(
                    ['board_id' => $event->board->id, 'position' => $entry['position']],
                    [
                        'type' => $entry['type'] ?? 'NORMAL',
                        // A snake pointing off the end of a smaller board is
                        // a snake to nowhere, so it becomes a plain tile.
                        'target_position' => ($entry['target_position'] ?? null) !== null
                            && $entry['target_position'] <= $last
                                ? $entry['target_position']
                                : null,
                        'task_id' => $taskFor($entry['task_id'] ?? null),
                        'title_override' => $taskFor($entry['task_id'] ?? null) === null
                            ? ($entry['title'] ?? null)
                            : null,
                    ],
                );
            }

            return;
        }

        if ($event->bingoCard !== null) {
            $last = $event->bingoCard->squareCount() - 1;

            foreach ($this->layout as $entry) {
                if (($entry['position'] ?? null) === null || $entry['position'] > $last) {
                    continue;
                }

                BingoSquare::updateOrCreate(
                    ['bingo_card_id' => $event->bingoCard->id, 'position' => $entry['position']],
                    [
                        'task_id' => $taskFor($entry['task_id'] ?? null),
                        'title_override' => $taskFor($entry['task_id'] ?? null) === null
                            ? ($entry['title'] ?? null)
                            : null,
                        'points' => $entry['points'] ?? 1,
                        'is_wildcard' => $entry['is_wildcard'] ?? false,
                    ],
                );
            }
        }
    }
}
