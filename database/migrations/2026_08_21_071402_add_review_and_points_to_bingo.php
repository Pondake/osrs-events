<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What the first pass at bingo was missing, per docs/bingo-research.md.
 *
 * A completion was a boolean fact — a row existed or it didn't. Every tracker
 * clans actually use treats it as a **claim under review**: a player submits
 * proof, a host approves or rejects it, and the leaderboard counts only what
 * was approved. Without that, the honest description of the feature was "a
 * shared checklist", not a bingo tracker.
 *
 * Points are the other half. "Points per tile plus a bonus per completed line"
 * is how these are scored in practice, with harder tiles weighted higher —
 * counting squares treats a Zulrah pet and a bucket of sand as equal.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bingo_cards', function (Blueprint $table) {
            // Awarded on top of tile points for each completed row, column or
            // diagonal. Zero keeps the previous behaviour, so an existing
            // card scores exactly as it did until someone sets it.
            $table->unsignedSmallInteger('line_bonus')->default(0)->after('win_condition');

            // Whether a host has to approve claims at all. A small clan that
            // trusts everyone should not have to review every square, and
            // making that a setting is cheaper than making them wish it was.
            $table->boolean('requires_approval')->default(true)->after('line_bonus');
        });

        Schema::table('bingo_squares', function (Blueprint $table) {
            // Tile weighting. Default 1 so every existing square keeps
            // counting for exactly what it did when squares were the score.
            $table->unsignedSmallInteger('points')->default(1)->after('title_override');
        });

        Schema::table('bingo_completions', function (Blueprint $table) {
            // PENDING / APPROVED / REJECTED. Existing rows predate review
            // entirely and were counted as done, so they become APPROVED —
            // moving them to PENDING would silently un-score finished events.
            $table->string('status')->default('APPROVED')->after('user_id');

            // The proof itself. A URL rather than an upload: clans already
            // post screenshots to Discord or Imgur, and becoming an image
            // host to duplicate that is a whole other set of problems.
            $table->text('proof_url')->nullable()->after('status');
            $table->string('note')->nullable()->after('proof_url');

            $table->foreignUuid('reviewed_by')->nullable()->after('marked_by')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            // Why it was turned down. The single most useful thing to a
            // player whose claim was rejected, and the thing a bare status
            // cannot say.
            $table->string('review_note')->nullable()->after('reviewed_at');
        });
    }

    public function down(): void
    {
        Schema::table('bingo_cards', function (Blueprint $table) {
            $table->dropColumn(['line_bonus', 'requires_approval']);
        });

        Schema::table('bingo_squares', function (Blueprint $table) {
            $table->dropColumn('points');
        });

        Schema::table('bingo_completions', function (Blueprint $table) {
            // Dropped before the columns: SQLite cannot drop a column a
            // foreign key still names, which has bitten this repo before.
            $table->dropForeign(['reviewed_by']);
            $table->dropColumn(['status', 'proof_url', 'note', 'reviewed_by', 'reviewed_at', 'review_note']);
        });
    }
};
