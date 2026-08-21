<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which shapes count as a line on this card.
 *
 * "First line wins" was doing three jobs at once — rows, columns and both
 * diagonals — with no way to say a card is rows-only, or that the diagonals
 * are the whole point. Clans run both, and on a 3x3 the difference decides
 * how long the event lasts.
 *
 * A JSON list rather than three boolean columns: the set may grow (corners,
 * the four-corners-plus-centre "X", a full border are all real bingo
 * variants), and adding one should be a value rather than a migration.
 *
 * Defaults to all three, which is exactly what every existing card already
 * behaved as — so no card changes meaning when this lands.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bingo_cards', function (Blueprint $table) {
            $table->json('win_lines')->nullable();
        });

        Schema::getConnection()->table('bingo_cards')->update([
            'win_lines' => json_encode(['ROW', 'COLUMN', 'DIAGONAL']),
        ]);
    }

    public function down(): void
    {
        Schema::table('bingo_cards', function (Blueprint $table) {
            $table->dropColumn('win_lines');
        });
    }
};
