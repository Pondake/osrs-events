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
use App\Models\TargetProgress;
use App\Models\Task;
use App\Models\Tile;
use App\Models\User;
use App\Services\BingoService;
use App\Support\RuneliteName;
use Database\Seeders\RunelitePluginTestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
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
        $this->player = User::factory()->create(['osrs_username' => 'Iron Sample']);
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
            'rsn' => 'Iron Sample',
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
        $this->api()->postJson('/api/plugin/v1/identity', ['rsn' => 'Iron Sample'])->assertNotFound();
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
        $this->api()->getJson('/api/plugin/v1/events')->assertOk()->assertJsonPath('rsn', 'Iron Sample');

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

    // ----------------------------------------------------- required_count

    #[Test]
    public function the_repetition_count_is_sent_with_every_target(): void
    {
        $card = $this->card([0 => $this->task('Zalcano shard')]);
        $card->squares()->where('position', 0)->update(['required_count' => 5]);

        $this->api()->getJson('/api/plugin/v1/events')
            ->assertOk()
            ->assertJsonPath('events.0.targets.0.required_count', 5);
    }

    /**
     * "Kill Zalcano three times." Three kills arrive as three reports whose
     * kill counts differ, and only the last one claims the square.
     */
    #[Test]
    public function distinct_kill_counts_walk_a_square_to_its_count(): void
    {
        $card = $this->card([0 => $this->task('Zalcano shard')], ['requires_approval' => false]);
        $card->squares()->where('position', 0)->update(['required_count' => 3]);

        foreach ([204, 205] as $killCount) {
            $this->api()->postJson('/api/plugin/v1/completions', $this->completion('Zalcano shard', [
                'context' => ['kill_count' => $killCount],
            ]))->assertCreated()->assertJsonPath('claims', []);
        }

        $this->assertSame(0, BingoCompletion::count());

        $this->api()->postJson('/api/plugin/v1/completions', $this->completion('Zalcano shard', [
            'context' => ['kill_count' => 206],
        ]))->assertCreated()->assertJsonPath('claims.0.status', 'APPROVED');

        $this->assertSame(1, BingoCompletion::count());
        $this->assertSame(3, TargetProgress::count());
    }

    /**
     * The reason the kill count is the unit rather than the report: a client
     * that resends the same kill under a fresh client_event_id must not walk
     * a three-kill square to three on one kill.
     */
    #[Test]
    public function the_same_kill_reported_again_does_not_advance_it(): void
    {
        $card = $this->card([0 => $this->task('Zalcano shard')], ['requires_approval' => false]);
        $card->squares()->where('position', 0)->update(['required_count' => 3]);

        for ($i = 0; $i < 5; $i++) {
            $this->api()->postJson('/api/plugin/v1/completions', $this->completion('Zalcano shard', [
                'context' => ['kill_count' => 204],
            ]))->assertCreated()->assertJsonPath('claims', []);
        }

        $this->assertSame(0, BingoCompletion::count());
        $this->assertSame(1, TargetProgress::count());
    }

    /** No kill count - a plain item drop - so each report is its own event. */
    #[Test]
    public function reports_without_a_kill_count_each_count_once(): void
    {
        $card = $this->card([0 => $this->task('Clue scroll (hard)')], ['requires_approval' => false]);
        $card->squares()->where('position', 0)->update(['required_count' => 3]);

        for ($i = 0; $i < 3; $i++) {
            $this->api()->postJson('/api/plugin/v1/completions', $this->completion('Clue scroll (hard)'))->assertCreated();
        }

        $this->assertSame(1, BingoCompletion::count());
        $this->assertSame(3, TargetProgress::count());
    }

    /**
     * The two modes stack. "Three drops of at least twenty each": a smaller
     * drop is not a qualifying report, so it does not move the count either.
     */
    #[Test]
    public function a_report_under_the_threshold_does_not_count_toward_the_repetitions(): void
    {
        $card = $this->card([0 => $this->task('Soaked page')], ['requires_approval' => false]);
        $card->squares()->where('position', 0)->update(['min_quantity' => 20, 'required_count' => 3]);

        foreach ([19, 19, 19] as $quantity) {
            $this->api()->postJson('/api/plugin/v1/completions', $this->completion('Soaked page', ['quantity' => $quantity]))
                ->assertCreated()
                ->assertJsonPath('claims', []);
        }

        $this->assertSame(0, TargetProgress::count());

        foreach ([20, 25] as $quantity) {
            $this->api()->postJson('/api/plugin/v1/completions', $this->completion('Soaked page', ['quantity' => $quantity]))
                ->assertCreated()
                ->assertJsonPath('claims', []);
        }

        $this->api()->postJson('/api/plugin/v1/completions', $this->completion('Soaked page', ['quantity' => 40]))
            ->assertCreated()
            ->assertJsonPath('claims.0.status', 'APPROVED');

        $this->assertSame(1, BingoCompletion::count());
    }

    /** A tile counts the same way, per player board. */
    #[Test]
    public function a_tile_is_claimed_on_the_last_of_its_repetitions(): void
    {
        $event = $this->event('SNAKES_LADDERS');
        $board = $event->board()->create(['size' => 'SIZE_5X5', 'requires_approval' => false]);
        Tile::create([
            'board_id' => $board->id,
            'position' => 0,
            'type' => 'NORMAL',
            'task_id' => $this->task('Zalcano shard')->id,
            'required_count' => 2,
        ]);
        PlayerBoard::create(['board_id' => $board->id, 'user_id' => $this->player->id, 'current_position' => 0]);

        $this->api()->getJson('/api/plugin/v1/events')->assertJsonPath('events.0.targets.0.required_count', 2);

        $this->api()->postJson('/api/plugin/v1/completions', $this->completion('Zalcano shard', ['context' => ['kill_count' => 11]]))
            ->assertCreated()
            ->assertJsonPath('claims', []);
        $this->assertSame(0, CompletedTile::count());

        $this->api()->postJson('/api/plugin/v1/completions', $this->completion('Zalcano shard', ['context' => ['kill_count' => 12]]))
            ->assertCreated()
            ->assertJsonPath('claims.0.kind', 'tile');
        $this->assertSame(1, CompletedTile::count());
    }

    /**
     * A counted square that moved says so. A square three of five of the way
     * there sitting silent until the fifth kill was the whole complaint.
     */
    #[Test]
    public function a_report_that_advances_a_counted_square_answers_its_progress(): void
    {
        $card = $this->card([0 => $this->task('Zalcano shard')], ['requires_approval' => false]);
        $card->squares()->where('position', 0)->update(['required_count' => 3]);

        $this->api()->postJson('/api/plugin/v1/completions', $this->completion('Zalcano shard', [
            'context' => ['kill_count' => 204],
        ]))
            ->assertCreated()
            ->assertJsonPath('claims', [])
            ->assertJsonPath('progress.0.done', 1)
            ->assertJsonPath('progress.0.required_count', 3)
            ->assertJsonPath('progress.0.name', 'Zalcano shard');

        $this->api()->postJson('/api/plugin/v1/completions', $this->completion('Zalcano shard', [
            'context' => ['kill_count' => 205],
        ]))
            ->assertCreated()
            ->assertJsonPath('progress.0.done', 2);
    }

    /** The last one claims, so it is a claim and not progress. */
    #[Test]
    public function the_claiming_report_answers_no_progress(): void
    {
        $card = $this->card([0 => $this->task('Zalcano shard')], ['requires_approval' => false]);
        $card->squares()->where('position', 0)->update(['required_count' => 2]);

        $this->api()->postJson('/api/plugin/v1/completions', $this->completion('Zalcano shard', [
            'context' => ['kill_count' => 204],
        ]))->assertCreated();

        $this->api()->postJson('/api/plugin/v1/completions', $this->completion('Zalcano shard', [
            'context' => ['kill_count' => 205],
        ]))
            ->assertCreated()
            ->assertJsonPath('claims.0.status', 'APPROVED')
            ->assertJsonPath('progress', []);
    }

    /** A resend of a kill already counted is not news: same total, no progress. */
    #[Test]
    public function a_resent_kill_answers_no_progress(): void
    {
        $card = $this->card([0 => $this->task('Zalcano shard')], ['requires_approval' => false]);
        $card->squares()->where('position', 0)->update(['required_count' => 3]);

        $this->api()->postJson('/api/plugin/v1/completions', $this->completion('Zalcano shard', [
            'context' => ['kill_count' => 204],
        ]))->assertCreated()->assertJsonPath('progress.0.done', 1);

        $this->api()->postJson('/api/plugin/v1/completions', $this->completion('Zalcano shard', [
            'context' => ['kill_count' => 204],
        ]))->assertCreated()->assertJsonPath('progress', []);
    }

    /** A retry of the same report answers what the first one answered. */
    #[Test]
    public function a_duplicate_answers_the_progress_of_the_original(): void
    {
        $card = $this->card([0 => $this->task('Zalcano shard')], ['requires_approval' => false]);
        $card->squares()->where('position', 0)->update(['required_count' => 3]);

        $payload = $this->completion('Zalcano shard', ['context' => ['kill_count' => 204]]);

        $this->api()->postJson('/api/plugin/v1/completions', $payload)->assertCreated();

        $this->api()->postJson('/api/plugin/v1/completions', $payload)
            ->assertOk()
            ->assertJsonPath('duplicate', true)
            ->assertJsonPath('progress.0.done', 1);
    }

    /** Something killed before the event opened is not something done in it. */
    #[Test]
    public function a_report_from_before_the_event_started_counts_for_nothing(): void
    {
        $card = $this->card([0 => $this->task('Zalcano shard')], ['requires_approval' => false]);
        $card->event->update(['start_date' => now()->subDay()]);
        $card->squares()->where('position', 0)->update(['required_count' => 2]);

        $this->api()->postJson('/api/plugin/v1/completions', $this->completion('Zalcano shard', [
            'occurred_at' => now()->subDays(3)->toIso8601String(),
            'context' => ['kill_count' => 204],
        ]))->assertCreated()->assertJsonPath('claims', []);

        $this->assertSame(0, TargetProgress::count());
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
    public function the_report_that_wins_a_card_says_so_and_a_retry_says_the_same(): void
    {
        $whip = $this->task('Abyssal whip');
        $card = $this->card([0 => $whip, 1 => $whip, 2 => $whip], ['requires_approval' => true, 'trust_runelite_completions' => true, 'size' => 3]);
        $report = $this->completion('Abyssal whip');

        $this->api()->postJson('/api/plugin/v1/completions', $report)
            ->assertCreated()
            ->assertJsonCount(1, 'finishes')
            ->assertJsonPath('finishes.0.event_id', $card->event_id)
            ->assertJsonPath('finishes.0.place', 1)
            ->assertJsonPath('finishes.0.team', null);

        $this->api()->postJson('/api/plugin/v1/completions', $report)
            ->assertOk()
            ->assertJsonPath('finishes.0.place', 1);

        $this->api()->postJson('/api/plugin/v1/completions', $this->completion('Abyssal whip'))
            ->assertJsonPath('finishes', []);
    }

    #[Test]
    public function a_running_event_carries_the_accounts_finish(): void
    {
        $whip = $this->task('Abyssal whip');
        $card = $this->card([0 => $whip, 1 => $whip, 2 => $whip], ['requires_approval' => true, 'trust_runelite_completions' => true, 'size' => 3]);

        $this->api()->getJson('/api/plugin/v1/events')->assertJsonPath('events.0.finish', null);

        $this->api()->postJson('/api/plugin/v1/completions', $this->completion('Abyssal whip'));

        $this->api()->getJson('/api/plugin/v1/events')
            ->assertJsonPath('events.0.id', $card->event_id)
            ->assertJsonPath('events.0.finish.place', 1)
            ->assertJsonPath('events.0.finish.provisional', false);
    }

    #[Test]
    public function events_that_are_not_running_are_listed_apart_and_watch_nothing(): void
    {
        $this->card([0 => $this->task('Abyssal whip')]);
        $paused = $this->card([0 => $this->task('Dragon bones')])->event;
        $paused->forceFill(['title' => 'Paused bingo', 'paused_at' => now()])->save();
        $this->event('BINGO', ['title' => 'Next week', 'start_date' => now()->addWeek(), 'end_date' => now()->addWeeks(2)]);
        $this->event('SKILL_RACE', ['title' => 'Last week', 'metric' => 'mining', 'start_date' => now()->subWeeks(2), 'end_date' => now()->subDays(3)]);
        $this->event('BINGO', ['title' => 'Long ago', 'start_date' => now()->subMonths(3), 'end_date' => now()->subMonths(2)]);

        $response = $this->api()->getJson('/api/plugin/v1/events')->assertOk();

        $this->assertSame(['Clan BINGO'], array_column($response->json('events'), 'title'));
        $this->assertEqualsCanonicalizing(
            ['Paused bingo' => 'paused', 'Next week' => 'upcoming', 'Last week' => 'ended'],
            array_column($response->json('other_events'), 'status', 'title'),
        );
        $this->assertNotContains('dragon bones', $response->json('watch'));
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
            ->assertJsonPath('message', trans('plugin.api_rsn_mismatch', ['rsn' => 'Zezima', 'expected' => 'Iron Sample']));

        $this->assertSame(0, BingoCompletion::count());
        $this->assertSame(0, PluginCompletion::count());
    }

    #[Test]
    public function the_rsn_compares_like_the_game_does(): void
    {
        $this->card([0 => $this->task('Abyssal whip')]);

        $this->api()->postJson('/api/plugin/v1/completions', $this->completion('Abyssal whip', ['rsn' => 'iron_sample']))
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

    // -------------------------------------------------------- plausibility

    /** A card that reviews but trusts RuneLite, one distinct drop per square, so each report can claim. */
    private function trustingCard(): BingoCard
    {
        return $this->card(
            [0 => $this->task('Tanzanite fang'), 1 => $this->task('Magic fang'), 2 => $this->task('Serpentine visage')],
            ['requires_approval' => true, 'trust_runelite_completions' => true],
        );
    }

    /** One Zulrah drop at a kill count and a moment, so a test reads as the sequence it is. */
    private function kill(string $drop, int $killCount, $occurredAt = null, array $context = []): TestResponse
    {
        return $this->api()->postJson('/api/plugin/v1/completions', $this->completion($drop, [
            'kind' => 'npc_kill',
            'occurred_at' => ($occurredAt ?? now())->toIso8601String(),
            'context' => ['npc_name' => 'Zulrah', 'kill_count' => $killCount, ...$context],
        ]));
    }

    #[Test]
    public function a_report_that_hangs_together_is_approved_on_a_trusting_card(): void
    {
        $this->trustingCard();

        $this->kill('Tanzanite fang', 217, now()->subMinutes(2))->assertCreated()->assertJsonPath('claims.0.status', 'APPROVED');
        $this->kill('Magic fang', 218, now()->subMinute())->assertCreated()->assertJsonPath('claims.0.status', 'APPROVED');
        $this->kill('Serpentine visage', 218)->assertCreated()->assertJsonPath('claims.0.status', 'APPROVED');

        $this->assertSame(0, PluginCompletion::whereNotNull('doubts')->count());
    }

    #[Test]
    public function a_kill_count_that_goes_down_is_pending_with_a_reason(): void
    {
        $this->trustingCard();

        $this->kill('Tanzanite fang', 217, now()->subMinutes(2))->assertJsonPath('claims.0.status', 'APPROVED');
        $this->kill('Magic fang', 200, now()->subMinute())
            ->assertCreated()
            ->assertJsonPath('claims.0.status', 'PENDING');

        $this->assertSame(['kill_count_dropped'], PluginCompletion::whereNotNull('doubts')->sole()->doubts);
    }

    #[Test]
    public function a_kill_count_that_jumps_is_pending_with_a_reason(): void
    {
        $this->trustingCard();

        $this->kill('Tanzanite fang', 217, now()->subMinute())->assertJsonPath('claims.0.status', 'APPROVED');
        $this->kill('Magic fang', 5000)->assertCreated()->assertJsonPath('claims.0.status', 'PENDING');

        $this->assertSame(['kill_count_jumped'], PluginCompletion::whereNotNull('doubts')->sole()->doubts);
    }

    #[Test]
    public function the_time_between_reports_is_how_far_a_kill_count_may_rise(): void
    {
        $this->trustingCard();

        $this->kill('Tanzanite fang', 217, now()->subHour())->assertJsonPath('claims.0.status', 'APPROVED');
        // An hour is 720 kills at five seconds each: 183 more is possible,
        // the same step a minute later is not.
        $this->kill('Magic fang', 400)->assertJsonPath('claims.0.status', 'APPROVED');
        $this->kill('Serpentine visage', 900, now()->addSeconds(30))->assertJsonPath('claims.0.status', 'PENDING');
    }

    #[Test]
    public function the_first_report_for_a_boss_has_nothing_to_contradict(): void
    {
        $this->trustingCard();

        $this->kill('Tanzanite fang', 9000)->assertCreated()->assertJsonPath('claims.0.status', 'APPROVED');
    }

    #[Test]
    public function a_kill_count_is_judged_per_boss_and_per_player(): void
    {
        $this->trustingCard();

        $this->kill('Tanzanite fang', 900, now()->subMinute())->assertJsonPath('claims.0.status', 'APPROVED');
        $this->kill('Magic fang', 12, now(), ['npc_name' => 'Vorkath'])->assertJsonPath('claims.0.status', 'APPROVED');

        $other = User::factory()->create(['osrs_username' => 'Someone']);
        EventParticipant::create(['event_id' => BingoCard::first()->event_id, 'user_id' => $other->id]);
        $this->withHeader('Authorization', 'Bearer '.PluginToken::issueFor($other))
            ->postJson('/api/plugin/v1/completions', [
                ...$this->completion('Serpentine visage', ['rsn' => 'Someone']),
                'context' => ['npc_name' => 'Zulrah', 'kill_count' => 3],
            ])
            ->assertCreated()
            ->assertJsonPath('claims.0.status', 'APPROVED');
    }

    #[Test]
    public function reports_arriving_out_of_order_are_judged_by_when_they_happened(): void
    {
        $this->trustingCard();

        $this->kill('Tanzanite fang', 219, now())->assertJsonPath('claims.0.status', 'APPROVED');
        $this->kill('Magic fang', 218, now()->subSeconds(20))->assertJsonPath('claims.0.status', 'APPROVED');
        $this->kill('Serpentine visage', 221, now()->subSeconds(10))->assertJsonPath('claims.0.status', 'PENDING');

        $this->assertSame(['kill_count_dropped'], PluginCompletion::whereNotNull('doubts')->sole()->doubts);
    }

    #[Test]
    public function a_time_in_the_future_is_pending_but_a_small_clock_difference_is_not(): void
    {
        $this->trustingCard();

        $this->api()->postJson('/api/plugin/v1/completions', $this->completion('Tanzanite fang', ['occurred_at' => now()->addSeconds(60)->toIso8601String()]))
            ->assertCreated()
            ->assertJsonPath('claims.0.status', 'APPROVED');

        $this->api()->postJson('/api/plugin/v1/completions', $this->completion('Magic fang', ['occurred_at' => now()->addHour()->toIso8601String()]))
            ->assertCreated()
            ->assertJsonPath('claims.0.status', 'PENDING');

        $this->assertSame(['occurred_in_future'], PluginCompletion::whereNotNull('doubts')->sole()->doubts);
    }

    #[Test]
    public function a_report_too_long_after_it_happened_is_pending(): void
    {
        $this->trustingCard();

        $this->api()->postJson('/api/plugin/v1/completions', $this->completion('Tanzanite fang', ['occurred_at' => now()->subHours(5)->toIso8601String()]))
            ->assertCreated()
            ->assertJsonPath('claims.0.status', 'APPROVED');

        $this->api()->postJson('/api/plugin/v1/completions', $this->completion('Magic fang', ['occurred_at' => now()->subHours(7)->toIso8601String()]))
            ->assertCreated()
            ->assertJsonPath('claims.0.status', 'PENDING');

        $this->assertSame(['occurred_too_old'], PluginCompletion::whereNotNull('doubts')->sole()->doubts);
    }

    #[Test]
    public function a_doubt_never_refuses_a_report_and_leaves_a_board_without_review_alone(): void
    {
        $this->card([0 => $this->task('Tanzanite fang')], ['requires_approval' => false]);

        $this->api()->postJson('/api/plugin/v1/completions', $this->completion('Tanzanite fang', ['occurred_at' => now()->addDay()->toIso8601String()]))
            ->assertCreated()
            ->assertJsonPath('claims.0.status', 'APPROVED');

        $this->assertSame(['occurred_in_future'], PluginCompletion::sole()->doubts);
    }

    #[Test]
    public function a_doubt_on_a_snakes_and_ladders_tile_is_pending_too(): void
    {
        $event = $this->event('SNAKES_LADDERS');
        $board = $event->board()->create(['size' => 'SIZE_5X5', 'requires_approval' => true, 'trust_runelite_completions' => true]);
        Tile::create(['board_id' => $board->id, 'position' => 0, 'type' => 'NORMAL', 'task_id' => $this->task('Tanzanite fang')->id]);
        PlayerBoard::create(['board_id' => $board->id, 'user_id' => $this->player->id, 'current_position' => 0]);

        $this->api()->postJson('/api/plugin/v1/completions', $this->completion('Tanzanite fang', ['occurred_at' => now()->addDay()->toIso8601String()]))
            ->assertCreated();

        $this->assertSame('PENDING', CompletedTile::sole()->status);
    }

    #[Test]
    public function the_reasons_reach_the_host_reviewing_the_claim(): void
    {
        $this->trustingCard();

        $this->kill('Tanzanite fang', 217, now()->subMinute());
        $this->kill('Magic fang', 5000);

        $claim = BingoCompletion::where('status', 'PENDING')->sole();

        $this->assertSame(['kill_count_jumped'], $claim->pluginCompletion->reviewContext()['doubts']);
        $this->assertSame([], BingoCompletion::where('status', 'APPROVED')->sole()->pluginCompletion->reviewContext()['doubts']);
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
