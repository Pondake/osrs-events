<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A suggestion waiting on a human, and the memory of one that was turned down.
 *
 * The scheduled check proposes rather than applies. The backlog set that rule
 * before either half was built and it still holds: an automatic import only
 * earns its place once the mapping has been right by hand for a while, and a
 * wrong icon appearing on a live event page is worse than a blank one.
 *
 * `dismissed_url` is what stops a rejected suggestion coming back every week.
 * It remembers the URL rather than a flag, so a genuinely better image for the
 * same boss can still be proposed later.
 *
 * A row can now exist with no `icon_url` at all — just a suggestion — which is
 * why that column became nullable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('boss_icons', function (Blueprint $table) {
            $table->text('suggested_url')->nullable()->after('icon_url');
            $table->text('dismissed_url')->nullable()->after('suggested_url');
        });

        // SQLite cannot alter a column in place, and this project runs SQLite
        // in tests and PostgreSQL in production — so the change goes through
        // the schema builder's own change(), which handles both.
        Schema::table('boss_icons', function (Blueprint $table) {
            $table->text('icon_url')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('boss_icons', function (Blueprint $table) {
            $table->dropColumn(['suggested_url', 'dismissed_url']);
        });
    }
};
