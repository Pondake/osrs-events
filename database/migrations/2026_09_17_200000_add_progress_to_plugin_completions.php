<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // What a report moved forward without claiming: a "do this N times"
        // square that is now 2 of 5. Stored beside the claims it made so a
        // retry of the same client_event_id answers the same thing.
        Schema::table('plugin_completions', function (Blueprint $table) {
            $table->json('progress')->nullable()->after('claims');
        });
    }

    public function down(): void
    {
        Schema::table('plugin_completions', fn (Blueprint $table) => $table->dropColumn('progress'));
    }
};
