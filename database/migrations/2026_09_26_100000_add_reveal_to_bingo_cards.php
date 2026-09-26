<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reveal: squares start hidden and are drawn one at a time, by the host or on
 * a timer, optionally stopping after a set number.
 *
 * A square is visible once it has a `revealed_at`. Null on a card without
 * reveal means nothing: every square there is visible.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bingo_cards', function (Blueprint $table) {
            $table->boolean('reveal')->default(false)->after('lockout');
            $table->unsignedSmallInteger('reveal_limit')->nullable()->after('reveal');
            $table->unsignedSmallInteger('reveal_every_minutes')->nullable()->after('reveal_limit');
        });

        Schema::table('bingo_squares', function (Blueprint $table) {
            $table->timestamp('revealed_at')->nullable()->after('is_wildcard');
        });
    }

    public function down(): void
    {
        Schema::table('bingo_squares', function (Blueprint $table) {
            $table->dropColumn('revealed_at');
        });

        Schema::table('bingo_cards', function (Blueprint $table) {
            $table->dropColumn(['reveal', 'reveal_limit', 'reveal_every_minutes']);
        });
    }
};
