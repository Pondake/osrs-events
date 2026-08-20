<?php

use App\Models\EventStanding;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One entry per OSRS account per event.
     *
     * `users.osrs_username` is deliberately not unique — two people claiming
     * the same name is a moderation question, and a global unique index would
     * let the first claimant permanently lock a name they may not own. Inside
     * a single race it is not a question at all: the same account cannot
     * compete against itself, and two rows for one name means one set of gains
     * rendered twice, which reads as a bug. Caught by exactly that happening.
     *
     * The (event_id, user_id) unique from the create migration stays — it is
     * what makes entering idempotent. This one is about the name.
     */
    public function up(): void
    {
        // A duplicate would make the index creation fail outright, so any
        // existing pair is collapsed to the row that has actually been synced
        // (falling back to the older one) before the constraint goes on.
        EventStanding::query()
            ->get()
            ->groupBy(fn ($row) => $row->event_id.'|'.strtolower($row->username))
            ->each(function ($rows) {
                if ($rows->count() < 2) {
                    return;
                }

                $keep = $rows->sortByDesc(fn ($row) => [$row->synced_at !== null, $row->created_at])->first();

                $rows->reject(fn ($row) => $row->id === $keep->id)->each->delete();
            });

        Schema::table('event_standings', function (Blueprint $table) {
            $table->unique(['event_id', 'username']);
        });
    }

    public function down(): void
    {
        Schema::table('event_standings', function (Blueprint $table) {
            $table->dropUnique(['event_id', 'username']);
        });
    }
};
