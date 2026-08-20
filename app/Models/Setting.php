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
        'announcement' => null,
        'announcement_type' => 'info',
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
        return Cache::rememberForever(self::CACHE_KEY, fn () => [
            ...self::DEFAULTS,
            ...static::query()->pluck('value', 'key')->all(),
        ]);
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
