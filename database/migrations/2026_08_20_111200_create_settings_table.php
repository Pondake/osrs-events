<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Runtime-editable site settings — the handful of things an admin
     * plausibly wants to change without a deploy.
     *
     * Key/value with a JSON `value` rather than a column per setting: adding
     * the next one shouldn't need a migration, and the set is small enough
     * that querying individual keys is never the access pattern (the whole
     * table is read at once and cached — see App\Models\Setting).
     *
     * No `type` column: the JSON cast round-trips bools, ints and strings
     * faithfully on its own, and a separate type column would be a second
     * source of truth to keep in step.
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->json('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
