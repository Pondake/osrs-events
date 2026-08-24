<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * When the numbers on a race stopped describing the event they are on.
     *
     * A standing is a measurement over a window: this metric, between these
     * two dates. Move either date or change the metric and every row is still
     * displayed, still ranked, and no longer true — measured against a window
     * that no longer exists. Nothing said so, because nothing could: the rows
     * carry `synced_at`, which answers "when was this read" and not "was it
     * read about the same question".
     *
     * Set when the window changes, cleared when a sync catches up. Deliberately
     * NOT an automatic re-sync on save: a forty-entrant race is forty outbound
     * requests to somebody else's public API, which is not something a form
     * submit should decide to spend on its own.
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->timestamp('standings_stale_since')->nullable()->after('discord_webhook_url');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('standings_stale_since');
        });
    }
};
