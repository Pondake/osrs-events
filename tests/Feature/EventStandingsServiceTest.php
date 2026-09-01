<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventStanding;
use App\Models\User;
use App\Services\EventStandingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Standings: who is in a race, how they are ranked, and what happens when a
 * lookup cannot answer.
 *
 * Several of these cover bugs that already shipped once — the duplicate-RSN
 * rename that took down the whole scheduled sync, and the unranked row that
 * was stealing a placing from people actually competing.
 */
class EventStandingsServiceTest extends TestCase
{
    use RefreshDatabase;

    private function standings(): EventStandingsService
    {
        return app(EventStandingsService::class);
    }

    private function race(array $attributes = []): Event
    {
        return Event::create([
            'title' => 'Skill of the Month — Mining',
            'type' => 'SKILL_RACE',
            'metric' => 'mining',
            'mode' => 'SOLO',
            'access_mode' => 'OPEN',
            'is_listed' => true,
            'start_date' => Carbon::now()->subWeek(),
            'end_date' => Carbon::now()->addWeek(),
            ...$attributes,
        ]);
    }

    private function fakeGains(int $gained): void
    {
        Http::fake(['api.wiseoldman.net/v2/players/*/gained*' => Http::response([
            'data' => ['skills' => ['mining' => ['experience' => [
                'gained' => $gained, 'start' => 1000, 'end' => 1000 + $gained,
            ]]]],
        ])]);
    }

    // ---------------------------------------------------------------- enter

    #[Test]
    public function entering_creates_a_standing_under_the_users_osrs_name(): void
    {
        $event = $this->race();
        $user = User::factory()->create(['osrs_username' => 'Pondake']);

        $standing = $this->standings()->enter($event, $user);

        $this->assertNotNull($standing);
        $this->assertSame('Pondake', $standing->username);
        $this->assertNull($standing->synced_at, 'a fresh entry has never been looked up');
    }

    /** A row with no name to look up is a permanent zero — worse than absent. */
    #[Test]
    public function a_user_without_an_osrs_name_cannot_enter(): void
    {
        $event = $this->race();
        $user = User::factory()->create(['osrs_username' => null]);

        $this->assertNull($this->standings()->enter($event, $user));
        $this->assertSame(0, EventStanding::count());
    }

