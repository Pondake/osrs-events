<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The same trust problem bingo was built to solve, unsolved on the other
 * board type — see docs/backlog.md, "Snakes & Ladders task tiles have no
 * claim/approve flow". A Tile can carry exactly the same kind of challenge
 * as a bingo square, but PlayerBoardController::toggleTile() marked it done
 * with a plain self-toggle: no proof, no host review.
 *
 * Deliberately mirrors add_review_and_points_to_bingo's shape (status,
 * proof_url, note, reviewed_by/at, review_note) so the two claim flows read
 * as the same idea applied twice, not two different systems that happen to
 * look similar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('boards', function (Blueprint $table) {
            // Whether a host has to approve claims at all — same setting,
            // same default as bingo_cards.requires_approval. A clan that
            // trusts everyone should not have to review every tile.
            $table->boolean('requires_approval')->default(true)->after('dice_roll_limit');
        });

        Schema::table('completed_tiles', function (Blueprint $table) {
            // PENDING / APPROVED / REJECTED. Existing rows predate review
            // entirely and were counted as done, so they become APPROVED —
            // moving them to PENDING would silently strand every player
            // mid-board, unable to roll again until somebody reviewed a
            // claim they never knew they had to make.
            $table->string('status')->default('APPROVED')->after('completed_via');

            $table->text('proof_url')->nullable()->after('status');
            $table->string('note')->nullable()->after('proof_url');

            // Who actually clicked complete, not just whose board it is: on
            // a TEAM board any member can claim a tile, and knowing which
            // one did is what makes a disputed claim answerable — same
            // reasoning as bingo_completions.marked_by.
            $table->foreignUuid('marked_by')->nullable()->after('note')->constrained('users')->nullOnDelete();

            $table->foreignUuid('reviewed_by')->nullable()->after('marked_by')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            $table->string('review_note')->nullable()->after('reviewed_at');
        });
    }

    public function down(): void
    {
        Schema::table('boards', function (Blueprint $table) {
            $table->dropColumn('requires_approval');
        });

        Schema::table('completed_tiles', function (Blueprint $table) {
            // Dropped before the columns: SQLite cannot drop a column a
            // foreign key still names.
            $table->dropForeign(['marked_by']);
            $table->dropForeign(['reviewed_by']);
            $table->dropColumn(['status', 'proof_url', 'note', 'marked_by', 'reviewed_by', 'reviewed_at', 'review_note']);
        });
    }
};
