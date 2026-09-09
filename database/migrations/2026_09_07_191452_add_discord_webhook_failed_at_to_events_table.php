<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * When this event's Discord webhook last refused a post.
 *
 * A revoked webhook answers 404 and nothing else happens: the pause still
 * pauses, the request still succeeds, and `DiscordAnnouncer` swallows it by
 * design — see the note there about failure never being the user's problem.
 * Measured against a real server on 2026-09-07, that turned out to mean
 * nobody ever finds out: no log line, no word to the host, and an event that
 * quietly stops announcing for the rest of its run.
 *
 * A timestamp rather than a boolean, because "since when" is the difference
 * between "I broke it just now" and "this has been dead for a fortnight",
 * and because it is what lets the notice say something concrete. Null means
 * the webhook is fine or was never configured — the state a row starts in
 * and returns to the moment a post lands again.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->timestamp('discord_webhook_failed_at')->nullable()->after('discord_webhook_url');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('discord_webhook_failed_at');
        });
    }
};
