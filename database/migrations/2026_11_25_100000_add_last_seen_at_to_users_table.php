<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_seen_at')->nullable()->index();
        });

        // Start from the sessions that already exist, so the column is not
        // empty for everyone who was active before it was added.
        DB::table('sessions')
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->select('user_id', DB::raw('MAX(last_activity) as last_activity'))
            ->get()
            ->each(fn ($row) => DB::table('users')
                ->where('id', $row->user_id)
                ->update(['last_seen_at' => date('Y-m-d H:i:s', (int) $row->last_activity)]));
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['last_seen_at']);
            $table->dropColumn('last_seen_at');
        });
    }
};
