<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventStanding;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The scheduled sync.
 *
 * This runs unattended, which is what makes its failure modes nasty: nobody
 * sees an exception, and the only symptom is a leaderboard that quietly stops
 * moving. Several tests here exist because that already happened once.
 */
class SyncEventStandingsCommandTest extends TestCase
{
    use RefreshDatabase;

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

    private function enter(Event $event, string $name): EventStanding
    {
        return EventStanding::create([
            'event_id' => $event->id,
            'user_id' => User::factory()->create(['osrs_username' => $name])->id,
            'username' => $name,
        ]);
    }

    private function fakeGains(int $gained): void
    {
        Http::fake(['api.wiseoldman.net/*' => Http::response([
            'data' => ['skills' => ['mining' => ['experience' => [
                'gained' => $gained, 'start' => 0, 'end' => $gained,
            ]]]],
        ])]);
    }

    #[Test]
    public function it_fills_in_the_standings_for_a_running_race(): void
    {
        $this->fakeGains(1234);
        $event = $this->race();
        $standing = $this->enter($event, 'Pondake');

        $this->artisan('events:sync-standings', ['--event' => $event->id])->assertSuccessful();

        $this->assertSame(1234, $standing->fresh()->gained);
    }

    /** A finished race's numbers are final; a future one has nothing to measure. */
    #[Test]
    public function it_skips_races_outside_their_window(): void
    {
        Http::fake();
        $this->enter($this->race([
            'start_date' => Carbon::now()->addWeek(),
            'end_date' => Carbon::now()->addWeeks(3),
        ]), 'Pondake');
        $this->enter($this->race([
            'title' => 'Long finished',
            'start_date' => Carbon::now()->subMonths(3),
            'end_date' => Carbon::now()->subMonths(2),
        ]), 'Zezima');

        $this->artisan('events:sync-standings')->assertSuccessful();

        Http::assertNothingSent();
    }

    #[Test]
    public function it_ignores_snakes_and_ladders_events_entirely(): void
    {
        Http::fake();
        Event::create([
            'title' => 'Winter Clan Grind',
            'type' => 'SNAKES_LADDERS',
            'mode' => 'SOLO',
            'access_mode' => 'OPEN',
            'is_listed' => true,
        ]);

        $this->artisan('events:sync-standings')->assertSuccessful();

        Http::assertNothingSent();
    }

    /**
     * The one that matters most. One participant must never be able to stop
     * the run — an exception on an early row used to leave every later row,
     * in this event and every event after it, permanently stale.
     */
    #[Test]
    public function one_failing_participant_does_not_stop_the_others(): void
    {
        $event = $this->race();
        $broken = $this->enter($event, 'Breaks It');
        $good = $this->enter($event, 'Fine Player');

        // Keyed on the username rather than call order: the command syncs
        // least-recently-synced first, and both rows start at null, so the
        // order between them is not something to assert on.
        //
        // A thrown ConnectionException, not a 500: gained() already handles
        // a bad status itself, so only a real exception exercises the
        // command's own try/catch — which is the thing under test.
        Http::fake(function ($request) {
            if (str_contains($request->url(), rawurlencode('Breaks It'))) {
                throw new ConnectionException('connection reset');
            }

            return Http::response(['data' => ['skills' => ['mining' => ['experience' => [
                'gained' => 777, 'start' => 0, 'end' => 777,
            ]]]]]);
        });

        $this->artisan('events:sync-standings', ['--event' => $event->id])->assertSuccessful();

        $this->assertSame(777, $good->fresh()->gained, 'the healthy row still synced');
        $this->assertNull($broken->fresh()->synced_at, 'the failing row was left alone, not scored zero');
    }

    /** Re-running must not double anything or churn names. */
    #[Test]
    public function running_it_twice_is_idempotent(): void
    {
        $this->fakeGains(500);
        $event = $this->race();
        $standing = $this->enter($event, 'Pondake');

        $this->artisan('events:sync-standings', ['--event' => $event->id])->assertSuccessful();
        $this->artisan('events:sync-standings', ['--event' => $event->id])->assertSuccessful();

        $this->assertSame(1, EventStanding::count());
        $this->assertSame(500, $standing->fresh()->gained);
    }

    /**
     * --track POSTs to Wise Old Man to re-import a player. That is a write
     * against someone else's service, so it must never happen by default.
     */
    #[Test]
    public function it_never_writes_to_wise_old_man_without_the_track_flag(): void
    {
        $this->fakeGains(10);
        $event = $this->race();
        $this->enter($event, 'Pondake');

        $this->artisan('events:sync-standings', ['--event' => $event->id])->assertSuccessful();

        Http::assertSentCount(1);
        Http::assertNotSent(fn ($request) => $request->method() === 'POST');
    }
}
