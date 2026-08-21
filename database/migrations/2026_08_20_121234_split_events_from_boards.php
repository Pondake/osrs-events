<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Splits the event out of the board.
     *
     * Snakes & Ladders was the whole product, so one row held both "what this
     * competition is" (title, dates, who may join) and "how the game works"
     * (grid size, dice limit). A Bingo event has the first half and a
     * completely different second half; a drop race has no grid at all. So
     * the event-level columns move to their own table and a board becomes the
     * Snakes & Ladders *payload* of an event.
     *
     * Which satellites move, and why:
     *   - tiles, player_boards STAY on the board. They are the game itself,
     *     and Bingo will bring its own equivalents.
     *   - board_authors, board_teams, board_invites, board_accesses MOVE to
     *     the event. Ownership, entry and team assignment are things you have
     *     before you have a grid — and a Bingo event with no board must still
     *     be able to invite people.
     *
     * **Each event reuses its board's uuid.** That is deliberate, not
     * laziness: every satellite's board_id value is then already a valid
     * event_id, so those columns are copied straight across rather than
     * remapped row by row, and every
     * /events/{uuid} URL already in the wild keeps resolving. The two ids
     * coincide only for rows migrated here; anything created later gets its
     * own. A shared value across two tables costs nothing, and the remapping
     * it avoids is where this migration would otherwise lose data.
     */
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->string('type')->default('SNAKES_LADDERS')->index();
            $table->text('description')->nullable();
            $table->enum('mode', ['SOLO', 'TEAM'])->default('SOLO');
            $table->enum('access_mode', ['OPEN', 'GUILD', 'INVITE'])->default('OPEN');
            $table->string('required_guild_id')->nullable();
            $table->boolean('is_listed')->default(true);
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->timestamps();
        });

        // One event per existing board, same id.
        DB::statement('
            INSERT INTO events (id, title, type, description, mode, access_mode, required_guild_id, is_listed, start_date, end_date, created_at, updated_at)
            SELECT id, title, type, description, mode, access_mode, required_guild_id, is_listed, start_date, end_date, created_at, updated_at
            FROM boards
        ');

        Schema::table('boards', function (Blueprint $table) {
            $table->uuid('event_id')->nullable()->after('id');
        });

        DB::statement('UPDATE boards SET event_id = id');

        Schema::table('boards', function (Blueprint $table) {
            // Only now that it's populated, since the column can't be
            // non-null and unpopulated at the same moment.
            $table->uuid('event_id')->nullable(false)->change();
            $table->foreign('event_id')->references('id')->on('events')->cascadeOnDelete();

            // SQLite refuses to drop a column an index still references, and
            // the type index was added by its own migration a few hours ago.
            $table->dropIndex('boards_type_index');

            $table->dropColumn([
                'title',
                'type',
                'description',
                'mode',
                'access_mode',
                'required_guild_id',
                'is_listed',
                'start_date',
                'end_date',
            ]);
        });

        // The four event-level satellites. Each carries a composite unique
        // index and a foreign key naming board_id, and both have to go before
        // the column can — SQLite refuses to drop a column that an index or a
        // key definition still mentions, and simply renaming the column would
        // leave the constraint pointing at boards.
        //
        // The unique index is recreated on event_id rather than dropped: "one
        // author per board" was always really "one author per event", and
        // losing it would let duplicates in.
        $satellites = [
            'board_authors' => ['second' => 'user_id'],
            'board_teams' => ['second' => 'team_id'],
            'board_invites' => ['second' => 'short_code'],
            'board_accesses' => ['second' => 'user_id'],
        ];

        foreach ($satellites as $tableName => $meta) {
            $second = $meta['second'];

            Schema::table($tableName, function (Blueprint $table) {
                $table->uuid('event_id')->nullable()->after('id');
            });

            DB::statement("UPDATE {$tableName} SET event_id = board_id");

            Schema::table($tableName, function (Blueprint $table) use ($tableName, $second) {
                // Foreign key first, index second. MySQL requires an index on
                // a foreign key's columns and refuses to drop the one the FK
                // is relying on ("Cannot drop index ...: needed in a foreign
                // key constraint"). Reversing these is invisible on SQLite and
                // PostgreSQL, which is why it shipped.
                $table->dropForeign(['board_id']);
                $table->dropUnique("{$tableName}_board_id_{$second}_unique");
            });

            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('board_id');
            });

            Schema::table($tableName, function (Blueprint $table) use ($second) {
                $table->uuid('event_id')->nullable(false)->change();
                $table->foreign('event_id')->references('id')->on('events')->cascadeOnDelete();
                $table->unique(['event_id', $second]);
            });
        }
    }

    public function down(): void
    {
        $satellites = [
            'board_authors' => 'user_id',
            'board_teams' => 'team_id',
            'board_invites' => 'short_code',
            'board_accesses' => 'user_id',
        ];

        foreach ($satellites as $tableName => $second) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->uuid('board_id')->nullable()->after('id');
            });

            DB::statement("UPDATE {$tableName} SET board_id = event_id");

            Schema::table($tableName, function (Blueprint $table) use ($second) {
                // Same ordering rule as up() — see the comment there.
                $table->dropForeign(['event_id']);
                $table->dropUnique(['event_id', $second]);
            });

            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('event_id');
            });

            Schema::table($tableName, function (Blueprint $table) use ($second) {
                $table->uuid('board_id')->nullable(false)->change();
                $table->foreign('board_id')->references('id')->on('boards')->cascadeOnDelete();
                $table->unique(['board_id', $second]);
            });
        }

        Schema::table('boards', function (Blueprint $table) {
            $table->string('title')->default('');
            $table->string('type')->default('SNAKES_LADDERS');
            $table->text('description')->nullable();
            $table->enum('mode', ['SOLO', 'TEAM'])->default('SOLO');
            $table->enum('access_mode', ['OPEN', 'GUILD', 'INVITE'])->default('OPEN');
            $table->string('required_guild_id')->nullable();
            $table->boolean('is_listed')->default(true);
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
        });

        DB::statement('
            UPDATE boards SET
                title = (SELECT title FROM events WHERE events.id = boards.event_id),
                type = (SELECT type FROM events WHERE events.id = boards.event_id),
                description = (SELECT description FROM events WHERE events.id = boards.event_id),
                mode = (SELECT mode FROM events WHERE events.id = boards.event_id),
                access_mode = (SELECT access_mode FROM events WHERE events.id = boards.event_id),
                required_guild_id = (SELECT required_guild_id FROM events WHERE events.id = boards.event_id),
                is_listed = (SELECT is_listed FROM events WHERE events.id = boards.event_id),
                start_date = (SELECT start_date FROM events WHERE events.id = boards.event_id),
                end_date = (SELECT end_date FROM events WHERE events.id = boards.event_id)
        ');

        Schema::table('boards', function (Blueprint $table) {
            // No dropForeign: SQLite can't drop a constraint in place, and
            // dropping the column takes its constraint with it anyway.
            $table->dropColumn('event_id');
        });

        Schema::dropIfExists('events');
    }
};
