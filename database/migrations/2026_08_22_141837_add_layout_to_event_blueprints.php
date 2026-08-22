<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A blueprint grows again: from the shape of an event to the board itself.
 *
 * `settings` carries what the create form would fill in — grid size, win
 * condition, who may join. `layout` carries what a host would then spend an
 * evening on: which task sits on which tile, where the snakes and ladders
 * run, which square is the wildcard. Reusing a format without that is
 * reusing the easy half.
 *
 * **A separate column, not another key in `settings`.** Settings is a small
 * flat map that the create form applies field by field and an allow-list
 * vets; a layout is a list of up to 81 rows applied by a different code path
 * after the event exists. Same JSON, different lifetime and different rules.
 *
 * **A snapshot, not a reference** — decided 2026-08-22. Each entry carries
 * the task id AND the title it had at the time: the id keeps the tile linked
 * to the shared Task where that still exists, and the title survives the
 * Task being renamed or deleted, so a year-old blueprint still describes
 * itself.
 *
 * **Tied to a grid size**, which is the one thing a caller has to check: a
 * 5x5 layout cannot be poured into a 7x7. The size lives in `settings`, and
 * the picker filters on it rather than failing at apply time.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_blueprints', function (Blueprint $table) {
            $table->json('layout')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('event_blueprints', function (Blueprint $table) {
            $table->dropColumn('layout');
        });
    }
};
