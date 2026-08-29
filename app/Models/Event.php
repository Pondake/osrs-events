<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A competition: what it is, when it runs, and who may take part.
 *
 * How it is *played* lives on a type-specific payload — for Snakes & Ladders
 * that is the Board (grid size, dice limit, tiles, player progress). Bingo
 * and the phase 7 types will bring their own, which is exactly why this split
 * exists; see the split_events_from_boards migration.
 *
 * Ownership, entry and team assignment hang off the event rather than the
 * board, so an event type with no board can still be owned, joined and
 * invited to.
 */
class Event extends Model
{
    use HasUuids;

    /**
     * Deleting an event does not delete an event.
     *
     * Every child table cascades off this row — board, tiles, player boards,
     * completions, standings, participants, invites — so a hard delete takes
     * a clan's whole event with it and nothing can put it back. The trashed
     * row drops out of every list and 404s on every route, which is the part
     * anyone deleting actually wants; an admin can restore it from
     * /admin/events.
     */
    use SoftDeletes;

    protected $fillable = [
        'title',
        'type',
        'metric',
        'description',
        'mode',
        'access_mode',
        'required_guild_id',
        'is_listed',
        'start_date',
        'end_date',
        // Where announcements go, when a host wires one up. An ordinary
        // setting: changing it announces nothing by itself.
        'discord_webhook_url',
        // `paused_at` and `pause_reason` are deliberately absent: pausing is
        // its own action with its own permission check and its own audit
        // entry, not a field somebody can slip into an ordinary settings save.
    ];

    protected $casts = [
        'is_listed' => 'boolean',
        // Cast explicitly — a missed datetime cast on PlayerBoard.last_roll_date
        // was a real 500 (see CLAUDE.md).
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'paused_at' => 'datetime',
        'standings_stale_since' => 'datetime',
    ];

    /**
     * On hold: readable, not playable.
     *
     * Every write a *player* makes checks this — rolling, ticking a tile,
     * claiming a square, joining. Everything a *host* does stays open,
     * because pausing is usually the prelude to fixing whatever went wrong.
     */
    public function isPaused(): bool
    {
        return $this->paused_at !== null;
    }

    /**
     * Over: nothing anyone does moves it any more.
     *
     * A pure function of `end_date`, mirroring `boardEventStatus()` in
     * resources/js/Support/board.js — the JS side already derives the
     * "Ended" badge shown on the page from this same date, so a mutation
     * endpoint that didn't check it too would let a player roll or tick a
     * tile on a board the page itself displays as finished. Ended outranks
     * paused, same reasoning as the JS `eventStatus()` wrapper: a paused
     * event that ran past its end date is over, not "paused".
     */
    public function isEnded(): bool
    {
        // endOfDay(), not a bare isPast(): boardEventStatus() in board.js only
        // calls a day "ended" once it is wholly in the past (utcDay(end) <
        // today), so an event running through the end of its own end_date is
        // still live for the rest of that day, not ended at its first
        // midnight tick.
        return $this->end_date !== null && $this->end_date->copy()->endOfDay()->isPast();
    }

    /**
     * Not open yet: nothing anyone does on it should count until its own
     * start date arrives.
     *
     * Same mirror-of-the-JS reasoning as isEnded() — boardEventStatus()
     * already shows the "Upcoming" badge from this same date, but nothing on
     * the server enforced it: PlayerBoardController::roll() checked isPaused()
     * and isEnded() and stopped there, so a board dated to start next month
     * could be rolled on today, the moment the event exists — reported
     * directly, from exactly that state (a "Starts next month" board with a
     * working dice button). startOfDay(), not a bare isFuture(): the mirror
     * image of isEnded()'s endOfDay() — the start date's own day already
     * counts as started, not upcoming, so a board due today is live from
     * midnight, the same instant boardEventStatus() stops calling it
     * "Upcoming".
     */
    public function isUpcoming(): bool
    {
        return $this->start_date !== null && $this->start_date->copy()->startOfDay()->isFuture();
    }

    /**
     * The fields a standing is measured against.
     *
     * Change any of them and every row on the event describes a window that
     * no longer exists — see the standings_stale_since migration.
     */
    public const MEASUREMENT_FIELDS = ['metric', 'start_date', 'end_date'];

    /** Whether the numbers were read before the question last changed. */
    public function standingsAreStale(): bool
    {
        return $this->standings_stale_since !== null;
    }

