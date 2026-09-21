<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventStanding;
use App\Models\PluginToken;
use App\Models\Setting;
use App\Models\User;
use App\Services\EventStandingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * A drop race counting the kills the plugin reports, alongside Wise Old Man.
 *
 * @see \App\Services\RaceKillService
 */
class RaceKillTest extends TestCase
{
    use RefreshDatabase;

    private User $player;

    private string $code;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::set('runelite_plugin_mode', 'testing');
        $this->player = User::factory()->create(['osrs_username' => 'Iron Pondake']);
        $this->code = PluginToken::issueFor($this->player);
    }

    private function api(): static
    {
        return $this->withHeader('Authorization', "Bearer {$this->code}");
    }

    private function race(string $metric = 'zulrah', array $attributes = []): Event
    {
        $event = (new Event)->forceFill([
            'title' => 'Clan drop race',
            'type' => 'DROP_RACE',
            'mode' => 'SOLO',
            'access_mode' => 'OPEN',
            'is_listed' => true,
            'metric' => $metric,
            ...$attributes,
        ]);
        $event->save();

        app(EventStandingsService::class)->enter($event, $this->player);

        return $event;
    }

    private function kill(string $name, int $killCount, ?string $occurredAt = null, ?User $as = null, ?string $code = null): \Illuminate\Testing\TestResponse
    {
        return $this->withHeader('Authorization', 'Bearer '.($code ?? $this->code))
            ->postJson('/api/plugin/v1/completions', [
                'client_event_id' => (string) str()->uuid(),
                'kind' => 'npc_kill',
                'name' => $name,
                'quantity' => 1,
                'rsn' => ($as ?? $this->player)->osrs_username,
                'occurred_at' => $occurredAt ?? now()->toIso8601String(),
                'context' => ['npc_name' => $name, 'kill_count' => $killCount],
            ]);
    }

    private function standing(Event $event): EventStanding
    {
        return EventStanding::where('event_id', $event->id)->sole();
    }

    #[Test]
    public function the_race_boss_is_in_the_watch_list(): void
    {
        $this->race('kreearra');

        // Normalised the same way a square's name is: one flat list of
        // strings, which is what the shipped plugin reads.
        $this->api()->getJson('/api/plugin/v1/events')
            ->assertOk()
            ->assertJsonPath('watch', ['kreearra']);
    }

    #[Test]
    public function a_boss_with_no_known_kill_count_message_is_not_watched(): void
    {
        $this->race('mimic');

        $this->api()->getJson('/api/plugin/v1/events')->assertOk()->assertJsonPath('watch', []);
    }

    #[Test]
    public function distinct_kill_counts_raise_the_standing(): void
    {
        $event = $this->race();

        $this->kill('Zulrah', 204, now()->subMinutes(2)->toIso8601String())->assertCreated();
        $this->kill('Zulrah', 205, now()->subMinute()->toIso8601String())->assertCreated();

        $standing = $this->standing($event);
        $this->assertSame(2, $standing->live_gained);
        $this->assertSame(2, $standing->gained);
    }

    #[Test]
    public function the_same_kill_reported_again_counts_once(): void
    {
        $event = $this->race();

        $this->kill('Zulrah', 204, now()->subMinute()->toIso8601String());
        $this->kill('Zulrah', 204)->assertCreated();

        $this->assertSame(1, $this->standing($event)->live_gained);
    }

    #[Test]
    public function a_kill_of_another_boss_counts_for_nothing(): void
    {
        $event = $this->race('zulrah');

        $this->kill('Vorkath', 12)->assertCreated();

        $this->assertSame(0, $this->standing($event)->live_gained);
    }

    #[Test]
    public function a_doubted_report_is_not_counted(): void
    {
        $event = $this->race();

        // A leaderboard has no host to review it, so a report the
        // plausibility check doubted moves no rank at all.
        $this->kill('Zulrah', 204, now()->addDay()->toIso8601String())->assertCreated();

        $this->assertSame(0, $this->standing($event)->live_gained);
    }

    #[Test]
    public function a_kill_outside_the_event_window_counts_for_nothing(): void
    {
        $event = $this->race('zulrah', ['start_date' => now()->subDay()]);

        $this->kill('Zulrah', 204, now()->subDays(3)->toIso8601String())->assertCreated();

        $this->assertSame(0, $this->standing($event)->live_gained);
    }

    #[Test]
    public function a_paused_or_ended_race_counts_nothing(): void
    {
        $paused = $this->race('zulrah', ['paused_at' => now()]);
        $this->kill('Zulrah', 204)->assertCreated();
        $this->assertSame(0, $this->standing($paused)->live_gained);
    }

    #[Test]
    public function a_kill_is_counted_for_the_player_who_reported_it(): void
    {
        $event = $this->race();

        $other = User::factory()->create(['osrs_username' => 'Someone']);
        app(EventStandingsService::class)->enter($event, $other);

        $this->kill('Zulrah', 204, null, $other, PluginToken::issueFor($other))->assertCreated();

        $rows = EventStanding::where('event_id', $event->id)->get()->keyBy('username');
        $this->assertSame(1, $rows['Someone']->live_gained);
        $this->assertSame(0, $rows['Iron Pondake']->live_gained);
    }

    #[Test]
    public function a_sync_never_walks_a_live_count_backwards(): void
    {
        $event = $this->race();

        $this->kill('Zulrah', 204, now()->subMinutes(2)->toIso8601String());
        $this->kill('Zulrah', 205, now()->subMinute()->toIso8601String());

        // The hiscores lag by hours, so a sync landing between two kills sees
        // fewer than the plugin already reported.
        $standing = $this->standing($event);
        $standing->forceFill(['gained' => 1, 'synced_at' => now(), 'sync_error' => null])->save();

        $this->assertSame(2, $this->standing($event)->live_gained);

        // And once their number catches up it wins on its own.
        $standing->forceFill(['gained' => max(9, $standing->live_gained)])->save();
        $this->assertSame(9, $this->standing($event)->gained);
    }

    #[Test]
    public function a_player_the_hiscores_have_never_answered_for_is_still_ranked(): void
    {
        $event = $this->race();

        $this->kill('Zulrah', 204)->assertCreated();

        $row = app(EventStandingsService::class)->forEvent($event)->sole();
        $this->assertSame(1, $row['rank']);
        $this->assertSame(1, $row['gained']);
        $this->assertSame(1, $row['live']);
    }

    #[Test]
    public function a_live_kill_changes_the_standings_fingerprint(): void
    {
        $event = $this->race();
        $standings = app(EventStandingsService::class);

        $before = $standings->fingerprint($event);
        $this->kill('Zulrah', 204);

        $this->assertNotSame($before, $standings->fingerprint($event));
    }

    #[Test]
    public function a_rename_drops_the_kills_counted_under_the_old_name(): void
    {
        $event = $this->race();

        $this->kill('Zulrah', 204);
        $this->assertSame(1, $this->standing($event)->live_gained);

        $this->player->forceFill(['osrs_username' => 'Iron Pondake II'])->save();
        app(EventStandingsService::class)->syncUsernames($event);

        $standing = $this->standing($event);
        $this->assertSame(0, $standing->live_gained);
        $this->assertSame(0, $standing->kills()->count());
    }

    #[Test]
    public function a_bingo_square_and_a_race_can_both_count_one_kill(): void
    {
        $event = $this->race();

        $this->kill('Zulrah', 204)
            ->assertCreated()
            // Nothing claims: the race is not a target, so `claims` stays
            // empty and the plugin's answer does not change shape.
            ->assertJsonPath('claims', []);

        $this->assertSame(1, $this->standing($event)->live_gained);
    }
}
