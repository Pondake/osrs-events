<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per participant per metric event: the stored result of a
     * hiscores lookup, not a live one.
     *
     * The shape is Wise Old Man's `deltas` shape on purpose — start, end,
     * gained — because that is what their API hands back and the closer this
     * stays to the source, the less translation there is to get wrong. Credit
     * to Wise Old Man for the model; see WiseOldManService.
     *
     * Why store `gained` rather than compute `end - start` on read: it is what
     * the standings are ordered by, and an indexed column sorts in the
     * database instead of in PHP after fetching every row. It also stays
     * correct when start is still null (nobody has a baseline yet) instead of
     * ordering everyone at zero.
     *
     * Why `username` is duplicated off users.osrs_username: a rename mid-event
     * would otherwise silently re-point a row at a different account's gains.
     * This records which name the numbers actually came from.
     */
    public function up(): void
    {
        Schema::create('event_standings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('event_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('username', 12);

            // Nullable until the first successful sync. Null is "we have not
            // looked yet", which the UI shows differently from a real zero —
            // a participant who genuinely gained nothing.
            $table->unsignedBigInteger('start_value')->nullable();
            $table->unsignedBigInteger('end_value')->nullable();
            $table->unsignedBigInteger('gained')->default(0);

            // Distinguishes "synced, no gains" from "never synced", and gives
            // the sync command something to order by so the least recently
            // updated participant is refreshed first under the rate limit.
            $table->timestamp('synced_at')->nullable();
            $table->string('sync_error')->nullable();

            $table->timestamps();

            // One standing per participant per event — the sync upserts
            // against this, so it is what makes repeated runs idempotent.
            $table->unique(['event_id', 'user_id']);
            $table->index(['event_id', 'gained']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_standings');
    }
};