    /**
     * The kinds of event this app runs, per docs/ROADMAP.md phases 5 and 7.
     *
     * `available` gates what can be created. A type listed but unavailable
     * shows in the UI as coming soon rather than being absent — hiding
     * planned types just means nobody knows they're coming.
     */
    public const EVENT_TYPES = [
        'SNAKES_LADDERS' => ['icon' => 'i-lucide-dice-6', 'available' => true, 'needsMetric' => false, 'metricKind' => null],
        'SKILL_RACE' => ['icon' => 'i-lucide-trophy', 'available' => true, 'needsMetric' => true, 'metricKind' => 'skill'],
        'BINGO' => ['icon' => 'i-lucide-grid-3x3', 'available' => true, 'needsMetric' => false, 'metricKind' => null],
        // A drop race is a boss killcount race. Wise Old Man returns
        // `bosses.{name}.kills` in the same envelope as `skills.{name}
        // .experience`, so this reuses the whole standings pipeline — the
        // only thing that differs is which branch of the response is read.
        'DROP_RACE' => ['icon' => 'i-lucide-swords', 'available' => true, 'needsMetric' => true, 'metricKind' => 'boss'],
    ];

    /**
     * The metric a skill event can race on.
     *
     * These are **Wise Old Man's own metric names**, not ours. Their API is
     * the intended source of the XP gains this ranks on, and their
     * competition model is what this whole event type is modelled after —
     * see the credit in the README. Keeping their vocabulary means a metric
     * goes straight into an API call with no translation table to drift.
     *
     * Skills only for now. Wise Old Man also supports boss killcounts and
     * activity metrics, which is where DROP_RACE would eventually point.
     */
    public const SKILL_METRICS = [
        'overall', 'attack', 'defence', 'strength', 'hitpoints', 'ranged',
        'prayer', 'magic', 'cooking', 'woodcutting', 'fletching', 'fishing',
        'firemaking', 'crafting', 'smithing', 'mining', 'herblore', 'agility',
        'thieving', 'slayer', 'farming', 'runecrafting', 'hunter',
        'construction',
    ];

    /**
     * Boss killcount metrics, again in Wise Old Man's own vocabulary and for
     * the same reason as SKILL_METRICS: their API is the source, and a
     * translation table between two spellings is a thing to keep in step
     * forever. Pulled from a live response rather than transcribed.
     */
    public const BOSS_METRICS = [
        'abyssal_sire', 'alchemical_hydra', 'amoxliatl', 'araxxor', 'artio',
        'barrows_chests', 'brutus', 'bryophyta', 'callisto', 'calvarion',
        'cerberus', 'chambers_of_xeric', 'chambers_of_xeric_challenge_mode',
        'chaos_elemental', 'chaos_fanatic', 'commander_zilyana', 'corporeal_beast',
        'crazy_archaeologist', 'dagannoth_prime', 'dagannoth_rex', 'dagannoth_supreme',
        'deranged_archaeologist', 'doom_of_mokhaiotl', 'duke_sucellus',
        'general_graardor', 'giant_mole', 'grotesque_guardians', 'hespori',
        'kalphite_queen', 'king_black_dragon', 'kraken', 'kreearra', 'kril_tsutsaroth',
        'lunar_chests', 'mad_angel', 'maggot_king', 'mimic', 'nex', 'nightmare',
        'phosanis_nightmare', 'obor', 'phantom_muspah', 'sarachnis', 'scorpia',
        'scurrius', 'shellbane_gryphon', 'skotizo', 'sol_heredit', 'spindel',
        'tempoross', 'the_gauntlet', 'the_corrupted_gauntlet', 'the_hueycoatl',
        'the_leviathan', 'the_royal_titans', 'the_whisperer', 'theatre_of_blood',
        'theatre_of_blood_hard_mode', 'thermonuclear_smoke_devil', 'tombs_of_amascut',
        'tombs_of_amascut_expert', 'tzkal_zuk', 'tztok_jad', 'vardorvis',
        'venenatis', 'vetion', 'vorkath', 'wintertodt', 'yama', 'zalcano',
        'zulrah',
    ];

    /** Whether this event's type races on a metric at all. */
    public function needsMetric(): bool
    {
        return self::EVENT_TYPES[$this->type]['needsMetric'] ?? false;
    }

    /**
     * Which half of Wise Old Man's response this event's metric lives in —
     * 'skill', 'boss', or null for types that do not race on one.
     */
    public function metricKind(): ?string
    {
        return self::EVENT_TYPES[$this->type]['metricKind'] ?? null;
    }

