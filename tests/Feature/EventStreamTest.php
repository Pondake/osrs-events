<?php

namespace Tests\Feature;

use App\Events\Channels\BingoChannel;
use App\Events\Channels\EventChannelResolver;
use App\Events\Channels\MetricRaceChannel;
use App\Events\Channels\SnakesLaddersChannel;
use App\Models\BingoCard;
use App\Models\BingoCompletion;
use App\Models\Board;
use App\Models\BoardAuthor;
use App\Models\Event;
use App\Models\EventStanding;
use App\Models\PlayerBoard;
use App\Models\Tile;
use App\Models\User;
use App\Services\BingoService;
use App\Support\EventCard;
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

        // One instance throughout, deliberately. Handing the channel a fresh
        // model on every call is not what the stream does — it loads the
        // event once and asks the same object for 45 seconds — and a test
        // that re-reads is a test that cannot fail on staleness.
        //
        // Size and approval are in here because they were not: the grid is
        // drawn from the size, and a resize that happened to keep the same
        // number of squares changed nothing the channel could see.
        foreach ([
            'win_lines' => ['ROW'],
            'win_condition' => 'FULL_HOUSE',
            'line_bonus' => 5,
            'requires_approval' => false,
            'size' => 4,
        ] as $field => $value) {
            $before = $channel->fingerprint($event);

            $card->update([$field => $value]);

            $this->assertNotSame($before, $channel->fingerprint($event), $field);
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
    // -------------------------------------------------------- board tiles

    /**
     * A host editing the board mid-event is as visible as a player moving on
     * it, and it reached nobody: the second browser kept the old board — old
     * tasks, old ladders — until somebody reloaded. The bingo card streamed
     * its squares from the first version; this is the same thing for the same
     * reason.
     */
    #[Test]
    public function a_board_fingerprint_changes_when_a_tile_is_edited(): void
    {
        $event = $this->event('SNAKES_LADDERS');
        $board = Board::create(['event_id' => $event->id, 'size' => 'SIZE_5X5']);
        $tile = Tile::create(['board_id' => $board->id, 'position' => 4, 'type' => 'NORMAL', 'title_override' => 'Kill a goblin']);

        $channel = $this->resolver()->for($event);
        $before = $channel->fingerprint($event);

        $tile->update(['title_override' => 'Kill two goblins']);

        $this->assertNotSame($before, $channel->fingerprint($event));
    }

    /** Specifically the arrows — a ladder that moved has to move everywhere. */
    #[Test]
    public function a_board_fingerprint_changes_when_a_ladder_moves(): void
    {
        $event = $this->event('SNAKES_LADDERS');
        $board = Board::create(['event_id' => $event->id, 'size' => 'SIZE_5X5']);
        $tile = Tile::create(['board_id' => $board->id, 'position' => 2, 'type' => 'LADDER', 'target_position' => 9]);

        $channel = $this->resolver()->for($event);
        $before = $channel->fingerprint($event);

        $tile->update(['target_position' => 14]);

        $this->assertNotSame($before, $channel->fingerprint($event));
    }

    #[Test]
    public function a_board_payload_carries_the_tiles(): void
    {
        $event = $this->event('SNAKES_LADDERS');
        $board = Board::create(['event_id' => $event->id, 'size' => 'SIZE_5X5']);
        Tile::create(['board_id' => $board->id, 'position' => 2, 'type' => 'LADDER', 'target_position' => 9]);

        $payload = $this->resolver()->for($event)->payload($event);

        $this->assertCount(1, $payload['tiles']);
        $this->assertSame(9, $payload['tiles'][0]['target_position']);
    }

    // ------------------------------------------------------- event edits

    /**
     * The event itself, on every channel. Moving a race's end date reached
     * nobody watching it — reported as "event changes don't seem to come
     * through at all", and they didn't.
     */
    #[Test]
    public function every_channel_notices_the_event_being_edited(): void
    {
        foreach (['SNAKES_LADDERS', 'BINGO', 'SKILL_RACE'] as $type) {
            $event = $this->event($type, $type === 'SKILL_RACE' ? [
                'metric' => 'mining',
                'start_date' => Carbon::now()->subWeek(),
                'end_date' => Carbon::now()->addWeek(),
            ] : ['end_date' => Carbon::now()->addWeek()]);

            $channel = $this->resolver()->for($event);
            $before = $channel->fingerprint($event);

            // Written past the instance the channel is holding, because that
            // is the only way an edit ever reaches an open stream: the host
            // saving is a different request with a different copy of the row.
            Event::where('id', $event->id)->update(['end_date' => Carbon::now()->addWeeks(3)]);

            $this->assertNotSame($before, $channel->fingerprint($event), "{$type} ignored an edit to its own dates");
        }
    }

    /**
     * The event travels with the payload, not just a version to go and ask
     * about.
     *
     * It was a version at first, on the reasoning that the pages render these
     * details in too many places to patch by hand. That cost a second request
     * — and a second request is exactly what a page watching a stream cannot
     * get cheaply: on the single-worker dev server it queues behind the very
     * stream that triggered it, measured at 29 seconds from edit to screen.
     * The details ride along now, and the page swaps one card for the other.
     */
    #[Test]
    public function every_payload_carries_the_event_itself(): void
    {
        foreach (['SNAKES_LADDERS', 'BINGO', 'SKILL_RACE'] as $type) {
            $event = $this->event($type, $type === 'SKILL_RACE' ? ['metric' => 'mining'] : []);
            $channel = $this->resolver()->for($event);

            $this->assertArrayHasKey('event_version', $channel->payload($event), "{$type} sends no event version");

            // Behind the instance the channel is holding, the way a host's
            // save always reaches an open stream.
            Event::where('id', $event->id)->update(['title' => 'Renamed by the host']);

            $card = $channel->payload($event)['event'] ?? null;

            $this->assertNotNull($card, "{$type} sends no event card");
            $this->assertSame('Renamed by the host', $card['title'], "{$type} sent a stale card");
        }
    }

    /**
     * The card the channel sends and the card the page was rendered with have
     * to be the same shape, or the page swaps in something with holes in it.
     * `metricKind` is the one that bit: the race page reads it to decide
     * whether it is counting XP or kills, and it used to be added by the
     * controller alone.
     */
    #[Test]
    public function the_streamed_card_matches_the_rendered_one(): void
    {
        $event = $this->event('SKILL_RACE', ['metric' => 'mining']);

        $rendered = EventCard::for($event);
        $streamed = $this->resolver()->for($event)->payload($event)['event'];

        $this->assertSame(array_keys($rendered), array_keys($streamed));
        $this->assertSame('skill', $streamed['metricKind']);
    }

    /**
     * A rename is an edit; a write that changes nothing on screen is not.
     * Both directions, because a fingerprint that is too eager wakes every
     * open browser for nothing.
     */
    #[Test]
    public function an_event_fingerprint_is_stable_when_nothing_visible_changed(): void
    {
        $event = $this->event('BINGO');
        $channel = $this->resolver()->for($event);

        $before = $channel->fingerprint($event);
        Event::where('id', $event->id)->update(['updated_at' => Carbon::now()->addHour()]);

        $this->assertSame($before, $channel->fingerprint($event));

        Event::where('id', $event->id)->update(['title' => 'A different name entirely']);

        $this->assertNotSame($before, $channel->fingerprint($event));
    }

    /**
     * The bug this whole rule exists for.
     *
     * The stream loads the event when the connection opens and then asks the
     * channel the same question every few seconds for the next 45. Read the
     * model's own attributes and the answer is frozen at whenever that viewer
     * connected — so an edit only surfaced when the connection turned over,
     * three quarters of a minute later, which looked like the dev server
     * being slow rather than the fingerprint being wrong.
     *
     * Every earlier test missed it by handing the channel a fresh instance,
     * or by editing through the instance the channel was holding. This one
     * does neither.
     */
    #[Test]
    public function a_channel_reads_the_event_as_it_is_now_not_as_it_was_loaded(): void
    {
        foreach (['SNAKES_LADDERS', 'BINGO', 'SKILL_RACE'] as $type) {
            $event = $this->event($type, $type === 'SKILL_RACE' ? ['metric' => 'mining'] : []);
            $channel = $this->resolver()->for($event);

            // The instance is warmed first, the way one poll warms it for the
            // next: whatever it caches now is what a stale channel keeps.
            $before = $channel->fingerprint($event);

            Event::where('id', $event->id)->update(['title' => 'Renamed by the host']);

            $this->assertNotSame($before, $channel->fingerprint($event), "{$type} answered from a stale model");
        }
    }

    /** The same staleness one level down: the card's rules and the board's tiles. */
    #[Test]
    public function a_channel_reads_the_payload_as_it_is_now_too(): void
    {
        $bingo = $this->event('BINGO');
        $card = $bingo->bingoCard()->create(['size' => 3, 'win_condition' => 'LINE', 'win_lines' => ['ROW'], 'line_bonus' => 0]);
        app(BingoService::class)->ensureSquares($card);

        $bingoChannel = $this->resolver()->for($bingo);
        $bingoBefore = $bingoChannel->fingerprint($bingo);

        BingoCard::where('id', $card->id)->update(['win_condition' => 'FULL_HOUSE']);

        $this->assertNotSame($bingoBefore, $bingoChannel->fingerprint($bingo), 'the card was read from a stale relation');

        $board = $this->event('SNAKES_LADDERS');
        $boardRow = Board::create(['event_id' => $board->id, 'size' => 'SIZE_5X5']);
        $tile = Tile::create(['board_id' => $boardRow->id, 'position' => 2, 'type' => 'LADDER', 'target_position' => 9]);

        $boardChannel = $this->resolver()->for($board);
        $boardBefore = $boardChannel->fingerprint($board);

        Tile::where('id', $tile->id)->update(['target_position' => 14]);

        $this->assertNotSame($boardBefore, $boardChannel->fingerprint($board), 'the board was read from a stale relation');
    }
    // ------------------------------------------------------- what it leaks

    /**
     * The payload carries the hosts now, and `User` hides only password and
     * remember_token — so a bare `authors.user` publishes every host's email
     * address. That exact leak shipped once, on the event pages and the board
     * cards, which is why the card builder names its columns.
     *
     * Asserted against the encoded payload rather than the array, because the
     * question is what goes down the wire.
     */
    #[Test]
    public function no_channel_sends_a_host_email_address(): void
    {
        foreach (['SNAKES_LADDERS', 'BINGO', 'SKILL_RACE'] as $type) {
            $event = $this->event($type, $type === 'SKILL_RACE' ? ['metric' => 'mining'] : []);
            $email = strtolower($type).'@example.test';
            $host = User::factory()->create(['email' => $email, 'nickname' => "Host of {$type}"]);
            BoardAuthor::create(['event_id' => $event->id, 'user_id' => $host->id, 'is_owner' => true]);

            $json = json_encode($this->resolver()->for($event)->payload($event));

            // The name has to be in there, or this proves nothing: a payload
            // that carries no host at all would pass the assertion below
            // while saying nothing about what happens when it does.
            $this->assertStringContainsString("Host of {$type}", $json, "{$type} sends no host at all");
            $this->assertStringNotContainsString($email, $json, "{$type} published a host email");
        }
    }

    // ------------------------------------------------------ claim verdicts

    /**
     * A ruling on a claim is the one change a shared channel cannot deliver:
     * the verdict, the note and whether it completed a line are yours alone.
     * So the payload says only *that* claims changed, and the page fetches
     * its own copy.
     *
     * Before this, only hosts refreshed. A player watched the standings award
     * them points while their own square still read "waiting for review" —
     * reported as a card rendering half pending, half approved.
     */
    #[Test]
    public function the_bingo_payload_says_when_a_claim_was_ruled_on(): void
    {
        $event = $this->event('BINGO');
        $card = $event->bingoCard()->create(['size' => 3, 'win_lines' => ['ROW']]);
        app(BingoService::class)->ensureSquares($card);
        $square = $card->squares()->orderBy('position')->firstOrFail();

        $claim = BingoCompletion::create([
            'bingo_square_id' => $square->id,
            'user_id' => $this->player()->id,
            'status' => 'PENDING',
        ]);

        $channel = $this->resolver()->for($event);
        $before = $channel->payload($event)['claims_version'];

        BingoCompletion::where('id', $claim->id)->update(['status' => 'APPROVED']);

        $this->assertNotSame($before, $channel->payload($event)['claims_version']);
    }

    /**
     * And says nothing when a square was merely edited — that reaches every
     * viewer over the channel already, so making them each fetch their own
     * claims for it would be a request per viewer for nothing.
     */
    #[Test]
    public function the_claims_version_ignores_a_square_being_edited(): void
    {
        $event = $this->event('BINGO');
        $card = $event->bingoCard()->create(['size' => 3, 'win_lines' => ['ROW']]);
        app(BingoService::class)->ensureSquares($card);

        $channel = $this->resolver()->for($event);
        $before = $channel->payload($event)['claims_version'];

        $card->squares()->orderBy('position')->firstOrFail()->update(['title_override' => 'Something else']);

        $this->assertSame($before, $channel->payload($event)['claims_version']);
    }

    /**
     * The card's own settings travel with it. Its size drives the grid, so a
     * host resizing one mid-event sent every other viewer 36 squares to draw
     * in five columns.
     */
    #[Test]
    public function the_bingo_payload_carries_the_cards_settings(): void
    {
        $event = $this->event('BINGO');
        $card = $event->bingoCard()->create(['size' => 3, 'win_condition' => 'LINE', 'win_lines' => ['ROW'], 'line_bonus' => 0]);
        app(BingoService::class)->ensureSquares($card);

        $channel = $this->resolver()->for($event);
        $channel->payload($event);

        BingoCard::where('id', $card->id)->update(['size' => 5, 'requires_approval' => false]);
        app(BingoService::class)->ensureSquares($card->fresh());

        $settings = $channel->payload($event)['event']['card'];

        $this->assertSame(5, $settings['size']);
        $this->assertFalse($settings['requiresApproval']);
    }
}
