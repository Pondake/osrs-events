<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * What a skill event competes on — "one skill for a month", the shape
     * Wise Old Man calls a competition metric.
     *
     * Nullable, because it only means anything for event types that race on a
     * metric. A Snakes & Ladders event has no metric and never will, so a
     * default would be a lie rather than a convenience.
     *
     * The column stores Wise Old Man's own metric vocabulary (lowercase skill
     * names — their API returns `"metric": "smithing"`), deliberately: their
     * API is the intended source of the gains this ranks on, and translating
     * between two vocabularies at the boundary is how they drift apart.
     * Credit where due — see Event::SKILL_METRICS.
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('metric')->nullable()->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('metric');
        });
    }
};
