<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * A snake or a ladder carries no task, and now the rows say so too.
 *
 * Landing on one moves the player on within the same roll, so nobody is ever
 * standing on it to complete anything — a task there was unreachable by
 * construction. The editors used to offer the field anyway, and boards were
 * built with tasks on jump tiles that then drew an icon and a title next to
 * an arrow saying the tile sends you away.
 *
 * TileController::upsert() enforces it from here on. This is the rows that
 * already exist. Deliberately not reversible: the down() would have to
 * invent which task each tile used to point at, and there is no honest
 * answer to that — the data is being dropped precisely because it never
 * meant anything.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('tiles')
            ->whereIn('type', ['SNAKE', 'LADDER'])
            ->update(['task_id' => null, 'title_override' => null]);
    }

    public function down(): void
    {
        // Nothing to put back — see the note above.
    }
};
