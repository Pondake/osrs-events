<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * `default_event_duration_days` becomes `default_event_duration`.
 *
 * The setting stopped being a day count and became a duration — `10d`, `2w`,
 * `1m` — so that a month can mean a calendar month. The key changed with it,
 * because a key named `_days` holding `2w` is a lie the next reader has to
 * work out.
 *
 * Without this, an admin who had deliberately set 30 days would have found it
 * quietly back at the default. The old value is a day count, and a day count
 * is still a valid duration, so it carries across as-is.
 *
 * The old row is left where it is. It costs nothing, and it is the only
 * record of what the setting was if this turns out to have been wrong.
 */
return new class extends Migration
{
    public function up(): void
    {
        $old = DB::table('settings')->where('key', 'default_event_duration_days')->value('value');

        if ($old === null) {
            return;
        }

        // Only when nothing has been set the new way already — a re-run must
        // not undo a deliberate change made since.
        if (DB::table('settings')->where('key', 'default_event_duration')->exists()) {
            return;
        }

        $days = (int) json_decode((string) $old, true);

        if ($days < 1 || $days > 365) {
            return;
        }

        DB::table('settings')->insert([
            'key' => 'default_event_duration',
            'value' => json_encode((string) $days),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->forgetCache();
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'default_event_duration')->delete();

        $this->forgetCache();
    }

    /**
     * Settings are cached forever and only cleared by Setting::set(), which a
     * migration writing through the query builder never calls. Without this
     * the row lands and the app keeps serving the old value until something
     * else happens to clear it — which on a deploy means the carry-over looks
     * like it silently did nothing. Confirmed by reading it back and getting
     * the default.
     */
    private function forgetCache(): void
    {
        Cache::forget('settings.all');
    }
};
