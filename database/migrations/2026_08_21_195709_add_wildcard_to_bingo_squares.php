<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A free square — bingo's "wildcard", counted as done for everybody without
 * anyone claiming it.
 *
 * Conventionally the middle of the card, because a free centre sits on both
 * diagonals and the middle row and column, so it is worth four of the twelve
 * lines on a 5x5. That convention is why this cannot just be "a square
 * somebody pre-approved": it has to count for every competitor at once, and
 * a completion row belongs to exactly one of them.
 *
 * Any square can be one — a host running a themed card may want two, or want
 * theirs off-centre. The app takes no view; it only makes it possible.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bingo_squares', function (Blueprint $table) {
            $table->boolean('is_wildcard')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('bingo_squares', function (Blueprint $table) {
            $table->dropColumn('is_wildcard');
        });
    }
};
