<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A blueprint grows from a title into a whole event's shape.
 *
 * It started as autocomplete: a name, and optionally a type and a metric. The
 * ask now is to start an event FROM one — which means it has to carry the
 * settings a host would otherwise fill in by hand (grid size, win condition,
 * roll limit, whether claims are reviewed), and it has to be worth looking at
 * before you pick it rather than being one line in a dropdown.
 *
 * **`settings` is a JSON column, not thirty nullable ones.** The fields a
 * blueprint can carry are exactly the fields the create form has, and those
 * differ per event type — a bingo card has a win condition and no dice, a
 * board has a roll limit and no card. Spreading that across columns would put
 * every type's fields on every row and leave most of them null forever. The
 * shape is enforced where it is applied (EventBlueprint::APPLICABLE), not by
 * the table.
 *
 * **`created_by` and `guild_id` are here before anything filters on them.**
 * A host saving their own event as a template is the next step, and both
 * "whose is this" and "which clan is it for" are answers we will need. Null
 * on both means the seeded, global set — the formats that ship with the app
 * and belong to nobody.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_blueprints', function (Blueprint $table) {
            $table->json('settings')->nullable();

            // Nulled rather than cascaded on delete: a template outlives the
            // account that wrote it, the same way an audit entry does. Losing
            // the author is not a reason to lose the format.
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('guild_id')->nullable();

            $table->index('created_by');
            $table->index('guild_id');
        });
    }

    public function down(): void
    {
        Schema::table('event_blueprints', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
            $table->dropIndex(['guild_id']);
            $table->dropColumn(['settings', 'guild_id']);
        });
    }
};
