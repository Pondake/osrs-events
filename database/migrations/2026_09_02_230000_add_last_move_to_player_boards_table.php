<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The last move a player made, so everybody watching can see it happen.
 *
 * The board animates a roll — walking the dice, then riding whatever snake or
 * ladder was waiting. Until now that only ran for the player who rolled,
 * because the move lived in their own session flash: to a second viewer the
 * piece simply appeared on the far tile. Reported from a two-browser test.
 *
 * The live stream already carries `current_position`, which is where a piece
 * ENDED. A move is the rest of that sentence — where it started and what
 * carried it — and it is a fact about the board rather than about a viewer,
 * so it belongs next to the position and can go out on the shared channel.
 *
 * `move_seq` is what makes it usable: two rolls can land on the same tile, and
 * a viewer needs to tell "a new move" from "the same state re-sent".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('player_boards', function (Blueprint $table) {
            $table->unsignedInteger('move_seq')->default(0)->after('current_position');
            $table->unsignedInteger('last_move_from')->nullable()->after('move_seq');
            $table->unsignedInteger('last_move_landed')->nullable()->after('last_move_from');
            // 'snake', 'ladder', or null for a plain walk.
            $table->string('last_move_jump', 16)->nullable()->after('last_move_landed');
        });
    }

    public function down(): void
    {
        Schema::table('player_boards', function (Blueprint $table) {
            $table->dropColumn(['move_seq', 'last_move_from', 'last_move_landed', 'last_move_jump']);
        });
    }
};
