<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Who got home, and when. The podium, stored once for every event type.
 *
 * **Not a `finished_at` column on `player_boards`.** That is where it looks
 * like it belongs right up until bingo asks the same question: a card has no
 * per-competitor row at all, only completions per square, and the phase 7
 * types will not have one either. One table keyed on the event, holding
 * whichever competitor the event's mode makes real, means the rule ("first
 * finish may close the event") lives in one service instead of once per type
 * — exactly the reasoning the bingo tables migration used to argue the other
 * way for the grid itself.
 *
 * **Rank is not stored.** It is the order of `finished_at`, derived on read.
 * A stored rank is a number that goes stale the moment anything else touches
 * the table — the same conclusion RaceRankNotifier reached about leaderboard
 * positions, for the same reason.
 *
 * The timestamp is when the finish became *true*, which on a board that
 * requires approval is when the host approved the last claim, not when the
 * player submitted it. That is deliberate and it is the honest reading: on a
 * reviewed board the host's verdict is what makes a claim count, so it is
 * also what decides who was first.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_finishes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('event_id')->constrained()->cascadeOnDelete();

            // Exactly one of the two is set, decided by the event's mode —
            // same shape and same reasoning as bingo_completions, so anyone
            // reading both does not have to hold two mental models.
            $table->foreignUuid('team_id')->nullable()->constrained()->cascadeOnDelete();

            // Nullable, and NOT cascading, unlike the team: a podium has to
            // outlive the account that stood on it. An event that was won in
            // July still has a winner after that account closes — the row
            // stays and only the link is cut, which on screen reads as a
            // deleted player rather than as an absence. Same rule, same
            // reason, as the allow_history_to_outlive_an_account migration;
            // AccountDeletionService is where accounts are actually unpicked,
            // but this one is a fresh table so it can simply say nullOnDelete
            // rather than needing a second migration to change its mind.
            $table->foreignUuid('user_id')->nullable()->constrained()->nullOnDelete();

            // The display name at the time, so a podium survives the account
            // that earned it. Same trick event_standings plays with
            // `username`: without it a deleted account leaves a rank with
            // nothing to print next to it.
            $table->string('display_name')->nullable();

            $table->timestamp('finished_at')->useCurrent();
            $table->timestamps();

            // A competitor finishes an event once. Two partial-ish uniques
            // rather than one over a nullable pair, matching
            // bingo_completions: on PostgreSQL a NULL never equals a NULL, so
            // a single composite index over (event, team, user) would happily
            // accept the same solo player twice.
            $table->unique(['event_id', 'user_id']);
            $table->unique(['event_id', 'team_id']);

            // The read this table exists for: the podium, in order.
            $table->index(['event_id', 'finished_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_finishes');
    }
};
