<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What happens when somebody actually finishes.
 *
 * Until now, nothing did. Completing the last tile of a Snakes & Ladders
 * board flipped a ref in the browser and showed a 🎉 modal; a refresh erased
 * it. The dice stayed live for a player standing on the final tile — every
 * roll clamped to where they already were, so the button burnt a daily roll
 * and moved nobody. Nobody else was told, and no order was recorded, so
 * second and third place were not merely unshown but unknowable after the
 * fact. Bingo was the same story with `hasWon` recomputed per request.
 *
 * Two columns, doing two different jobs:
 *
 * `finish_rule` is a **setting** — what the host wants to happen when the
 * first competitor gets home. CONTINUE keeps the board open until the end
 * date, which is how clan events actually run: people are in different time
 * zones and want their own completion, and finish order gives a full podium
 * for free. STOP is the race format — first one home ends it for everyone.
 *
 * `closed_at` is a **fact** — this event was stopped by something other than
 * its own calendar. Stamped by the first finish under STOP, and by a host
 * pressing End now. A column rather than a join through event_finishes on
 * every isEnded() call, and, more importantly, because it survives the host
 * changing their mind about the rule afterwards: an event that was closed
 * stays closed until somebody reopens it deliberately.
 *
 * Deliberately mirrors `paused_at`, down to being absent from `$fillable` —
 * stopping an event is its own action with its own audit entry, not a field
 * that can ride along in an ordinary settings save.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            // A string, not an enum: adding a third rule later should be data
            // and a validation rule, not a migration against a live table.
            // CONTINUE is the default because it is the forgiving one — a
            // host who wanted the race format will say so, and a host who
            // never thought about it has not accidentally cut their event
            // short the first time somebody gets lucky with the dice.
            $table->string('finish_rule')->default('CONTINUE')->after('end_date');
            $table->timestamp('closed_at')->nullable()->after('paused_at');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['finish_rule', 'closed_at']);
        });
    }
};
