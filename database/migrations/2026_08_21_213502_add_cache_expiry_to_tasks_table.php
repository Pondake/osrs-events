<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * When a wiki-sourced task was last confirmed against the wiki.
 *
 * The task table stopped being a library people browse and became a cache of
 * the OSRS Wiki — the picker offers one search box over the wiki, and every
 * result it uses lands here. A cache needs an age: a page can be renamed or
 * get a new image, and a row that was right in March should not still be
 * showing March's title in September.
 *
 * Null for the hand-written seeded tasks ("Kill 50 cows"). Those have no
 * upstream to go stale against, and refreshing them would mean refreshing
 * them from nowhere.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->timestamp('wiki_synced_at')->nullable();
        });

        // Everything already imported was correct at the moment it was
        // imported; created_at is the closest honest answer to "when was
        // this last true", and it makes existing rows age out normally
        // rather than all refreshing at once on deploy.
        Schema::getConnection()
            ->table('tasks')
            ->whereNotNull('wiki_page_id')
            ->update(['wiki_synced_at' => Schema::getConnection()->raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('wiki_synced_at');
        });
    }
};