    /**
     * The metrics an event of the given type may choose from.
     *
     * @return array<int, string>
     */
    public static function metricsForType(?string $type): array
    {
        return match (self::EVENT_TYPES[$type]['metricKind'] ?? null) {
            'skill' => self::SKILL_METRICS,
            'boss' => self::BOSS_METRICS,
            default => [],
        };
    }

    /**
     * The event types that race on a metric, so the sync command can select
     * them without naming each one and forgetting the next.
     *
     * @return array<int, string>
     */
    public static function metricTypes(): array
    {
        return array_keys(array_filter(self::EVENT_TYPES, fn ($meta) => $meta['needsMetric']));
    }

    /** Every metric any available type could use, for validation. */
    public static function allMetrics(): array
    {
        return [...self::SKILL_METRICS, ...self::BOSS_METRICS];
    }

    /** @return array<int, string> */
    public static function availableTypes(): array
    {
        return array_keys(array_filter(self::EVENT_TYPES, fn ($meta) => $meta['available']));
    }

    /**
     * hasOne, not hasMany: a Snakes & Ladders event has exactly one board.
     * Null for any event type that doesn't use one.
     */
    /**
     * Every event this user has a stake in, whatever its type.
     *
     * "Mine" used to mean a PlayerBoard row, which only Snakes & Ladders
     * creates — so a race you entered, a bingo card you claimed on, and even
     * an event you created yourself were all absent from your own lists. The
     * hub then looked like a public directory with nothing of yours in it,
     * and the profile page said you had no boards.
     *
     * Authorship counts too: an event you run is yours whether or not you
     * also play in it, and for a race the host frequently does not.
     */
    public function scopeInvolving(Builder $query, ?User $user): Builder
    {
        if ($user === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(fn (Builder $q) => $q
            ->whereHas('authors', fn (Builder $a) => $a->where('user_id', $user->id))
            ->orWhere(fn (Builder $played) => $played->playedBy($user)));
    }

    /** Events this user runs — author or owner, playing or not. */
    public function scopeHostedBy(Builder $query, ?User $user): Builder
    {
        if ($user === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas('authors', fn (Builder $a) => $a->where('user_id', $user->id));
    }

    /**
     * Events this user takes part in.
     *
     * Joining is its own record now (EventParticipant), which is what makes
     * this one question with one answer instead of three tables to union.
     * Hosting is deliberately not participation — running a race you are not
     * entered in is common.
     */
    public function scopePlayedBy(Builder $query, ?User $user): Builder
    {
        if ($user === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(fn (Builder $q) => $q
            ->whereHas('participants', fn (Builder $p) => $p->where('user_id', $user->id))
            // The play tables as well, for anyone who was already playing
            // when joining became a record of its own. The migration carried
            // those across, so this is a belt-and-braces read rather than the
            // primary one — and it keeps a row written directly by a test or
            // a fixture from vanishing off somebody's list.
            ->orWhereHas('standings', fn (Builder $s) => $s->where('user_id', $user->id))
            ->orWhereHas('board.playerBoards', fn (Builder $p) => $p->where('user_id', $user->id))
            ->orWhereHas('bingoCard.squares.completions', fn (Builder $c) => $c->where('user_id', $user->id)));
    }

    /** Everybody who has explicitly joined, whatever the type plays on. */
    public function participants(): HasMany
    {
        return $this->hasMany(EventParticipant::class);
    }

    public function board(): HasOne
    {
        return $this->hasOne(Board::class);
    }

    /**
     * hasOne for the same reason board() is: a bingo event has exactly one
     * card, and every other type has none.
     */
    public function bingoCard(): HasOne
    {
        return $this->hasOne(BingoCard::class);
    }

    public function authors(): HasMany
    {
        return $this->hasMany(BoardAuthor::class);
    }

    public function eventTeams(): HasMany
    {
        return $this->hasMany(BoardTeam::class);
    }

    public function invites(): HasMany
    {
        return $this->hasMany(BoardInvite::class);
    }

    public function accesses(): HasMany
    {
        return $this->hasMany(BoardAccess::class);
    }

    /**
     * Leaderboard rows for metric events. Empty for types that don't race on
     * one — a Snakes & Ladders event never gets a standing.
     */
    public function standings(): HasMany
    {
        return $this->hasMany(EventStanding::class);
    }

    /**
     * Progress rows live on the board, but callers want them by event.
     * hasManyThrough rather than hopping via ->board->playerBoards, so an
     * event with no board yields an empty set instead of a null dereference.
     */
    public function playerBoards(): HasManyThrough
    {
        return $this->hasManyThrough(PlayerBoard::class, Board::class);
    }
}
