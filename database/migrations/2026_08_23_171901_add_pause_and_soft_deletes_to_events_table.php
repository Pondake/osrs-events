<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Two ways to stop an event, because hosts asked for both and they are
     * not the same thing.
     *
     * `paused_at` is the reversible one: the event stays where it is, stays
     * readable, and stops accepting play — no rolls, no claims, no new
     * entries. A timestamp rather than a boolean so the page can say how long
     * it has been on hold, which is the first thing anyone asks.
     *
     * `deleted_at` is the other one. Deleting used to be `$event->delete()`
     * against a schema whose every child table cascades — board, tiles,
     * player boards, completed tiles, standings, participants, invites, all
     * of it, with no way back. That is a lot of somebody else's evening to
     * hang on one misclick in a modal. Soft-deleting keeps the rows and takes
     * the event out of every list and every route (Laravel's global scope
     * does that for free), so an admin can put it back.
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->timestamp('paused_at')->nullable()->after('end_date');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('paused_at');
            $table->dropSoftDeletes();
        });
    }
};
