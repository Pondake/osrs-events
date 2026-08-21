<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Team management used to hang off one global role (TEAM_MANAGER), which
 * meant whoever created a team could do nothing with it afterwards while
 * anyone holding that role could manage every team on the site. Both halves
 * were wrong: the right to manage a team belongs to that team.
 *
 * Three roles, deliberately not more:
 *   OWNER   - created it. Only role that can delete the team or hand out
 *             MANAGER; exactly one per team.
 *   MANAGER - promoted by the owner. Rename the team, add/remove members.
 *   MEMBER  - in the team, manages nothing. The default for anyone added.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('team_members', function (Blueprint $table) {
            $table->string('role')->default('MEMBER');
        });

        // TeamController::store() has always inserted the creator as the
        // first member, so the oldest row per team is that creator. Ties on
        // created_at (the seeders insert a whole team in one statement) fall
        // back to id so the result is deterministic rather than whichever
        // row the engine happens to return first.
        DB::table('team_members')
            ->orderBy('team_id')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get(['id', 'team_id'])
            ->groupBy('team_id')
            ->each(fn ($rows) => DB::table('team_members')
                ->where('id', $rows->first()->id)
                ->update(['role' => 'OWNER']));
    }

    public function down(): void
    {
        Schema::table('team_members', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
