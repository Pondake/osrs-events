<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Links a RUNELITE claim back to the report it came from, so a host
        // reviewing it can see what the plugin actually saw. nullOnDelete:
        // losing the report is not a reason to lose the claim it created.
        Schema::table('bingo_completions', function (Blueprint $table) {
            $table->foreignUuid('plugin_completion_id')->nullable()->after('completed_via')
                ->constrained('plugin_completions')->nullOnDelete();
        });

        Schema::table('completed_tiles', function (Blueprint $table) {
            $table->foreignUuid('plugin_completion_id')->nullable()->after('completed_via')
                ->constrained('plugin_completions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bingo_completions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('plugin_completion_id');
        });

        Schema::table('completed_tiles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('plugin_completion_id');
        });
    }
};
