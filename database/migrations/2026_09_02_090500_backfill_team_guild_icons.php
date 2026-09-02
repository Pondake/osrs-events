<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Fill the new column for teams that were linked before it existed.
 *
 * Without this the fallback only appears once someone re-saves a team, so
 * every team already on the site would keep its empty box for no reason a
 * reader could see. The hash comes from `user_guilds`, which is where the
 * write path reads it from too — any row for that guild will do, since the
 * icon is a property of the server and not of the member who synced it.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('teams')
            ->whereNotNull('guild_id')
            ->whereNull('guild_icon')
            ->orderBy('id')
            ->each(function ($team) {
                $hash = DB::table('user_guilds')
                    ->where('guild_id', $team->guild_id)
                    ->whereNotNull('guild_icon')
                    ->value('guild_icon');

                if ($hash !== null) {
                    DB::table('teams')->where('id', $team->id)->update(['guild_icon' => $hash]);
                }
            });
    }

    /**
     * Nothing to undo: the column itself goes with the migration that added
     * it, and there is no earlier state of these values to restore.
     */
    public function down(): void {}
};
