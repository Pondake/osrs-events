<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The linked server's icon hash, so a team without its own icon can borrow it.
 *
 * It has to live on the team, not be read from `user_guilds` at render time:
 * that table only holds the servers of people who have logged in, so the
 * fallback would show up for a clan mate and be blank for a stranger looking
 * at the same public event. A team's avatar must not depend on who is looking.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->string('guild_icon')->nullable()->after('guild_name');
        });
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn('guild_icon');
        });
    }
};
