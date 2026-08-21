<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bingo's payload, the third event type to bring its own.
 *
 * **Not reusing `boards` and `tiles`**, despite both being grids. A Snakes &
 * Ladders board has a dice limit, a per-player position, and tiles whose type
 * (snake/ladder) and target position mean nothing here; a bingo card has none
 * of that and needs something they lack — a completion that belongs to a
 * *team*, not to one player's walk across the board. Bolting bingo onto
 * PlayerBoard would mean a nullable position, a meaningless dice limit and a
 * completion table that means two different things depending on the parent's
 * type. Separate tables cost three migrations once; that ambiguity costs
 * every query afterwards.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bingo_cards', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('event_id')->constrained()->cascadeOnDelete();

            // Square grids only, stored as the side length rather than a size
            // enum: bingo cards are conventionally 5x5, and a plain integer
            // means adding 6x6 is data rather than a migration.
            $table->unsignedTinyInteger('size')->default(5);

            // How a card is won. LINE means any full row, column or diagonal;
            // FULL_HOUSE means every square. Stored because it decides what
            // "finished" means, and an event that changes its mind mid-run
            // would invalidate results already shown.
            $table->string('win_condition')->default('LINE');

            $table->timestamps();

            // One card per event — the event IS the bingo, the card is its
            // payload, exactly as Board is for Snakes & Ladders.
            $table->unique('event_id');
        });

        Schema::create('bingo_squares', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('bingo_card_id')->constrained()->cascadeOnDelete();

            // Row-major, zero-based. Same convention as `tiles.position`, so
            // anyone reading both does not have to hold two mental models.
            $table->unsignedSmallInteger('position');

            // A square points at a Task the same way a tile does, with an
            // override for one-off wording. nullOnDelete, not cascade:
            // deleting a task must not silently delete squares out of a
            // running card.
            $table->foreignUuid('task_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title_override')->nullable();

            $table->timestamps();

            $table->unique(['bingo_card_id', 'position']);
        });

        Schema::create('bingo_completions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('bingo_square_id')->constrained()->cascadeOnDelete();

            // The competitor. A SOLO event scores per user and a TEAM event
            // per team, so exactly one of these is set — enforced by the two
            // unique indexes below rather than by a nullable-pair convention
            // nobody remembers.
            $table->foreignUuid('team_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained()->cascadeOnDelete();

            // Who ticked it, which is not the same as who it counts for: on a
            // team card any member can mark a square, and knowing which one
            // did is what makes a disputed square answerable.
            $table->foreignUuid('marked_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // A square counts once per competitor. Two partial uniques rather
            // than one over both columns, because SQL treats NULLs as
            // distinct and a single index would let the same team tick a
            // square twice.
            $table->unique(['bingo_square_id', 'team_id']);
            $table->unique(['bingo_square_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bingo_completions');
        Schema::dropIfExists('bingo_squares');
        Schema::dropIfExists('bingo_cards');
    }
};
