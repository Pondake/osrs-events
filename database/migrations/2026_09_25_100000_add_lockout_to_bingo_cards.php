<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Lockout: on a team card, the first team to have a square approved keeps it.
 *
 * `claimed_at` is when the claim was made, which is not always when the row
 * was written: a RuneLite report carries the moment it happened in game, and
 * a client that was offline for a minute still got there first. It is what
 * the line for a locked square is ordered by.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bingo_cards', function (Blueprint $table) {
            $table->boolean('lockout')->default(false)->after('trust_runelite_completions');
        });

        Schema::table('bingo_completions', function (Blueprint $table) {
            $table->timestamp('claimed_at')->nullable()->after('status');
        });

        DB::table('bingo_completions')->whereNull('claimed_at')->update(['claimed_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('bingo_completions', function (Blueprint $table) {
            $table->dropColumn('claimed_at');
        });

        Schema::table('bingo_cards', function (Blueprint $table) {
            $table->dropColumn('lockout');
        });
    }
};
