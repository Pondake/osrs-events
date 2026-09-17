<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Nothing sensitive: no chat, no other players, no exact coordinates
        // (region only) — see docs/runelite-plugin.md and the validation in
        // RunelitePluginController.
        Schema::table('plugin_completions', function (Blueprint $table) {
            $table->json('context')->nullable()->after('occurred_at');
        });
    }

    public function down(): void
    {
        Schema::table('plugin_completions', fn (Blueprint $table) => $table->dropColumn('context'));
    }
};
