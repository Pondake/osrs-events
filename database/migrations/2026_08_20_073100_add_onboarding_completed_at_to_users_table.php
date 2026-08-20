<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nullable timestamp rather than a boolean flag: "when did they finish
     * onboarding" answers "have they?" too, and keeps the door open for
     * re-running the flow after a big enough product change (compare the
     * timestamp against a released-at date) without another migration.
     *
     * Existing users are backfilled to now() in the same migration — they've
     * been using the app for weeks, so showing them a first-run tour on
     * their next visit would be nonsense.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('onboarding_completed_at')->nullable()->after('avatar_url');
        });

        DB::table('users')->update(['onboarding_completed_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('onboarding_completed_at');
        });
    }
};