    #[Test]
    public function entering_twice_is_idempotent(): void
    {
        $event = $this->race();
        $user = User::factory()->create(['osrs_username' => 'Pondake']);

        $first = $this->standings()->enter($event, $user);
        $second = $this->standings()->enter($event, $user);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, EventStanding::count());
    }

    /** The same account cannot compete against itself. */
    #[Test]
    public function two_accounts_cannot_enter_one_race_under_the_same_name(): void
    {
        $event = $this->race();
        $this->standings()->enter($event, User::factory()->create(['osrs_username' => 'Pondake']));

        $this->expectException(ValidationException::class);
        $this->standings()->enter($event, User::factory()->create(['osrs_username' => 'Pondake']));
    }

    /** Uniqueness is per race, not global — a name may enter many events. */
    #[Test]
    public function the_same_name_may_enter_two_different_races(): void
    {
        $user = User::factory()->create(['osrs_username' => 'Pondake']);

        $this->standings()->enter($this->race(), $user);
        $this->standings()->enter($this->race(['title' => 'Another race']), $user);

        $this->assertSame(2, EventStanding::count());
    }

    #[Test]
    public function leaving_removes_only_that_users_standing(): void
    {
        $event = $this->race();
        $mine = User::factory()->create(['osrs_username' => 'Pondake']);
        $theirs = User::factory()->create(['osrs_username' => 'Zezima']);
        $this->standings()->enter($event, $mine);
        $this->standings()->enter($event, $theirs);

        $this->standings()->leave($event, $mine);

        $this->assertSame(1, EventStanding::count());
        $this->assertSame('Zezima', EventStanding::first()->username);
    }

    // ------------------------------------------------------------- refresh

    #[Test]
    public function refreshing_stores_the_gains_and_marks_the_row_synced(): void
    {
        $this->fakeGains(2360640);
        $event = $this->race();
        $standing = $this->standings()->enter($event, User::factory()->create(['osrs_username' => 'Pondake']));

        $this->standings()->refresh($event, $standing);

        $standing->refresh();
        $this->assertSame(2360640, $standing->gained);
        $this->assertNull($standing->sync_error);
        $this->assertNotNull($standing->synced_at);
    }

    /** Reading gains proves the account exists, so the notice can stop. */
    #[Test]
    public function a_successful_refresh_verifies_the_account(): void
    {
        $this->fakeGains(100);
        $event = $this->race();
        $user = User::factory()->create(['osrs_username' => 'Pondake', 'osrs_verified_at' => null]);

        $this->standings()->refresh($event, $this->standings()->enter($event, $user));

        $this->assertNotNull($user->fresh()->osrs_verified_at);
    }

    #[Test]
    public function an_untracked_player_is_flagged_rather_than_scored_zero(): void
    {
        Http::fake(['api.wiseoldman.net/*' => Http::response(['code' => 'PLAYER_NOT_FOUND'], 404)]);
        $event = $this->race();
        $standing = $this->standings()->enter($event, User::factory()->create(['osrs_username' => 'Not A Player']));

        $this->standings()->refresh($event, $standing);

        $this->assertSame('not_tracked', $standing->fresh()->sync_error);
    }

    /** Nullable column, required field — a fatal is a bad way to find out. */
    #[Test]
    public function a_race_with_no_metric_is_flagged_rather_than_throwing(): void
    {
        Http::fake();
        $event = $this->race(['metric' => null]);
        $standing = $this->standings()->enter($event, User::factory()->create(['osrs_username' => 'Pondake']));

        $this->standings()->refresh($event, $standing);

        $this->assertSame('no_metric', $standing->fresh()->sync_error);
        Http::assertNothingSent();
    }

    #[Test]
    public function a_race_that_has_not_started_is_left_alone(): void
    {
        Http::fake();
        $event = $this->race([
            'start_date' => Carbon::now()->addWeek(),
            'end_date' => Carbon::now()->addWeeks(3),
        ]);
        $standing = $this->standings()->enter($event, User::factory()->create(['osrs_username' => 'Pondake']));

        $this->standings()->refresh($event, $standing);

        $this->assertNull($standing->fresh()->synced_at);
        Http::assertNothingSent();
    }

    // ------------------------------------------------------ syncUsernames

    #[Test]
    public function a_rename_re_points_the_standing_and_clears_the_old_baseline(): void
    {
        Http::fake();
        $event = $this->race();
        $user = User::factory()->create(['osrs_username' => 'Old Name']);
        $standing = $this->standings()->enter($event, $user);
        $standing->forceFill(['gained' => 5000, 'start_value' => 10, 'synced_at' => Carbon::now()])->save();

        $user->forceFill(['osrs_username' => 'New Name'])->save();
        $this->standings()->syncUsernames($event);

        $standing->refresh();
        $this->assertSame('New Name', $standing->username);
        $this->assertSame(0, $standing->gained, 'the old baseline belonged to a different account');
        $this->assertNull($standing->synced_at);
    }

    /**
     * The bug that took down the entire scheduled sync: renaming into a name
     * already racing violated the unique index, and the exception killed the
     * command — freezing every remaining row in every remaining event.
     */
    #[Test]
    public function renaming_into_a_name_already_in_the_race_is_flagged_not_fatal(): void
    {
        Http::fake();
        $event = $this->race();
        $this->standings()->enter($event, User::factory()->create(['osrs_username' => 'Pondake']));

        $renamer = User::factory()->create(['osrs_username' => 'Someone Else']);
        $standing = $this->standings()->enter($event, $renamer);
        $renamer->forceFill(['osrs_username' => 'Pondake'])->save();

        $this->standings()->syncUsernames($event);

        $standing->refresh();
        $this->assertSame('duplicate_username', $standing->sync_error);
        $this->assertSame('Someone Else', $standing->username, 'keeps the name its numbers came from');
        $this->assertSame(2, EventStanding::count());
    }

    // ----------------------------------------------------------- forEvent

    #[Test]
    public function standings_are_ranked_by_gains_with_ties_sharing_a_place(): void
    {
        $event = $this->race();
        $this->seedRow($event, 'Winner', gained: 900);
        $this->seedRow($event, 'Tied A', gained: 100);
        $this->seedRow($event, 'Tied B', gained: 100);
        $this->seedRow($event, 'Last', gained: 5);

        $rows = $this->standings()->forEvent($event);

        // 1, 2, 2, 4 — the next distinct score skips, matching how Wise Old
        // Man ranks a competition.
        $this->assertSame([1, 2, 2, 4], $rows->pluck('rank')->all());
        $this->assertSame('Winner', $rows->first()['name']);
    }

    /**
     * An unmeasured row has gained 0, so without special handling it ties
     * with everyone who genuinely gained nothing and takes a placing off
     * people who are actually competing.
     */
    #[Test]
    public function an_unmeasured_row_sorts_last_and_holds_no_rank(): void
    {
        $event = $this->race();
        $this->seedRow($event, 'Scored', gained: 50);
        $this->seedRow($event, 'Real Zero', gained: 0);
        $this->seedRow($event, 'Untracked', gained: 0, error: 'not_tracked');
        $this->seedRow($event, 'Never Synced', gained: 0, synced: false);

        $rows = $this->standings()->forEvent($event);

        $this->assertSame(['Scored', 'Real Zero', 'Never Synced', 'Untracked'], $rows->pluck('name')->all());
        $this->assertSame([1, 2, null, null], $rows->pluck('rank')->all());
    }

    // --------------------------------------------------------- fingerprint

    #[Test]
    public function the_fingerprint_changes_only_when_the_visible_standings_do(): void
    {
        $event = $this->race();
        $row = $this->seedRow($event, 'Pondake', gained: 100);

        $before = $this->standings()->fingerprint($event);

        // A sync that finds no change still rewrites synced_at. That must not
        // wake every connected browser.
        $row->forceFill(['synced_at' => Carbon::now()->addHour()])->save();
        $this->assertSame($before, $this->standings()->fingerprint($event));

        $row->forceFill(['gained' => 200])->save();
        $this->assertNotSame($before, $this->standings()->fingerprint($event));
    }

    // -------------------------------------------------------- rate limiting

    /**
     * A 429 is ours, not the player's.
     *
     * It used to fall through the same `return null` as "no data", which the
     * caller wrote as `sync_error = 'not_tracked'` — and that message tells
     * the player to go and search their own name on wiseoldman.net. Wrong
     * advice, about an account that is tracked perfectly well, blaming them
     * for our pacing.
     */
    #[Test]
    public function a_rate_limited_lookup_is_not_reported_as_an_untracked_player(): void
    {
        Http::fake(['api.wiseoldman.net/v2/players/*/gained*' => Http::response('', 429)]);

        $event = $this->race();
        $row = $this->seedRow($event, 'Pondake', gained: 0, synced: false);

        $this->standings()->refresh($event, $row);

        $this->assertSame('rate_limited', $row->refresh()->sync_error);
    }

    /**
     * And it must not look answered.
     *
     * refreshAll() and the scheduled command both order never-synced and
     * failing rows first; stamping synced_at here would put a row we never
     * actually asked about at the back of the queue.
     */
    #[Test]
    public function a_rate_limited_row_stays_unsynced_so_the_next_run_retries_it(): void
    {
        Http::fake(['api.wiseoldman.net/v2/players/*/gained*' => Http::response('', 429)]);

        $event = $this->race();
        $row = $this->seedRow($event, 'Pondake', gained: 0, synced: false);

        $this->standings()->refresh($event, $row);

        $this->assertNull($row->refresh()->synced_at);
    }

    private function seedRow(Event $event, string $name, int $gained, ?string $error = null, bool $synced = true): EventStanding
    {
        return EventStanding::create([
            'event_id' => $event->id,
            'user_id' => User::factory()->create(['osrs_username' => $name])->id,
            'username' => $name,
            'gained' => $gained,
            'sync_error' => $error,
            'synced_at' => $synced ? Carbon::now() : null,
        ]);
    }
}
