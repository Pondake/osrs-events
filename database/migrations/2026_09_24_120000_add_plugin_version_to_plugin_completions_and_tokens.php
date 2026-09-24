<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The build that sent it, from the X-Plugin-Version header. Null for a
        // client that predates the header.
        Schema::table('plugin_completions', function (Blueprint $table) {
            $table->string('plugin_version', 32)->nullable()->after('rsn');
        });

        Schema::table('plugin_tokens', function (Blueprint $table) {
            $table->string('last_plugin_version', 32)->nullable()->after('last_used_at');
        });
    }

    public function down(): void
    {
        Schema::table('plugin_completions', fn (Blueprint $table) => $table->dropColumn('plugin_version'));
        Schema::table('plugin_tokens', fn (Blueprint $table) => $table->dropColumn('last_plugin_version'));
    }
};
