<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Two things a host had no way to say.
     *
     * `pause_reason` — "Paused" answers "can I still claim this?" and nothing
     * else. The question the clan actually asks in Discord is *why*, and for
     * how long, and only the host can answer it. Short on purpose: this is a
     * line on a banner and a line in an email, not a change log.
     *
     * `discord_webhook_url` — the honest fix for the notification gap. Discord
     * login never asks for an email address, so about half of any clan cannot
     * be emailed at all; but every one of these events is organised in a
     * Discord channel that already exists. One incoming-webhook URL per event
     * reaches everybody, including the people the mail cannot.
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('pause_reason', 200)->nullable()->after('paused_at');
            $table->string('discord_webhook_url', 500)->nullable()->after('pause_reason');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['pause_reason', 'discord_webhook_url']);
        });
    }
};
