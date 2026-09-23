<?php

use App\Support\RuneliteName;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Every OSRS character on an account: the main at position 0, alts after.
     *
     * `users.osrs_username` and its proof columns stay, as a copy of the main
     * that OsrsIdentityService keeps in step — dozens of places read "the
     * account's name" and all of them mean the main.
     *
     * Standings and claims remember the character they were made with, so an
     * alt removed mid-event leaves its history behind.
     */
    public function up(): void
    {
        Schema::create('osrs_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('username', 12);
            $table->unsignedTinyInteger('position')->default(0);
            $table->timestamp('osrs_verified_at')->nullable();
            $table->timestamp('osrs_proven_at')->nullable();
            $table->string('osrs_proven_via', 20)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'position']);
            $table->index('username');
        });

        $now = now();

        DB::table('users')->whereNotNull('osrs_username')->where('osrs_username', '!=', '')
            ->orderBy('id')
            ->chunk(500, function ($users) use ($now) {
                DB::table('osrs_accounts')->insert($users->map(fn ($user) => [
                    'id' => (string) Str::uuid(),
                    'user_id' => $user->id,
                    'username' => $user->osrs_username,
                    'position' => 0,
                    'osrs_verified_at' => $user->osrs_verified_at,
                    'osrs_proven_at' => $user->osrs_proven_at,
                    'osrs_proven_via' => $user->osrs_proven_via,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all());
            });

        Schema::table('events', function (Blueprint $table) {
            $table->boolean('allow_alts')->default(true);
        });

        Schema::table('event_standings', function (Blueprint $table) {
            $table->foreignUuid('osrs_account_id')->nullable()->constrained()->nullOnDelete();
        });

        // Compared the way the game does, so a standing stored before Wise Old
        // Man's canonical casing came back still finds its character. One
        // left unmatched is a rename the sync had not caught up with: it
        // keeps its numbers and a new row follows the new name.
        DB::table('event_standings')->whereNotNull('user_id')->orderBy('id')->chunk(500, function ($rows) {
            foreach ($rows as $row) {
                $account = DB::table('osrs_accounts')->where('user_id', $row->user_id)->get(['id', 'username'])
                    ->first(fn ($account) => RuneliteName::sameRsn($account->username, $row->username));

                if ($account !== null) {
                    DB::table('event_standings')->where('id', $row->id)->update(['osrs_account_id' => $account->id]);
                }
            }
        });

        Schema::table('event_standings', function (Blueprint $table) {
            $table->dropUnique(['event_id', 'user_id']);
            $table->index(['event_id', 'user_id']);
            $table->unique(['event_id', 'osrs_account_id']);
        });

        foreach (['bingo_completions', 'completed_tiles'] as $claims) {
            Schema::table($claims, function (Blueprint $table) {
                $table->string('rsn', 12)->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (['bingo_completions', 'completed_tiles'] as $claims) {
            Schema::table($claims, fn (Blueprint $table) => $table->dropColumn('rsn'));
        }

        // Back to one row per account per event: an alt's rows go, and of what
        // is left the main's row wins over one left behind by a rename.
        DB::table('event_standings')
            ->whereIn('osrs_account_id', DB::table('osrs_accounts')->where('position', '>', 0)->select('id'))
            ->delete();

        DB::table('event_standings')->whereNotNull('user_id')->get()
            ->groupBy(fn ($row) => $row->event_id.'|'.$row->user_id)
            ->each(fn ($rows) => $rows->sortByDesc(fn ($row) => $row->osrs_account_id !== null)->slice(1)
                ->each(fn ($row) => DB::table('event_standings')->where('id', $row->id)->delete()));

        Schema::table('event_standings', function (Blueprint $table) {
            $table->dropUnique(['event_id', 'osrs_account_id']);
            $table->dropConstrainedForeignId('osrs_account_id');
            $table->dropIndex(['event_id', 'user_id']);
            $table->unique(['event_id', 'user_id']);
        });

        Schema::table('events', fn (Blueprint $table) => $table->dropColumn('allow_alts'));

        Schema::dropIfExists('osrs_accounts');
    }
};
