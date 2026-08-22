<?php

namespace Tests\Feature;

use App\Events\Channels\BingoChannel;
use App\Events\Channels\EventChannelResolver;
use App\Events\Channels\MetricRaceChannel;
use App\Events\Channels\SnakesLaddersChannel;
use App\Models\BingoCompletion;
use App\Models\Board;
use App\Models\Event;
use App\Models\EventStanding;
use App\Models\PlayerBoard;
use App\Models\User;
use App\Services\BingoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The live channel architecture: one per event type, resolved by type, with
 * the controller knowing nothing about any of them.
 *
 * The fingerprint contract is what these mostly cover, because it is the part
 * that is easy to get subtly wrong in a way nothing surfaces: too sensitive
 * and every open browser is woken by a timestamp nobody can see, too blunt
 * and a real change never arrives.
 */
class EventStreamTest extends TestCase
{
    use RefreshDatabase;

    private function resolver(): EventChannelResolver
    {
        return app(EventChannelResolver::class);
    }

    private function event(string $type, array $attributes = []): Event
    {
        return Event::create([
            'title' => "A {$type} event",
            'type' => $type,
            'mode' => 'SOLO',
            'access_mode' => 'OPEN',
            'is_listed' => true,
            ...$attributes,
        ]);
    }

    private function player(string $name = 'Pondake'): User
    {
        return User::factory()->create(['osrs_username' => $name]);
    }

    // ----------------------------------------------------------- resolving

    #[Test]
    public function every_event_type_resolves_to_a_channel(): void
    {
        $expected = [
            'SNAKES_LADDERS' => SnakesLaddersChannel::class,
            'SKILL_RACE' => MetricRaceChannel::class,
            'DROP_RACE' => MetricRaceChannel::class,
            'BINGO' => BingoChannel::class,
        ];

        foreach ($expected as $type => $class) {
            $this->assertInstanceOf($class, $this->resolver()->for($this->event($type)), $type);
        }
    }

    /**
     * If a type is added without a channel this is what says so, rather than
     * a viewer holding an idle connection open forever.
     */
    #[Test]
    public function every_available_type_has_one(): void
    {
        foreach (Event::availableTypes() as $type) {
            $this->assertTrue($this->resolver()->has($this->event($type)), $type);
        }
    }

    #[Test]
    public function each_channel_names_its_own_message(): void
    {
        $this->assertSame('players', $this->resolver()->for($this->event('SNAKES_LADDERS'))->name());
        $this->assertSame('standings', $this->resolver()->for($this->event('SKILL_RACE'))->name());
        $this->assertSame('bingo', $this->resolver()->for($this->event('BINGO'))->name());
    }

    // -------------------------------------------------------- access rules

    #[Test]
    public function the_stream_requires_a_login(): void
    {
        $this->get("/events/{$this->event('BINGO')->id}/stream")->assertRedirect();
    }

    /**
     * The headers, which are the difference between a live stream and one
     * long delivery at the end.
     *
     * `X-Accel-Buffering: no` is the one that matters in production and
     * cannot be caught locally: nginx buffers a proxied response by default,
     * which holds every event until the connection closes — so the page
     * would sit silent for 45 seconds and then receive everything at once,
     * looking exactly like a stream that does not work. Nothing else in the
     * suite would notice it going missing.
     *
     * Asserted without reading the body: consuming it would block for the
     * stream's full 45 seconds.
     */
    #[Test]
    public function the_stream_announces_itself_as_one(): void
    {
        $event = $this->event('BINGO');
        $event->bingoCard()->create(['size' => 3]);

        $response = $this->actingAs($this->player())->get("/events/{$event->id}/stream");

        $response->assertOk();
        $response->assertHeader('content-type', 'text/event-stream; charset=utf-8');
        $response->assertHeader('cache-control', 'no-cache, no-transform, private');
        $response->assertHeader('x-accel-buffering', 'no');
    }

    #[Test]
    public function the_stream_is_refused_without_access(): void
    {
        $event = $this->event('BINGO', ['access_mode' => 'INVITE']);

        $this->actingAs($this->player())->get("/events/{$event->id}/stream")->assertForbidden();
    }

