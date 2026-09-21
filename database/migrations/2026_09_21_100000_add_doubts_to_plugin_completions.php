<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Why the server was not sure about a report: a list of reason codes,
        // null when nothing looked off. Kept apart from `context`, which is
        // only ever what the client said.
        Schema::table('plugin_completions', function (Blueprint $table) {
            $table->json('doubts')->nullable()->after('progress');
        });
    }

    public function down(): void
    {
        Schema::table('plugin_completions', fn (Blueprint $table) => $table->dropColumn('doubts'));
    }
};
