<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Boss kills the RuneLite plugin reported during a drop race, one row per
     * distinct kill count. The number of them is what the plugin has seen,
     * which is at most the truth: a kill made with the plugin off is not
     * there. The standings take the higher of this and Wise Old Man's number.
     */
    public function up(): void
    {
        Schema::table('event_standings', function (Blueprint $table) {
            $table->unsignedInteger('live_gained')->default(0)->after('gained');
        });

        Schema::create('event_standing_kills', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('event_standing_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('kill_count');
            $table->foreignUuid('plugin_completion_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->unique(['event_standing_id', 'kill_count']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_standing_kills');
        Schema::table('event_standings', fn (Blueprint $table) => $table->dropColumn('live_gained'));
    }
};
