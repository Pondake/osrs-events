<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * When we last confirmed this account exists on Wise Old Man.
     *
     * A timestamp rather than a boolean because "verified" is a claim with an
     * age: an account can be renamed or archived after the fact, and a bare
     * true would keep asserting something we checked once, months ago.
     *
     * Null carries two different situations on purpose — never checked, and
     * checked but not found. Both mean the same thing to the app (we cannot
     * confirm this player, so keep saying so), and splitting them into two
     * columns would add a state to handle for no behaviour that differs.
     *
     * Cleared whenever osrs_username changes: the confirmation belongs to the
     * name it was made about, not to the account.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('osrs_verified_at')->nullable()->after('osrs_username');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('osrs_verified_at');
        });
    }
};