    // ------------------------------------------------- bingo fingerprints

    #[Test]
    public function a_bingo_fingerprint_changes_when_a_square_is_claimed(): void
    {
        $event = $this->event('BINGO');
        $card = $event->bingoCard()->create(['size' => 3]);
        app(BingoService::class)->ensureSquares($card);
        $channel = $this->resolver()->for($event);

        $before = $channel->fingerprint($event->fresh());

        BingoCompletion::create([
            'bingo_square_id' => $card->squares()->first()->id,
            'user_id' => $this->player()->id,
            'status' => 'PENDING',
        ]);

        $this->assertNotSame($before, $channel->fingerprint($event->fresh()));
    }

    /** Approving a claim is a visible change, so it must wake the stream. */
    #[Test]
    public function a_bingo_fingerprint_changes_when_a_claim_is_reviewed(): void
    {
        $event = $this->event('BINGO');
        $card = $event->bingoCard()->create(['size' => 3]);
        app(BingoService::class)->ensureSquares($card);

        $claim = BingoCompletion::create([
            'bingo_square_id' => $card->squares()->first()->id,
            'user_id' => $this->player()->id,
            'status' => 'PENDING',
        ]);

        $channel = $this->resolver()->for($event);
        $before = $channel->fingerprint($event->fresh());

        $claim->update(['status' => 'APPROVED']);

        $this->assertNotSame($before, $channel->fingerprint($event->fresh()));
    }

    /** An author's edit should reach everyone looking at the card. */
    #[Test]
    public function a_bingo_fingerprint_changes_when_a_square_is_edited(): void
    {
        $event = $this->event('BINGO');
        $card = $event->bingoCard()->create(['size' => 3]);
        app(BingoService::class)->ensureSquares($card);
        $channel = $this->resolver()->for($event);

        $before = $channel->fingerprint($event->fresh());

        $card->squares()->first()->update(['title_override' => 'Kill 50 cows']);

        $this->assertNotSame($before, $channel->fingerprint($event->fresh()));
    }

    /**
     * The card's own settings are part of what everybody is looking at.
     *
     * Bingo.vue takes `winLines` off the payload so "a host changing which
     * shapes count mid-event reaches every open card" — but the fingerprint
     * only watched the claims and the squares, so that change woke nobody and
     * the comment was describing something that did not happen. The win
     * condition is worse: it decides the standings, so an open card would
     * have gone on scoring by a rule that had been switched off.
     */
    #[Test]
    public function a_bingo_fingerprint_changes_when_the_rules_do(): void
    {
        $event = $this->event('BINGO');
        $card = $event->bingoCard()->create([
            'size' => 3,
            'win_condition' => 'LINE',
            'win_lines' => ['ROW', 'COLUMN', 'DIAGONAL'],
            'line_bonus' => 0,
        ]);
        app(BingoService::class)->ensureSquares($card);
        $channel = $this->resolver()->for($event);

        foreach ([
            'win_lines' => ['ROW'],
            'win_condition' => 'FULL_HOUSE',
            'line_bonus' => 5,
        ] as $field => $value) {
            $before = $channel->fingerprint($event->fresh());

            $card->update([$field => $value]);

            $this->assertNotSame($before, $channel->fingerprint($event->fresh()), $field);
        }
    }

    /** And the payload carries them, or there would be nothing to send. */
    #[Test]
    public function a_bingo_payload_carries_the_rules_and_the_standings(): void
    {
        $event = $this->event('BINGO');
        $card = $event->bingoCard()->create(['size' => 3, 'win_lines' => ['ROW']]);
        app(BingoService::class)->ensureSquares($card);

        BingoCompletion::create([
            'bingo_square_id' => $card->squares()->first()->id,
            'user_id' => $this->player()->id,
            'status' => 'APPROVED',
        ]);

        $payload = $this->resolver()->for($event)->payload($event->fresh());

        $this->assertSame(['ROW'], $payload['winLines']);
        $this->assertCount(1, $payload['standings']);
        $this->assertCount(9, $payload['squares']);
        // Who holds which square is public — an approved claim is already in
        // the standings.
        $this->assertArrayHasKey('approvedBy', $payload);
    }

