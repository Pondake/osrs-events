<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The OSRS account name, which is a different identity from the Discord
     * one we already store.
     *
     * XP gains are looked up on the hiscores by RSN — Wise Old Man's API keys
     * every player by username — so without this a user simply cannot be
     * tracked in a skill race, no matter how they logged in.
     *
     * Nullable and unconstrained-by-default because it only matters for
     * metric-based events: a Snakes & Ladders player never needs one, and
     * demanding it at signup would gate the whole app on a field most of it
     * has no use for.
     *
     * Not unique. Two accounts claiming the same RSN is a moderation problem,
     * not a data-integrity one, and a unique index here would let the first
     * claimant permanently lock a name they may not own.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('osrs_username', 12)->nullable()->after('nickname');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('osrs_username');
        });
    }
};
