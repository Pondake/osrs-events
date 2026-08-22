<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * "I am taking part in this."
 *
 * Every event type recorded participation in whatever table it happened to
 * write to — a standing for a race, a player board for Snakes & Ladders, a
 * claim for bingo — which meant three different answers to one question, and
 * for bingo no answer at all until somebody claimed a square. Anything that
 * needed the plain list (who is in this, which events am I playing) had to
 * union three tables and guess.
 *
 * So joining becomes its own record, the same for every type. The play tables
 * keep holding progress; this one holds intent, which is the part that was
 * never written down.
 *
 * Existing participation is carried across below, because an event running
 * right now must not empty out when this ships.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_participants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('event_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            // One row per person per event. Joining twice is not a thing, and
            // the constraint is what lets join() be a plain firstOrCreate
            // rather than a read-then-write race.
            $table->unique(['event_id', 'user_id']);
        });

        $this->carryOverExistingPlayers();
    }

    public function down(): void
    {
        Schema::dropIfExists('event_participants');
    }

    /**
     * Everyone already playing counts as joined.
     *
     * Written through the query builder rather than the models: a migration
     * has to keep working when the models move on, and these three reads are
     * plain enough not to need them.
     */
    private function carryOverExistingPlayers(): void
    {
        $rows = collect()
            ->merge(DB::table('event_standings')->select('event_id', 'user_id')->get())
            ->merge(
                DB::table('player_boards')
                    ->join('boards', 'boards.id', '=', 'player_boards.board_id')
                    ->whereNotNull('player_boards.user_id')
                    ->select('boards.event_id', 'player_boards.user_id')
                    ->get()
            )
            ->merge(
                DB::table('bingo_completions')
                    ->join('bingo_squares', 'bingo_squares.id', '=', 'bingo_completions.bingo_square_id')
                    ->join('bingo_cards', 'bingo_cards.id', '=', 'bingo_squares.bingo_card_id')
                    ->whereNotNull('bingo_completions.user_id')
                    ->select('bingo_cards.event_id', 'bingo_completions.user_id')
                    ->get()
            )
            ->filter(fn ($row) => $row->event_id !== null && $row->user_id !== null)
            ->unique(fn ($row) => $row->event_id.':'.$row->user_id)
            ->map(fn ($row) => [
                'id' => (string) str()->uuid(),
                'event_id' => $row->event_id,
                'user_id' => $row->user_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        $rows->chunk(500)->each(fn ($chunk) => DB::table('event_participants')->insert($chunk->all()));
    }
};
