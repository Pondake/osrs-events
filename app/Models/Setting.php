<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Runtime-editable site settings, read through a single cached array.
 *
 * Every defaulted key lives in DEFAULTS below, which is the actual contract:
 * a key missing from the table falls back to its default rather than null,
 * so a fresh install works with an empty table and adding a setting needs no
 * data migration. get() on an unknown key returns null and is a programming
 * error, not a supported state.
 */
class Setting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    protected $casts = ['value' => 'json'];

    private const CACHE_KEY = 'settings.all';

    /**
     * Editable keys and their fallbacks. Anything not listed here is not a
     * setting — the admin form validates against exactly this list, so a
     * stray key can't be written through the UI.
     */
    public const DEFAULTS = [
        'registration_open' => true,
        'default_board_size' => 'SIZE_7X7',
        'default_dice_roll_limit' => 1,
        // How long the create-event form pre-fills a new event to run for.
        // A default, not a rule: the dates stay editable on the form, this
        // only decides what they start at. Clans settle into a rhythm — a
        // week, a fortnight, a month — and this is where that gets said once
        // instead of being corrected on every event.
        //
        // A short form — `10d`, `2w`, `1m` — parsed by Support\EventDuration.
        // A bare number still reads as days, so a row left over from when
        // this was an integer keeps working. The unit is kept rather than
        // converted because a month has to mean a calendar month.
        'default_event_duration' => '2w',
        'announcement' => null,
        'announcement_type' => 'info',
        // Whether a host may wire an event up to a Discord channel at all.
        //
        // Off by default and deliberately so: it makes the app POST to a URL
        // somebody typed in, into a room full of people who did not ask this
        // app for anything. That is worth switching on knowingly, after
        // somebody has watched it post to a real server — see the backlog.
        // With it off the field is not offered and nothing is ever sent,
        // whatever is stored on an event.
        'discord_webhooks_enabled' => false,
        // Every "support" button on the site points straight here. A default
        // rather than a nullable: an empty value would render buttons that go
        // nowhere, and the profile is not something that changes often enough
        // to be worth that failure mode.
        'kofi_url' => 'https://ko-fi.com/pondake',
        // A shared password in front of the whole site for the pre-launch
        // stretch — see EnsureSiteUnlocked. The password is stored hashed
        // under site_lock_password and is never read back into a form; the
        // admin page can set a new one or leave it alone.
        'site_lock_enabled' => false,
        'site_lock_password' => null,
    ];

    /** Banner styles. Keys are stored; the UI maps them to colour and icon. */
    public const ANNOUNCEMENT_TYPES = ['info', 'success', 'warning', 'error'];

    /**
     * All settings, defaults merged under whatever the table overrides.
     *
     * Named cached(), NOT all() — Eloquent already defines a static all()
     * that returns a Collection of models. Overriding it with something
     * returning a plain key/value array would silently break every caller
     * expecting the framework's behaviour.
     */
    public static function cached(): array
    {
        // DEFAULTS is merged on READ, not baked into the cached value.
        //
        // It used to be inside the closure, which meant the cache held a
        // snapshot of whichever keys existed when it was written — so adding
        // a new setting made every read of it an "Undefined array key" until
        // somebody happened to clear the cache. That is a deploy-time 500
        // waiting for the next setting anyone adds, and it duly happened the
        // first time one was (site_lock_password). Only the table's own rows
        // are cached now, which is the part that costs a query.
        $stored = Cache::rememberForever(
            self::CACHE_KEY,
            fn () => static::query()->pluck('value', 'key')->all(),
        );

        return [...self::DEFAULTS, ...$stored];
    }

    public static function get(string $key): mixed
    {
        return self::cached()[$key] ?? null;
    }

    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget(self::CACHE_KEY);
    }

    /** @param array<string, mixed> $values */
    public static function setMany(array $values): void
    {
        foreach ($values as $key => $value) {
            static::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        Cache::forget(self::CACHE_KEY);
    }
}
