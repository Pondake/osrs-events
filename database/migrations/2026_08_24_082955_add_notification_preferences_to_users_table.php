<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which categories a person wants, and whether they want any at all.
 *
 * Two columns rather than one, because they answer different questions and
 * the second one is the one that is easy to miss:
 *
 *  - `notification_preferences` is a sparse map of category => bool. Sparse on
 *    purpose: a category absent from it falls back to its declared default, so
 *    adding a tenth category next year does not need a backfill, and a default
 *    can be changed for everyone who never expressed an opinion.
 *  - `push_opted_out_at` is the explicit off switch. Unsubscribing drops the
 *    browser's subscription but leaves the OS permission granted — which is
 *    exactly the state auto-subscribe reads as "granted, subscribe silently".
 *    Without a stored opt-out, turning notifications off turns them straight
 *    back on at the next page load and the switch does nothing.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('notification_preferences')->nullable();
            $table->timestamp('push_opted_out_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['notification_preferences', 'push_opted_out_at']);
        });
    }
};
