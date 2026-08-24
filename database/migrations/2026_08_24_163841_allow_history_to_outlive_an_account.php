<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lets a finished event keep its record after the person in it is gone.
 *
 * Deleting an account should not quietly rewrite a leaderboard. A race that
 * ended in July had a winner, and it still had one after that account closed —
 * so the row stays, its `username` intact, and only the link to the account is
 * cut. On screen that reads as a deleted player rather than an absence, which
 * is the honest version of what happened.
 *
 * **Only the columns change here, not the foreign keys.** The obvious
 * alternative was to switch four constraints to `nullOnDelete` and let the
 * database do it, and it was rejected twice over: changing a foreign key's
 * action means dropping and recreating the constraint, which SQLite cannot do
 * without a table rebuild, and — more importantly — it would scatter the rule
 * across four migrations where nobody deciding "what happens when an account
 * closes" would ever read it. AccountDeletionService nulls these explicitly
 * instead, in one readable pass, and the tests can watch it do so.
 *
 * `bingo_completions.user_id` is already nullable and is not touched.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_standings', function (Blueprint $table) {
            // The row keeps `username` — the OSRS name the race was scored on
            // — which is what a leaderboard actually displays.
            $table->uuid('user_id')->nullable()->change();
        });

        Schema::table('player_boards', function (Blueprint $table) {
            $table->uuid('user_id')->nullable()->change();
        });

        Schema::table('board_invites', function (Blueprint $table) {
            // This one is a bug fix as much as a feature. The column was NOT
            // NULL with a plain `constrained('users')`, which defaults to
            // RESTRICT — so deleting any account that had ever created an
            // invite link failed outright on a foreign key violation. Since
            // an admin deleting a user was the only deletion route that
            // existed, account deletion has been broken for every host since
            // invites shipped. Confirmed against the live schema before
            // writing this.
            $table->uuid('created_by')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Deliberately one-way. Rows nulled while this was in force cannot be
        // re-pointed at accounts that no longer exist, so restoring NOT NULL
        // would fail on exactly the data the migration was for.
    }
};
