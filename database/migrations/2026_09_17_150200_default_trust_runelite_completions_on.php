<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Owner's call, 2026-09-17: new boards and bingo cards trust RuneLite
     * completions by default. Column default only, on purpose — existing
     * rows keep whatever a host already chose.
     */
    public function up(): void
    {
        Schema::table('boards', function (Blueprint $table) {
            $table->boolean('trust_runelite_completions')->default(true)->change();
        });

        Schema::table('bingo_cards', function (Blueprint $table) {
            $table->boolean('trust_runelite_completions')->default(true)->change();
        });
    }

    public function down(): void
    {
        Schema::table('boards', function (Blueprint $table) {
            $table->boolean('trust_runelite_completions')->default(false)->change();
        });

        Schema::table('bingo_cards', function (Blueprint $table) {
            $table->boolean('trust_runelite_completions')->default(false)->change();
        });
    }
};
