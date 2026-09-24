<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The finishes a report caused — a bingo card won, a last tile
        // reached. Stored beside claims and progress so a retry of the same
        // client_event_id answers the same thing.
        Schema::table('plugin_completions', function (Blueprint $table) {
            $table->json('finishes')->nullable()->after('progress');
        });
    }

    public function down(): void
    {
        Schema::table('plugin_completions', fn (Blueprint $table) => $table->dropColumn('finishes'));
    }
};
