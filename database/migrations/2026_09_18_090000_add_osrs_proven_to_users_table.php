<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * When the RuneLite plugin last reported this account logged in as the
     * name it claims, and by which route.
     *
     * Deliberately not osrs_verified_at, which answers a different question:
     * that one says Wise Old Man has heard of the name, which anybody's name
     * passes. This one says somebody playing that character sent us a report
     * from a client signed in as it.
     *
     * What that is worth, exactly: it rules out a collision and a casual
     * squatter, because typing a name you do not play is no longer enough to
     * look proven. It does not rule out somebody calling the API by hand with
     * their own code and any name they like — the client tells us who it is
     * logged in as, and nothing signs that claim. Treat it as the strongest
     * thing we can cheaply ask for, not as identity.
     *
     * Cleared whenever osrs_username changes, on the same reasoning as
     * osrs_verified_at: the proof belongs to the name it was made about.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('osrs_proven_at')->nullable()->after('osrs_verified_at');
            $table->string('osrs_proven_via', 20)->nullable()->after('osrs_proven_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['osrs_proven_at', 'osrs_proven_via']);
        });
    }
};
