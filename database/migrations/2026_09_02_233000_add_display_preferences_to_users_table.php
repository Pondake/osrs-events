<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-account display choices, starting with the board's movement animation.
 *
 * Its own column rather than a corner of `notification_preferences`: that one
 * is a whitelist against NotificationCategory::ALL and its validator rejects
 * anything not in that catalogue, which is exactly the discipline that makes
 * it the wrong place for a setting about drawing.
 *
 * Null means "never chosen", which is not the same as "off" — every key falls
 * back to its own default, so adding a setting later does not silently switch
 * it off for everyone who saved this list before it existed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('display_preferences')->nullable()->after('notification_preferences');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('display_preferences');
        });
    }
};
