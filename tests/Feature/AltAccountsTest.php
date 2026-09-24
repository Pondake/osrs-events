<?php

namespace Tests\Feature;

use App\Models\BingoCompletion;
use App\Models\BoardAuthor;
use App\Models\Event;
use App\Models\EventStanding;
use App\Models\OsrsAccount;
use App\Models\PluginToken;
use App\Models\Setting;
use App\Models\User;
use App\Services\BingoService;
use App\Services\EventStandingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * More than one OSRS character per account: the main at position 0, alts
 * after it, each playing like the main wherever the host lets alts count.
 */
class AltAccountsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake(['api.wiseoldman.net/*' => Http::response(['code' => 'PLAYER_NOT_FOUND'], 404)]);
    }

    private function player(): User
    {
        return User::factory()->create(['osrs_username' => 'Main Sample']);
    }

    private function save(User $user, array $names)
    {
        return $this->actingAs($user)->put('/settings/connections/osrs', ['characters' => $names]);
    }

    private function names(User $user): array
    {
        return $user->osrsAccounts()->pluck('username')->all();
    }

    private function race(array $attributes = []): Event
    {
        return Event::create([
            'title' => 'Zulrah race',
            'type' => 'DROP_RACE',
            'metric' => 'zulrah',
            'mode' => 'SOLO',
            'access_mode' => 'OPEN',
            'is_listed' => true,
            'start_date' => Carbon::now()->subWeek(),
            'end_date' => Carbon::now()->addWeek(),
            ...$attributes,
        ]);
    }

    // ------------------------------------------------------------ the list

    #[Test]
    public function an_account_starts_with_its_main_as_the_only_character(): void
    {
        $user = $this->player();

        $this->assertSame(['Main Sample'], $this->names($user));
        $this->assertTrue($user->osrsAccounts()->first()->isMain());
    }

    #[Test]
    public function alts_are_saved_in_the_order_given(): void
    {
        $user = $this->player();

        $this->save($user, ['Main Sample', 'Iron Sample', 'Pure Sample'])->assertSessionHasNoErrors();

        $this->assertSame(['Main Sample', 'Iron Sample', 'Pure Sample'], $this->names($user));
        $this->assertSame('Main Sample', $user->fresh()->osrs_username);
    }

    #[Test]
    public function dragging_an_alt_to_the_top_makes_it_the_main_and_keeps_every_proof(): void
    {
        $user = $this->player();
        $this->save($user, ['Main Sample', 'Iron Sample']);
        $user->osrsAccounts()->where('username', 'Iron Sample')->update(['osrs_proven_at' => now(), 'osrs_proven_via' => 'runelite']);
        $user->fresh()->forceFill(['osrs_proven_at' => now(), 'osrs_proven_via' => 'runelite'])->save();

        $this->save($user->fresh(), ['iron_sample', 'Main Sample'])->assertSessionHasNoErrors();

        $user = $user->fresh();
        $this->assertSame(['Iron Sample', 'Main Sample'], $this->names($user));
        $this->assertSame('Iron Sample', $user->osrs_username);
        $this->assertNotNull($user->osrs_proven_at);
        $this->assertSame(2, OsrsAccount::whereNotNull('osrs_proven_at')->count());
    }

    #[Test]
    public function the_limit_counts_the_main_and_comes_from_the_admin_setting(): void
    {
        Setting::set('max_osrs_characters', 2);
        $user = $this->player();

        $this->save($user, ['Main Sample', 'Alt One', 'Alt Two'])->assertSessionHasErrors('characters');
        $this->assertSame(['Main Sample'], $this->names($user));
    }

    #[Test]
    public function lowering_the_limit_takes_no_names_away(): void
    {
        $user = $this->player();
        $this->save($user, ['Main Sample', 'Alt One', 'Alt Two']);
        Setting::set('max_osrs_characters', 1);

        $this->save($user, ['Alt Two', 'Main Sample', 'Alt One'])->assertSessionHasNoErrors();
        $this->save($user, ['Alt Two', 'Main Sample', 'Alt One', 'Alt Three'])->assertSessionHasErrors('characters');
    }

    #[Test]
    public function the_same_character_twice_is_refused(): void
    {
        $this->save($this->player(), ['Main Sample', 'main sample'])->assertSessionHasErrors('characters');
    }

    #[Test]
    public function an_alt_proved_by_another_account_cannot_be_added(): void
    {
        $other = User::factory()->create(['osrs_username' => 'Someone']);
        OsrsAccount::create(['user_id' => $other->id, 'username' => 'Taken Alt', 'position' => 1, 'osrs_proven_at' => now()]);

        $this->save($this->player(), ['Main Sample', 'Taken Alt'])->assertSessionHasErrors('characters.1');
    }

    // ------------------------------------------------------------ the plugin

    #[Test]
    public function the_plugin_adds_an_unknown_character_as_a_proved_alt(): void
    {
        $user = $this->player();
        $code = PluginToken::issueFor($user);
        Setting::set('runelite_plugin_mode', 'testing');

        $this->withHeader('Authorization', "Bearer {$code}")
            ->postJson('/api/plugin/v1/identity', ['rsn' => 'Iron Sample'])
            ->assertOk()
            ->assertJsonPath('added', true)
            ->assertJsonPath('characters.1.rsn', 'Iron Sample')
            ->assertJsonPath('characters.1.proven', true);
    }

    #[Test]
    public function a_full_account_gets_no_new_alt_from_the_plugin(): void
    {
        Setting::set('max_osrs_characters', 1);
        Setting::set('runelite_plugin_mode', 'testing');
        $user = $this->player();

        $this->withHeader('Authorization', 'Bearer '.PluginToken::issueFor($user))
            ->postJson('/api/plugin/v1/identity', ['rsn' => 'Iron Sample'])
            ->assertJsonPath('added', false)
            ->assertJsonPath('reason', 'limit');
    }

    #[Test]
    public function the_plugin_may_report_from_any_character_on_the_account(): void
    {
        Setting::set('runelite_plugin_mode', 'testing');
        $user = $this->player();
        $this->save($user, ['Main Sample', 'Iron Sample']);
        $api = $this->withHeader('Authorization', 'Bearer '.PluginToken::issueFor($user));
        $report = fn (string $rsn, string $id) => [
            'client_event_id' => $id, 'kind' => 'item', 'name' => 'Abyssal whip',
            'quantity' => 1, 'rsn' => $rsn, 'occurred_at' => now()->toIso8601String(),
        ];

        $api->postJson('/api/plugin/v1/completions', $report('Iron Sample', 'a'))->assertCreated();
        $api->postJson('/api/plugin/v1/completions', $report('Stranger', 'b'))->assertStatus(422);
    }

    // ------------------------------------------------------------ races

    #[Test]
    public function every_character_enters_and_the_best_one_carries_the_line(): void
    {
        $user = $this->player();
        $this->save($user, ['Main Sample', 'Iron Sample']);
        $event = $this->race();
        $standings = app(EventStandingsService::class);

        $standings->enter($event, $user->fresh());
        $this->assertSame(2, EventStanding::where('event_id', $event->id)->count());

        EventStanding::where('username', 'Main Sample')->update(['gained' => 10, 'synced_at' => now()]);
        EventStanding::where('username', 'Iron Sample')->update(['gained' => 25, 'synced_at' => now()]);
        $rival = User::factory()->create(['osrs_username' => 'Rival']);
        $standings->enter($event, $rival);
        EventStanding::where('username', 'Rival')->update(['gained' => 20, 'synced_at' => now()]);

        $lines = $standings->forEvent($event);

        $this->assertCount(2, $lines);
        $this->assertSame('Iron Sample', $lines[0]['name']);
        $this->assertSame(25, $lines[0]['gained']);
        $this->assertTrue($lines[0]['alt']);
        $this->assertSame(1, $lines[0]['rank']);
        $this->assertSame('Main Sample', $lines[0]['characters'][0]['name']);
        $this->assertSame(2, $lines[1]['rank']);
    }

    #[Test]
    public function the_plugin_lists_running_races_as_the_leaderboard_stands(): void
    {
        Setting::set('runelite_plugin_mode', 'testing');
        $user = $this->player();
        $this->save($user, ['Main Sample', 'Iron Sample']);
        $event = $this->race(['end_date' => Carbon::parse('2026-10-01')->addYears(5)]);
        $standings = app(EventStandingsService::class);
        $standings->enter($event, $user->fresh());
        $standings->enter($event, User::factory()->create(['osrs_username' => 'Rival Sample']));
        EventStanding::where('username', 'Iron Sample')->update(['gained' => 42, 'live_gained' => 5, 'synced_at' => now()]);
        EventStanding::where('username', 'Main Sample')->update(['gained' => 10, 'synced_at' => now()]);
        EventStanding::where('username', 'Rival Sample')->update(['gained' => 80, 'synced_at' => now()]);
        $this->race(['title' => 'Not entered']);
        $this->race(['title' => 'Upcoming', 'start_date' => Carbon::now()->addWeek(), 'end_date' => Carbon::now()->addWeeks(2)]);

        $this->withHeader('Authorization', 'Bearer '.PluginToken::issueFor($user))
            ->getJson('/api/plugin/v1/events')
            ->assertOk()
            ->assertJsonCount(1, 'races')
            ->assertJsonPath('races.0.id', $event->id)
            ->assertJsonPath('races.0.type', 'DROP_RACE')
            ->assertJsonPath('races.0.metric', 'Zulrah')
            ->assertJsonPath('races.0.unit', 'kills')
            ->assertJsonPath('races.0.rank', 2)
            ->assertJsonPath('races.0.entrants', 2)
            ->assertJsonPath('races.0.gained', 42)
            ->assertJsonPath('races.0.live', 5)
            ->assertJsonPath('races.0.leader', 80);
    }

    #[Test]
    public function with_alts_off_only_the_main_enters(): void
    {
        $user = $this->player();
        $this->save($user, ['Main Sample', 'Iron Sample']);
        $event = $this->race(['allow_alts' => false]);

        app(EventStandingsService::class)->enter($event, $user->fresh());

        $this->assertSame(['Main Sample'], EventStanding::where('event_id', $event->id)->pluck('username')->all());
    }

    #[Test]
    public function an_alt_removed_mid_event_keeps_its_numbers(): void
    {
        $user = $this->player();
        $this->save($user, ['Main Sample', 'Iron Sample']);
        $event = $this->race();
        $standings = app(EventStandingsService::class);
        $standings->enter($event, $user->fresh());
        EventStanding::where('username', 'Iron Sample')->update(['gained' => 25, 'synced_at' => now()]);

        $this->save($user->fresh(), ['Main Sample']);
        $standings->syncUsernames($event);

        $row = EventStanding::where('username', 'Iron Sample')->first();
        $this->assertNotNull($row);
        $this->assertNull($row->osrs_account_id);
        $this->assertSame(25, $row->gained);
    }

    // ------------------------------------------------------------ the host

    #[Test]
    public function the_host_cannot_switch_alts_once_the_event_has_started(): void
    {
        $event = $this->race();
        $host = User::factory()->create(['osrs_username' => 'Host']);
        BoardAuthor::create(['event_id' => $event->id, 'user_id' => $host->id, 'is_owner' => true]);

        $this->actingAs($host)->patch("/events/{$event->id}", ['allow_alts' => false])->assertSessionHasErrors('allow_alts');
        $this->assertTrue($event->fresh()->allow_alts);

        $upcoming = $this->race(['start_date' => Carbon::now()->addWeek(), 'end_date' => Carbon::now()->addWeeks(2)]);
        BoardAuthor::create(['event_id' => $upcoming->id, 'user_id' => $host->id, 'is_owner' => true]);

        $this->actingAs($host)->patch("/events/{$upcoming->id}", ['allow_alts' => false])->assertSessionHasNoErrors();
        $this->assertFalse($upcoming->fresh()->allow_alts);
    }

    // ------------------------------------------------------------ claims

    #[Test]
    public function a_claim_remembers_which_character_made_it(): void
    {
        $user = $this->player();
        $this->save($user, ['Main Sample', 'Iron Sample']);
        $event = Event::create(['title' => 'Bingo', 'type' => 'BINGO', 'mode' => 'SOLO', 'access_mode' => 'OPEN', 'is_listed' => true]);
        $card = $event->bingoCard()->create(['size' => 3, 'win_condition' => 'LINE']);
        app(BingoService::class)->ensureSquares($card);
        [$first, $second] = $card->squares()->orderBy('position')->take(2)->get()->all();

        $this->actingAs($user)->post("/events/{$event->id}/bingo/squares/{$first->id}/claim", ['proof_url' => 'https://i.imgur.com/x.png', 'rsn' => 'iron_sample']);
        $this->assertSame('Iron Sample', BingoCompletion::first()->rsn);

        $this->actingAs($user)->post("/events/{$event->id}/bingo/squares/{$second->id}/claim", ['proof_url' => 'https://i.imgur.com/x.png', 'rsn' => 'Stranger'])
            ->assertSessionHasErrors('rsn');
    }
}