    /**
     * The other half of the contract. A host re-reviewing a claim to the same
     * verdict rewrites updated_at without changing anything anyone can see,
     * and must not wake every open browser.
     */ /**
     * The other half of the contract. A host re-reviewing a claim to the same
     * verdict rewrites updated_at without changing anything anyone can see,
     * and must not wake every open browser.
     */
    #[Test]
    public function a_bingo_fingerprint_is_stable_when_nothing_visible_changed(): void
    {
        $event = $this->event('BINGO');
        $card = $event->bingoCard()->create(['size' => 3]);
        app(BingoService::class)->ensureSquares($card);

        $claim = BingoCompletion::create([
            'bingo_square_id' => $card->squares()->first()->id,
            'user_id' => $this->player()->id,
            'status' => 'APPROVED',
        ]);

        $channel = $this->resolver()->for($event);
        $before = $channel->fingerprint($event->fresh());

        $claim->touch();
        $claim->update(['status' => 'APPROVED', 'review_note' => null]);

        $this->assertSame($before, $channel->fingerprint($event->fresh()));
    }

    // ---------------------------------------- snakes & ladders fingerprints

    #[Test]
    public function a_board_fingerprint_changes_when_a_player_moves(): void
    {
        $event = $this->event('SNAKES_LADDERS');
        $board = Board::create(['event_id' => $event->id, 'size' => 'SIZE_5X5']);
        $playerBoard = PlayerBoard::create([
            'user_id' => $this->player()->id,
            'board_id' => $board->id,
            'current_position' => 0,
        ]);

        $channel = $this->resolver()->for($event);
        $before = $channel->fingerprint($event);

        $playerBoard->update(['current_position' => 3]);

        $this->assertNotSame($before, $channel->fingerprint($event));
    }

    #[Test]
    public function a_board_payload_carries_every_player(): void
    {
        $event = $this->event('SNAKES_LADDERS');
        $board = Board::create(['event_id' => $event->id, 'size' => 'SIZE_5X5']);
        PlayerBoard::create(['user_id' => $this->player('One')->id, 'board_id' => $board->id, 'current_position' => 4]);
        PlayerBoard::create(['user_id' => $this->player('Two')->id, 'board_id' => $board->id, 'current_position' => 9]);

        $payload = $this->resolver()->for($event)->payload($event);

        $this->assertCount(2, $payload['players']);
        // Ordered by position, furthest along first.
        $this->assertSame(9, $payload['players'][0]['current_position']);
    }

    // -------------------------------------------------- race fingerprints

    #[Test]
    public function a_race_fingerprint_changes_when_a_score_does(): void
    {
        $event = $this->event('SKILL_RACE', [
            'metric' => 'mining',
            'start_date' => Carbon::now()->subWeek(),
            'end_date' => Carbon::now()->addWeek(),
        ]);

        $standing = EventStanding::create([
            'event_id' => $event->id,
            'user_id' => $this->player()->id,
            'username' => 'Pondake',
            'gained' => 100,
            'synced_at' => Carbon::now(),
        ]);

        $channel = $this->resolver()->for($event);
        $before = $channel->fingerprint($event);

        $standing->update(['gained' => 200]);

        $this->assertNotSame($before, $channel->fingerprint($event));
    }

    /** A sync that finds no change rewrites synced_at and nothing else. */
    #[Test]
    public function a_race_fingerprint_ignores_a_resync_that_changed_nothing(): void
    {
        $event = $this->event('SKILL_RACE', [
            'metric' => 'mining',
            'start_date' => Carbon::now()->subWeek(),
            'end_date' => Carbon::now()->addWeek(),
        ]);

        $standing = EventStanding::create([
            'event_id' => $event->id,
            'user_id' => $this->player()->id,
            'username' => 'Pondake',
            'gained' => 100,
            'synced_at' => Carbon::now(),
        ]);

        $channel = $this->resolver()->for($event);
        $before = $channel->fingerprint($event);

        $standing->update(['synced_at' => Carbon::now()->addHour()]);

        $this->assertSame($before, $channel->fingerprint($event));
    }
}
