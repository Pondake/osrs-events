<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Whether the world has been told about this finish yet.
     *
     * Ordering by submission time fixed the podium, but not what everybody
     * hears while the queue is still being worked through. Reported directly:
     * two teams claimed the last tile, the host approved the SECOND one
     * first, and every player was told that team had got home first — while
     * the claim that actually won was still sitting in the review queue,
     * unopened. The correction arrived a minute later, which is a minute of
     * the wrong clan celebrating.
     *
     * So an announcement now waits for the same condition the close does:
     * nobody earlier can still beat this finish. A stamp rather than a
     * boolean, because "when did this go out" is the question asked when
     * somebody says they never got it — and a null is the honest answer for
     * a finish whose place is not settled yet.
     */
    public function up(): void
    {
        Schema::table('event_finishes', function (Blueprint $table) {
            $table->timestamp('announced_at')->nullable()->after('finished_at');
        });

        // Everything that already exists was announced under the old rule.
        // Leaving these null would re-announce every finish on the next
        // review of any event that has one.
        \Illuminate\Support\Facades\DB::table('event_finishes')->update(['announced_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('event_finishes', function (Blueprint $table) {
            $table->dropColumn('announced_at');
        });
    }
};
