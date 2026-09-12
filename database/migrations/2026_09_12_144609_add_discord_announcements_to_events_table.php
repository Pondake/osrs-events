<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which announcements this event sends to its Discord channel.
 *
 * Null is "nobody has chosen", and resolves to AnnouncementTrigger's defaults
 * — which are exactly what already fired before this column existed, so no
 * channel goes quieter because of it. An empty array is a host who ticked
 * everything off, and that is a different answer from never having looked.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->json('discord_announcements')->nullable()->after('discord_webhook_failed_at');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('discord_announcements');
        });
    }
};
