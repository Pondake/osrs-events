<?php

namespace Tests\Feature;

use App\Models\BingoCard;
use App\Models\BingoCompletion;
use App\Models\CompletedTile;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\PlayerBoard;
use App\Models\PluginCompletion;
use App\Models\PluginToken;
use App\Models\Setting;
use App\Models\Task;
use App\Models\Tile;
use App\Models\User;
use App\Services\BingoService;
use App\Support\RuneliteName;
use Database\Seeders\RunelitePluginTestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RunelitePluginApiTest extends TestCase
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

    private function task(string $title, ?int $wikiPageId = 1): Task
    {
        return Task::create(['title' => $title, 'wiki_page_id' => $wikiPageId === 1 ? random_int(10, 999999) : $wikiPageId]);
    }

    private function event(string $type, array $attributes = []): Event
    {
        $event = (new Event)->forceFill([
            'title' => "Clan {$type}",
            'type' => $type,
            'mode' => 'SOLO',
            'access_mode' => 'OPEN',
            'is_listed' => true,
            ...$attributes,
        ]);
        $event->save();

        EventParticipant::create(['event_id' => $event->id, 'user_id' => $this->player->id]);

        return $event;
    }

    /** @param array<int, Task|null> $tasks by position */
    private function card(array $tasks, array $attributes = []): BingoCard
    {
        $card = $this->event('BINGO')->bingoCard()->create(['size' => 3, ...$attributes]);
        app(BingoService::class)->ensureSquares($card);

        foreach ($tasks as $position => $task) {
            $card->squares()->where('position', $position)->update(['task_id' => $task?->id]);
        }

        return $card->fresh();
    }

    private function completion(string $name, array $overrides = []): array
    {
        return [
            'client_event_id' => (string) str()->uuid(),
            'kind' => 'item',
            'name' => $name,
            'quantity' => 1,
            'rsn' => 'Iron Pondake',
            'occurred_at' => now()->toIso8601String(),
            ...$overrides,
        ];
    }

    // ---------------------------------------------------------------- auth

    #[Test]
    public function the_api_does_not_exist_while_the_plugin_is_off(): void
    {
        Setting::set('runelite_plugin_mode', 'off');

        $this->api()->getJson('/api/plugin/v1/events')->assertNotFound();
        $this->api()->postJson('/api/plugin/v1/completions', $this->completion('Big bones'))->assertNotFound();
    }

    #[Test]
    public function a_missing_or_unknown_code_is_unauthenticated(): void
    {
        $this->getJson('/api/plugin/v1/events')->assertUnauthorized();
        $this->withHeader('Authorization', 'Bearer ose_nope')->getJson('/api/plugin/v1/events')->assertUnauthorized();
        $this->withHeader('Authorization', 'Bearer '.substr($this->code, 4))->getJson('/api/plugin/v1/events')->assertUnauthorized();
    }

    #[Test]
    public function a_valid_code_is_stamped_as_used(): void
    {
        $this->api()->getJson('/api/plugin/v1/events')->assertOk()->assertJsonPath('rsn', 'Iron Pondake');

        $this->assertNotNull(PluginToken::findByPlain($this->code)->last_used_at);
    }

    #[Test]
    public function a_code_is_rate_limited(): void
    {
        for ($i = 0; $i < 60; $i++) {
            $this->api()->getJson('/api/plugin/v1/events')->assertOk();
        }

        $this->api()->getJson('/api/plugin/v1/events')->assertTooManyRequests();
    }

    // ------------------------------------------------------------- targets

    #[Test]
    public function only_open_squares_with_a_wiki_task_are_targets(): void
    {
        $whip = $this->task('Abyssal whip');
        $claimed = $this->task('Big bones');
        $card = $this->card([
            0 => $whip,
            1 => $this->task('Kill 50 goblins', null),
            2 => $claimed,
            3 => $this->task('Zulrah'),
        ]);
        $card->squares()->where('position', 3)->update(['is_wildcard' => true]);
        BingoCompletion::create([
            'bingo_square_id' => $card->squares()->where('position', 2)->value('id'),
            'user_id' => $this->player->id,
            'marked_by' => $this->player->id,
            'status' => 'PENDING',
        ]);

        $this->api()->getJson('/api/plugin/v1/events')
            ->assertOk()
            ->assertJsonCount(1, 'events')
            ->assertJsonCount(1, 'events.0.targets')
            ->assertJsonPath('events.0.targets.0.name', 'Abyssal whip')
            ->assertJsonPath('events.0.targets.0.match', 'abyssal whip')
            ->assertJsonPath('watch', ['abyssal whip']);
    }

    #[Test]
    public function paused_ended_upcoming_and_unjoined_events_are_left_out(): void
    {
        $task = $this->task('Abyssal whip');

        foreach ([
            ['paused_at' => now()],
            ['closed_at' => now()],
            ['end_date' => now()->subDays(2)],
            ['start_date' => now()->addDays(2)],
        ] as $attributes) {
            $card = $this->event('BINGO', $attributes)->bingoCard()->create(['size' => 3]);
            app(BingoService::class)->ensureSquares($card);
            $card->squares()->update(['task_id' => $task->id]);
        }

        $unjoined = Event::create(['title' => 'Other', 'type' => 'BINGO', 'mode' => 'SOLO', 'access_mode' => 'OPEN', 'is_listed' => true]);
        $card = $unjoined->bingoCard()->create(['size' => 3]);
        app(BingoService::class)->ensureSquares($card);
        $card->squares()->update(['task_id' => $task->id]);

        $this->api()->getJson('/api/plugin/v1/events')->assertOk()->assertJsonCount(0, 'events')->assertJsonPath('watch', []);
        $this->api()->postJson('/api/plugin/v1/completions', $this->completion('Abyssal whip'))->assertCreated()->assertJsonPath('claims', []);
        $this->assertSame(0, BingoCompletion::count());
    }

    #[Test]
    public function snakes_and_ladders_offers_only_the_tile_the_player_stands_on(): void
    {
        $event = $this->event('SNAKES_LADDERS');
        $board = $event->board()->create(['size' => 'SIZE_5X5', 'requires_approval' => false]);
        $here = $this->task('Dragon bones');
        $ahead = $this->task('Abyssal whip');
        Tile::create(['board_id' => $board->id, 'position' => 3, 'type' => 'NORMAL', 'task_id' => $here->id]);
        Tile::create(['board_id' => $board->id, 'position' => 4, 'type' => 'NORMAL', 'task_id' => $ahead->id]);
        PlayerBoard::create(['board_id' => $board->id, 'user_id' => $this->player->id, 'current_position' => 3]);

        $this->api()->getJson('/api/plugin/v1/events')
            ->assertJsonCount(1, 'events.0.targets')
            ->assertJsonPath('events.0.targets.0.position', 3)
            ->assertJsonPath('watch', ['dragon bones']);

        $this->api()->postJson('/api/plugin/v1/completions', $this->completion('Abyssal whip'))->assertJsonPath('claims', []);
        $this->api()->postJson('/api/plugin/v1/completions', $this->completion('Dragon bones'))
            ->assertCreated()
            ->assertJsonPath('claims.0.kind', 'tile')
            ->assertJsonPath('claims.0.status', 'APPROVED');

        $this->assertSame('RUNELITE', CompletedTile::sole()->completed_via);
        $this->api()->getJson('/api/plugin/v1/events')->assertJsonCount(0, 'events.0.targets');
    }

    #[Test]
    public function a_tile_without_a_wiki_task_is_manual_only(): void
    {
        $event = $this->event('SNAKES_LADDERS');
        $board = $event->board()->create(['size' => 'SIZE_5X5']);
        Tile::create(['board_id' => $board->id, 'position' => 0, 'type' => 'NORMAL', 'task_id' => $this->task('Get 99 cooking', null)->id]);

        $this->api()->getJson('/api/plugin/v1/events')->assertJsonCount(0, 'events.0.targets');
        $this->api()->postJson('/api/plugin/v1/completions', $this->completion('Get 99 cooking'))->assertJsonPath('claims', []);
        $this->assertSame(0, CompletedTile::count());
    }

    #[Test]
    public function a_manual_only_square_cannot_be_reached_by_name(): void
    {
        $this->card([0 => $this->task('Big bones', null)]);

        $this->api()->postJson('/api/plugin/v1/completions', $this->completion('Big bones'))->assertCreated()->assertJsonPath('claims', []);
        $this->assertSame(0, BingoCompletion::count());
    }


    // ------------------------------------------------------ min_quantity

    #[Test]
    public function the_threshold_is_sent_with_every_target(): void
    {
        $card = $this->card([0 => $this->task('Soaked page')]);
        $card->squares()->where('position', 0)->update(['min_quantity' => 25]);

        $this->api()->getJson('/api/plugin/v1/events')
            ->assertOk()
            ->assertJsonPath('events.0.targets.0.min_quantity', 25);
    }

    /**
     * Below, at and above the bar.
     *
     * The dispute this exists for: Tempoross drops a Soaked page in stacks of
     * varying size and a clan had agreed only the 25 stack (1/400) counted.
     * A smaller report is not an error — the plugin reports every drop.
     */
    #[DataProvider('quantitiesAgainstTwentyFive')]
    #[Test]
    public function a_square_only_counts_from_its_threshold(int $quantity, bool $claims): void
    {
        $card = $this->card([0 => $this->task('Soaked page')], ['requires_approval' => false]);
        $card->squares()->where('position', 0)->update(['min_quantity' => 25]);

        $response = $this->api()->postJson('/api/plugin/v1/completions', $this->completion('Soaked page', ['quantity' => $quantity]))
            ->assertCreated();

        $response->assertJsonCount($claims ? 1 : 0, 'claims');
        $this->assertSame($claims ? 1 : 0, BingoCompletion::count());
    }

    public static function quantitiesAgainstTwentyFive(): array
    {
        return [
            'below' => [24, false],
            'at' => [25, true],
            'above' => [26, true],
        ];
    }

    #[Test]
    public function a_tile_only_counts_from_its_threshold(): void
    {
        $event = $this->event('SNAKES_LADDERS');
        $board = $event->board()->create(['size' => 'SIZE_5X5', 'requires_approval' => false]);
        Tile::create([
            'board_id' => $board->id,
            'position' => 0,
            'type' => 'NORMAL',
            'task_id' => $this->task('Soaked page')->id,
            'min_quantity' => 25,
        ]);
        PlayerBoard::create(['board_id' => $board->id, 'user_id' => $this->player->id, 'current_position' => 0]);

        $this->api()->getJson('/api/plugin/v1/events')->assertJsonPath('events.0.targets.0.min_quantity', 25);

        $this->api()->postJson('/api/plugin/v1/completions', $this->completion('Soaked page', ['quantity' => 24]))
            ->assertCreated()
            ->assertJsonPath('claims', []);
        $this->assertSame(0, CompletedTile::count());

        $this->api()->postJson('/api/plugin/v1/completions', $this->completion('Soaked page', ['quantity' => 25]))
            ->assertCreated()
            ->assertJsonPath('claims.0.kind', 'tile');
        $this->assertSame(1, CompletedTile::count());
    }

    /**
     * The deliberate choice: a single drop of 25 is not twenty-five drops of
     * one. Nothing accumulates — a clan that agreed the 25 stack counts did
     * not agree that twenty-five single pages do.
     */
    #[Test]
    public function reports_under_the_threshold_never_add_up(): void
    {
        $card = $this->card([0 => $this->task('Soaked page')], ['requires_approval' => false]);
        $card->squares()->where('position', 0)->update(['min_quantity' => 3]);

        for ($i = 0; $i < 5; $i++) {
            $this->api()->postJson('/api/plugin/v1/completions', $this->completion('Soaked page', ['quantity' => 1]))
                ->assertCreated()
                ->assertJsonPath('claims', []);
        }

        $this->assertSame(0, BingoCompletion::count());
        $this->assertSame(5, PluginCompletion::count());
    }

    #[Test]
    public function a_square_left_at_one_takes_any_amount(): void
    {
        $this->card([0 => $this->task('Abyssal whip')], ['requires_approval' => false]);

        $this->api()->postJson('/api/plugin/v1/completions', $this->completion('Abyssal whip', ['quantity' => 1]))
            ->assertCreated()
            ->assertJsonPath('claims.0.status', 'APPROVED');
        $this->assertSame(1, BingoCompletion::count());
    }
    // ---------------------------------------------------------- completing

    #[Test]
    public function a_completion_on_an_untrusting_reviewed_card_is_pending(): void
    {
        $this->card([0 => $this->task('Abyssal whip')], ['requires_approval' => true, 'trust_runelite_completions' => false]);

        $this->api()->postJson('/api/plugin/v1/completions', $this->completion('Abyssal whip'))
            ->assertCreated()
            ->assertJsonPath('duplicate', false)
            ->assertJsonPath('claims.0.status', 'PENDING');

        $claim = BingoCompletion::sole();
        $this->assertSame('RUNELITE', $claim->completed_via);
        $this->assertSame('PENDING', $claim->status);
        $this->assertNull($claim->proof_url);
    }

    #[Test]
    public function a_completion_on_a_trusting_card_is_approved_and_can_win_it(): void
    {
        $whip = $this->task('Abyssal whip');
        $card = $this->card([0 => $whip, 1 => $whip, 2 => $whip], ['requires_approval' => true, 'trust_runelite_completions' => true, 'size' => 3]);

        $this->api()->postJson('/api/plugin/v1/completions', $this->completion('Abyssal whip'))
            ->assertCreated()
            ->assertJsonCount(3, 'claims')
            ->assertJsonPath('claims.0.status', 'APPROVED');

        $this->assertSame(3, BingoCompletion::where('status', 'APPROVED')->count());
        $this->assertDatabaseHas('event_finishes', ['event_id' => $card->event_id, 'user_id' => $this->player->id]);
    }

    #[Test]
    public function a_retry_with_the_same_client_event_id_claims_nothing_twice(): void
    {
        $this->card([0 => $this->task('Abyssal whip'), 1 => $this->task('Abyssal whip')]);
        $payload = $this->completion('Abyssal whip');

        $first = $this->api()->postJson('/api/plugin/v1/completions', $payload)->assertCreated();
        $this->api()->postJson('/api/plugin/v1/completions', $payload)
            ->assertOk()
            ->assertJsonPath('duplicate', true)
            ->assertJsonPath('claims', $first->json('claims'));

        $this->assertSame(2, BingoCompletion::count());
        $this->assertSame(1, PluginCompletion::count());
    }

    #[Test]
    public function the_same_client_event_id_from_another_account_is_its_own_event(): void
    {
        $this->card([0 => $this->task('Abyssal whip')]);
        $payload = $this->completion('Abyssal whip', ['client_event_id' => 'drop-1']);

        $this->api()->postJson('/api/plugin/v1/completions', $payload)->assertCreated();

        $other = User::factory()->create(['osrs_username' => 'Someone']);
        $this->withHeader('Authorization', 'Bearer '.PluginToken::issueFor($other))
            ->postJson('/api/plugin/v1/completions', [...$payload, 'rsn' => 'Someone'])
            ->assertCreated()
            ->assertJsonPath('duplicate', false);
    }

    #[Test]
    public function a_completion_from_another_character_is_refused(): void
    {
        $this->card([0 => $this->task('Abyssal whip')]);

        $this->api()->postJson('/api/plugin/v1/completions', $this->completion('Abyssal whip', ['rsn' => 'Zezima']))
            ->assertUnprocessable()
            ->assertJsonPath('message', trans('plugin.api_rsn_mismatch', ['rsn' => 'Zezima', 'expected' => 'Iron Pondake']));

        $this->assertSame(0, BingoCompletion::count());
        $this->assertSame(0, PluginCompletion::count());
    }

    #[Test]
    public function the_rsn_compares_like_the_game_does(): void
    {
        $this->card([0 => $this->task('Abyssal whip')]);

        $this->api()->postJson('/api/plugin/v1/completions', $this->completion('Abyssal whip', ['rsn' => 'iron_pondake']))
            ->assertCreated()
            ->assertJsonCount(1, 'claims');
    }

    #[Test]
    public function an_invalid_payload_is_rejected(): void
    {
        $this->api()->postJson('/api/plugin/v1/completions', $this->completion('Abyssal whip', ['kind' => 'xp', 'quantity' => 0]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['kind', 'quantity']);
    }

    // ------------------------------------------------------------- context

    #[Test]
    public function context_is_accepted_and_stored_on_the_report_and_the_claim(): void
    {
        $this->card([0 => $this->task('Abyssal whip')]);

        $payload = $this->completion('Abyssal whip', [
            'kind' => 'npc_kill',
            'context' => [
                'source' => 'npc_kill',
                'npc_id' => 415,
                'npc_name' => 'Abyssal demon',
                'npc_level' => 124,
                'kill_count' => 217,
                'region_id' => 12441,
                'items' => [
                    ['id' => 4151, 'name' => 'Abyssal whip', 'quantity' => 1],
                    ['id' => 526, 'name' => 'Bones', 'quantity' => 1],
                ],
            ],
        ]);

        $this->api()->postJson('/api/plugin/v1/completions', $payload)->assertCreated();

        $report = PluginCompletion::sole();
        $this->assertSame('npc_kill', $report->context['source']);
        $this->assertSame('Abyssal demon', $report->context['npc_name']);
        $this->assertCount(2, $report->context['items']);

        $claim = BingoCompletion::sole();
        $this->assertSame($report->id, $claim->plugin_completion_id);
    }

    #[Test]
    public function context_is_optional(): void
    {
        $this->card([0 => $this->task('Abyssal whip')]);

        $this->api()->postJson('/api/plugin/v1/completions', $this->completion('Abyssal whip'))
            ->assertCreated();

        $report = PluginCompletion::sole();
        $this->assertNull($report->context);
        $this->assertSame($report->id, BingoCompletion::sole()->plugin_completion_id);
    }

    #[Test]
    public function an_unknown_context_key_is_a_422(): void
    {
        $this->card([0 => $this->task('Abyssal whip')]);

        $this->api()->postJson('/api/plugin/v1/completions', $this->completion('Abyssal whip', [
            'context' => ['source' => 'loot', 'chat_message' => 'nice drop'],
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['context']);

        $this->assertSame(0, PluginCompletion::count());
    }

    #[Test]
    public function an_unknown_key_on_an_item_is_a_422(): void
    {
        $this->card([0 => $this->task('Abyssal whip')]);

        $this->api()->postJson('/api/plugin/v1/completions', $this->completion('Abyssal whip', [
            'context' => ['items' => [['id' => 1, 'name' => 'Bones', 'quantity' => 1, 'exact_x' => 3200]]],
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['context.items.0']);
    }

    #[Test]
    public function a_game_name_matches_its_wiki_title(): void
    {
        $this->card([0 => $this->task('Prayer potion'), 1 => $this->task("Karil's coif"), 2 => $this->task('Pet Snakeling')]);

        foreach (['Prayer potion(4)', 'Karil’s coif', 'pet snakeling'] as $name) {
            $this->api()->postJson('/api/plugin/v1/completions', $this->completion($name))->assertJsonCount(1, 'claims');
        }
    }

    #[Test]
    public function the_events_call_reports_verdicts_on_this_players_claims(): void
    {
        $card = $this->card([0 => $this->task('Abyssal whip')], ['requires_approval' => true]);

        $this->api()->postJson('/api/plugin/v1/completions', $this->completion('Abyssal whip'))->assertCreated();
        $this->api()->getJson('/api/plugin/v1/events')->assertJsonPath('reviews', []);

        $completion = BingoCompletion::sole();
        $completion->update(['status' => 'APPROVED', 'reviewed_at' => now(), 'reviewed_by' => $this->player->id]);

        $this->api()->getJson('/api/plugin/v1/events')
            ->assertJsonCount(1, 'reviews')
            ->assertJsonPath('reviews.0.id', $completion->id)
            ->assertJsonPath('reviews.0.status', 'APPROVED')
            ->assertJsonPath('reviews.0.event_title', $card->event->title);
    }

    public static function names(): array
    {
        return [
            'case' => ['Pet snakeling', 'Pet Snakeling', true],
            'surrounding and double spaces' => ['  Dragon   bones ', 'Dragon bones', true],
            'underscores from a wiki url' => ['Abyssal_whip', 'Abyssal whip', true],
            'curly apostrophe' => ['Karil’s coif', "Karil's coif", true],
            'no apostrophe' => ['Karils coif', "Karil's coif", true],
            'dose suffix' => ['Prayer potion(4)', 'Prayer potion', true],
            'charge suffix with a space' => ['Games necklace (8)', 'Games necklace', true],
            'colour tags' => ['<col=ff9040>Giant Mole</col>', 'Giant Mole', true],
            'non-breaking space' => ["Giant\u{00A0}Mole", 'Giant Mole', true],
            'non-numeric suffix is a different item' => ['Clue scroll (medium)', 'Clue scroll', false],
            'imbued is a different item' => ['Berserker ring (i)', 'Berserker ring', false],
            'different item' => ['Dragon bones', 'Big bones', false],
        ];
    }

    #[Test]
    #[DataProvider('names')]
    public function names_are_normalised(string $game, string $wiki, bool $same): void
    {
        $this->assertSame($same, RuneliteName::normalize($game) === RuneliteName::normalize($wiki));
    }

    #[Test]
    public function the_test_set_seeds_one_manual_only_square(): void
    {
        $this->seed(RunelitePluginTestSeeder::class);
        $this->seed(RunelitePluginTestSeeder::class);

        $event = Event::where('title', RunelitePluginTestSeeder::EVENT_TITLE)->sole();
        EventParticipant::create(['event_id' => $event->id, 'user_id' => $this->player->id]);

        $this->assertSame(count(RunelitePluginTestSeeder::TASKS), Task::whereNotNull('wiki_page_id')->count());
        $this->api()->getJson('/api/plugin/v1/events')
            ->assertJsonCount(count(RunelitePluginTestSeeder::TASKS), 'events.0.targets');
    }
}
