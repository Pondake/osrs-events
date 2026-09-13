<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('boards', function (Blueprint $table) {
            $table->boolean('trust_runelite_completions')->default(false)->after('requires_approval');
        });

        Schema::table('bingo_cards', function (Blueprint $table) {
            $table->boolean('trust_runelite_completions')->default(false)->after('requires_approval');
        });

        Schema::table('bingo_completions', function (Blueprint $table) {
            $table->enum('completed_via', ['MANUAL', 'RUNELITE'])->default('MANUAL')->after('marked_by');
        });
    }

    public function down(): void
    {
        Schema::table('boards', fn (Blueprint $table) => $table->dropColumn('trust_runelite_completions'));
        Schema::table('bingo_cards', fn (Blueprint $table) => $table->dropColumn('trust_runelite_completions'));
        Schema::table('bingo_completions', fn (Blueprint $table) => $table->dropColumn('completed_via'));
    }
};
