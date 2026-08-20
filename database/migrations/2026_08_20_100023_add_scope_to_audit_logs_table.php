<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Scopes an entry to a team and to the Discord guild (clan) that team
     * belongs to, so the log can answer "what happened in this clan" and not
     * only "what happened to this user".
     *
     * Two pairs rather than one generic scope, because they're two different
     * questions that both need answering at once: a member being added is
     * scoped to a team, and that team's guild is the clan an admin would
     * filter by. Nesting them into a single scope column would force a join
     * back to `teams` to resolve the guild — and teams get deleted, which is
     * the same trap target_label exists to avoid.
     *
     * guild_id is Discord's snowflake, a string, not a local uuid — it comes
     * from Team.guild_id, which is itself denormalized from the OAuth sync.
     * Labels are captured at write time for the same reason as actor/target:
     * they have to outlive the row they describe.
     */
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->uuid('team_id')->nullable()->index();
            $table->string('team_label')->nullable();

            $table->string('guild_id')->nullable()->index();
            $table->string('guild_label')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropColumn(['team_id', 'team_label', 'guild_id', 'guild_label']);
        });
    }
};
