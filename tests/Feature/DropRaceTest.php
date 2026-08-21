<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventStanding;
use App\Models\User;
use App\Services\EventStandingsService;
use App\Services\WiseOldManService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * A drop race is a boss killcount race.
 *
 * Wise Old Man returns `bosses.{name}.kills` in the same envelope as
 * `skills.{name}.experience`, so this type reuses the whole standings
 * pipeline and differs only in which branch of the response is read. These
 * cover that branch, since reading the wrong one would silently score every
 * participant zero rather than failing.
 */
class DropRaceTest extends TestCase
{
    use RefreshDatabase;

    private function race(): Event
    {
        return Event::create([
            'title' => 'Drop race — Zulrah',
            'type' => 'DROP_RACE',
            'metric' => 'zulrah',
            'mode' => 'SOLO',
            'access_mode' => 'OPEN',
            'is_listed' => true,
            'start_date' => Carbon::now()->subWeek(),
            'end_date' => Carbon::now()->addWeek(),
        ]);
    }

    private function fakeKills(int $gained, int $start = 100): void
    {
        Http::fake(['api.wiseoldman.net/*' => Http::response([
            'data' => [
                // Both branches present, as the real API returns them — the
                // point is that the boss one is what gets read.
                'skills' => ['mining' => ['experience' => ['gained' => 999999, 'start' => 0, 'end' => 999999]]],
                'bosses' => ['zulrah' => ['kills' => ['gained' => $gained, 'start' => $start, 'end' => $start + $gained]]],
            ],
        ])]);
    }

    #[Test]
    public function it_reads_kills_rather_than_experience(): void
    {
        $this->fakeKills(42);
        $event = $this->race();
        $standings = app(EventStandingsService::class);
        $standing = $standings->enter($event, User::factory()->create(['osrs_username' => 'Pondake']));

        $standings->refresh($event, $standing);

        // 42, not the 999999 sitting in the skills branch of the same payload.
        $this->assertSame(42, $standing->fresh()->gained);
    }

    #[Test]
    public function the_event_knows_which_vocabulary_it_uses(): void
    {
        $this->assertSame('boss', $this->race()->metricKind());
        $this->assertContains('zulrah', Event::metricsForType('DROP_RACE'));
        $this->assertNotContains('mining', Event::metricsForType('DROP_RACE'));
        $this->assertContains('mining', Event::metricsForType('SKILL_RACE'));
    }

    /**
     * Wise Old Man uses -1 for "unranked" — the player is not on the
     * hiscores for this boss at all, which is common. It is an absence, not
     * a count, and the column it lands in is unsigned.
     */
    #[Test]
    public function an_unranked_player_stores_null_rather_than_minus_one(): void
    {
        Http::fake(['api.wiseoldman.net/*' => Http::response([
            'data' => ['bosses' => ['zulrah' => ['kills' => ['gained' => 0, 'start' => -1, 'end' => -1]]]],
        ])]);

        $event = $this->race();
        $standings = app(EventStandingsService::class);
        $standing = $standings->enter($event, User::factory()->create(['osrs_username' => 'Lynx Titan']));

        $standings->refresh($event, $standing);

        $standing->refresh();
        $this->assertNull($standing->start_value);
        $this->assertNull($standing->end_value);
        $this->assertSame(0, $standing->gained);
    }

    #[Test]
    public function the_page_renders_the_shared_standings_component(): void
    {
        Http::fake();
        $event = $this->race();

        $this->actingAs(User::factory()->create(['osrs_username' => 'Pondake']))
            ->get("/events/{$event->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Events/SkillRace')
                ->where('event.metric', 'zulrah')
                ->where('event.metricKind', 'boss'));
    }

    #[Test]
    public function the_sync_command_picks_up_drop_races_too(): void
    {
        $this->fakeKills(7);
        $event = $this->race();
        $standing = EventStanding::create([
            'event_id' => $event->id,
            'user_id' => User::factory()->create(['osrs_username' => 'Pondake'])->id,
            'username' => 'Pondake',
        ]);

        $this->artisan('events:sync-standings')->assertSuccessful();

        $this->assertSame(7, $standing->fresh()->gained);
    }

    #[Test]
    public function a_boss_metric_is_rejected_on_a_skill_race(): void
    {
        // Both lists are valid metrics overall, so the guard that matters is
        // that the service reads the right branch — a skill race asking for
        // 'zulrah' finds nothing under skills and reports it untracked
        // rather than silently scoring a boss.
        Http::fake(['api.wiseoldman.net/*' => Http::response([
            'data' => ['bosses' => ['zulrah' => ['kills' => ['gained' => 5, 'start' => 0, 'end' => 5]]]],
        ])]);

        $event = Event::create([
            'title' => 'Confused race',
            'type' => 'SKILL_RACE',
            'metric' => 'zulrah',
            'mode' => 'SOLO',
            'access_mode' => 'OPEN',
            'is_listed' => true,
            'start_date' => Carbon::now()->subWeek(),
            'end_date' => Carbon::now()->addWeek(),
        ]);

        $standings = app(EventStandingsService::class);
        $standing = $standings->enter($event, User::factory()->create(['osrs_username' => 'Pondake']));
        $standings->refresh($event, $standing);

        $this->assertSame('not_tracked', $standing->fresh()->sync_error);
    }

    #[Test]
    public function the_client_asks_for_the_boss_branch_by_kind(): void
    {
        $this->fakeKills(3);

        $delta = app(WiseOldManService::class)->gained(
            'Pondake',
            'zulrah',
            'boss',
            Carbon::parse('2026-08-01'),
            Carbon::parse('2026-08-20'),
        );

        $this->assertSame(3, $delta['gained']);
    }
}
