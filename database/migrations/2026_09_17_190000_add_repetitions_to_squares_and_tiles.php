<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Do this N times."
 *
 * The second counting mode, next to min_quantity. A Tempoross square set to 3
 * meant "kill it three times" and the app promised "one drop of 3 or more",
 * which is a different thing entirely — one report that is big enough, versus
 * N separate qualifying reports. They combine: "3 drops of at least 20 each".
 *
 * Not nullable, same reasoning as min_quantity: 1 already means "claim it on
 * the first one", and a second spelling of that is a branch every reader has
 * to remember.
 *
 * `target_progress` is where the running total lives. Deriving it from
 * plugin_completions was the alternative and it cannot work: a report that
 * does not yet claim anything is never linked to the square it was counted
 * against, so nothing says which of a player's reports belong to which
 * target. One row per counted report instead, with a `dedupe_key` doing the
 * distinctness the spec asks for — "kc:<kill count>" where the report carried
 * one, "report:<id>" where it did not — so the unique index *is* the counting
 * rule: a replayed kill 204 collides and changes nothing, two plain item
 * drops do not.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bingo_squares', function (Blueprint $table) {
            $table->unsignedInteger('required_count')->default(1)->after('min_quantity');
        });

        Schema::table('tiles', function (Blueprint $table) {
            $table->unsignedInteger('required_count')->default(1)->after('min_quantity');
        });

        Schema::create('target_progress', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // The square or tile this counts toward. Not a foreign key to
            // either table — one column cannot constrain two — but the id is
            // a uuid, so kind is about reading it, not about collisions.
            $table->string('kind', 16);
            $table->uuid('target_id');
            // "team:<id>", "user:<id>" or "board:<id>". The competitor split
            // BingoService::competitorFor() makes, plus the player board a
            // tile is claimed against, in one column.
            $table->string('competitor_key', 64);
            $table->string('dedupe_key', 64);
            $table->foreignUuid('plugin_completion_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->unique(['kind', 'target_id', 'competitor_key', 'dedupe_key'], 'target_progress_counted_once');
            $table->index(['kind', 'target_id', 'competitor_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('target_progress');
        Schema::table('bingo_squares', fn (Blueprint $table) => $table->dropColumn('required_count'));
        Schema::table('tiles', fn (Blueprint $table) => $table->dropColumn('required_count'));
    }
};
