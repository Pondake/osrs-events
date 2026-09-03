<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\BingoCard;
use App\Models\BingoCompletion;
use App\Models\BingoSquare;
use App\Models\Board;
use App\Models\BoardAuthor;
use App\Models\BoardTeam;
use App\Models\CompletedTile;
use App\Models\Event;
use App\Models\EventFinish;
use App\Models\EventParticipant;
use App\Models\PlayerBoard;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\Tile;
use App\Models\User;
use App\Notifications\EventStatusChanged;
use App\Services\BingoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Finishing an event, and what that is allowed to do to it.
 *
 * None of this existed. Completing the last tile flipped a ref in the
 * browser and showed a modal that a refresh erased; the finisher kept a dice
 * button that could only move them nowhere; nobody else was told; and second
 * place could not be worked out afterwards because no timestamp was written.
 *
 * The questions worth asking here are the ones where the two halves of the
 * feature meet: a finish is recorded *and* it may close the event, on a
 * board where a claim only counts once a host has approved it, in both event
 * types, and in both directions — because a rejection or a snake can take a
 * finish back and a podium that cannot be corrected is worse than none.
 */
class EventFinishTest extends TestCase
{
    use RefreshDatabase;

    /** A Snakes & Ladders event with a full 5x5 grid. */
    private function board(array $attributes = [], string $mode = 'SOLO'): Event
    {
        $event = Event::create([
            'title' => 'Board night',
            'type' => 'SNAKES_LADDERS',
            'mode' => $mode,
            'access_mode' => 'OPEN',
            'is_listed' => true,
            ...$attributes,
        ]);

        $board = Board::create([
            'event_id' => $event->id,
            'size' => 'SIZE_5X5',
            'requires_approval' => $attributes['requires_approval'] ?? false,
        ]);

        foreach (range(0, 24) as $position) {
            Tile::create(['board_id' => $board->id, 'position' => $position, 'type' => 'NORMAL']);
        }

        return $event->fresh();
    }

    private function host(Event $event, bool $owner = true): User
    {
        $host = User::factory()->create(['osrs_username' => 'Host']);
        BoardAuthor::create(['event_id' => $event->id, 'user_id' => $host->id, 'is_owner' => $owner]);

        return $host;
    }

    private function player(Event $event, int $position = 24): array
    {
        $user = User::factory()->create(['osrs_username' => 'Player'.random_int(100, 999)]);
        EventParticipant::create(['event_id' => $event->id, 'user_id' => $user->id, 'access_mode' => 'INVITE']);

        $playerBoard = PlayerBoard::create([
            'board_id' => $event->board->id,
            'user_id' => $user->id,
            'current_position' => $position,
        ]);

        return [$user, $playerBoard];
    }

    private function lastTile(Event $event): Tile
    {
        return Tile::where('board_id', $event->board->id)->where('position', 24)->firstOrFail();
    }

    private function tick(User $actor, Event $event, Tile $tile, array $body = [])
    {
        return $this->actingAs($actor)->post("/events/{$event->id}/tiles/{$tile->id}/toggle", $body);
    }

    // ------------------------------------------------ recording the finish

    #[Test]
    public function completing_the_last_tile_records_a_finish(): void
    {
        Notification::fake();
        $event = $this->board();
        [$user] = $this->player($event);

        $this->tick($user, $event, $this->lastTile($event))->assertRedirect();

        $finish = EventFinish::where('event_id', $event->id)->first();

        $this->assertNotNull($finish, 'expected the last tile to record a finish');
        $this->assertSame($user->id, $finish->user_id);
        $this->assertNull($finish->team_id);
        $this->assertNotNull($finish->finished_at);
    }

    /**
     * The board's SIZE decides the finish line, not the number of tiles a
     * host got round to filling in — the same distinction that once turned
     * the sixth tile of a 5x5 into the finish for rolling.
     */
    #[Test]
    public function a_tile_that_is_not_the_last_one_does_not_finish_the_board(): void
    {
        Notification::fake();
        $event = $this->board();
        [$user] = $this->player($event, position: 23);

        $tile = Tile::where('board_id', $event->board->id)->where('position', 23)->firstOrFail();

        $this->tick($user, $event, $tile)->assertRedirect();

        $this->assertSame(0, EventFinish::where('event_id', $event->id)->count());
    }

    /**
     * The whole reason the finish moved to the server: on a reviewed board
     * the claim is not the score, so it cannot be the finish either. The old
     * browser-side guess congratulated the player the moment they clicked.
     */
    #[Test]
    public function a_pending_claim_on_the_last_tile_does_not_finish_the_board(): void
    {
        Notification::fake();
        $event = $this->board(['requires_approval' => true]);
        [$user] = $this->player($event);

        $this->tick($user, $event, $this->lastTile($event), ['proof_url' => 'https://i.imgur.com/x.png'])
            ->assertRedirect();

        $this->assertSame('PENDING', CompletedTile::first()->status);
        $this->assertSame(0, EventFinish::where('event_id', $event->id)->count());
    }

    /** ...and the host's approval is what makes it true. */
    #[Test]
    public function approving_the_last_tile_records_the_finish(): void
    {
        Notification::fake();
        $event = $this->board(['requires_approval' => true]);
        $host = $this->host($event);
        [$user] = $this->player($event);

        $this->tick($user, $event, $this->lastTile($event), ['proof_url' => 'https://i.imgur.com/x.png']);

        $claim = CompletedTile::first();

        $this->actingAs($host)
            ->patch("/events/{$event->id}/tiles/completions/{$claim->id}", ['status' => 'APPROVED'])
            ->assertRedirect();

        $this->assertSame(1, EventFinish::where('event_id', $event->id)->count());
    }

    /**
     * And takes it back again. A podium that cannot be corrected is worse
     * than none — a host who approves the wrong screenshot has to be able to
     * undo the result, not just the claim.
     */
    #[Test]
    public function rejecting_an_approved_last_tile_removes_the_finish(): void
    {
        Notification::fake();
        $event = $this->board(['requires_approval' => true]);
        $host = $this->host($event);
        [$user] = $this->player($event);

        $this->tick($user, $event, $this->lastTile($event), ['proof_url' => 'https://i.imgur.com/x.png']);
        $claim = CompletedTile::first();

        $this->actingAs($host)->patch("/events/{$event->id}/tiles/completions/{$claim->id}", ['status' => 'APPROVED']);
        $this->assertSame(1, EventFinish::where('event_id', $event->id)->count());

        $this->actingAs($host)->patch("/events/{$event->id}/tiles/completions/{$claim->id}", ['status' => 'REJECTED']);
        $this->assertSame(0, EventFinish::where('event_id', $event->id)->count());
    }

    /** Withdrawing your own tick un-finishes you too. */
    #[Test]
    public function untickings_the_last_tile_removes_the_finish(): void
    {
        Notification::fake();
        $event = $this->board();
        [$user] = $this->player($event);
        $tile = $this->lastTile($event);

        $this->tick($user, $event, $tile);
        $this->assertSame(1, EventFinish::where('event_id', $event->id)->count());

        $this->tick($user, $event, $tile);
        $this->assertSame(0, EventFinish::where('event_id', $event->id)->count());
    }

    /** On a TEAM event the finish belongs to the team, not to whoever ticked. */
    #[Test]
    public function a_team_event_records_the_team_not_the_player(): void
    {
        Notification::fake();
        $event = $this->board(mode: 'TEAM');

        $team = Team::create(['name' => 'Nardah', 'guild_id' => null]);
        BoardTeam::create(['event_id' => $event->id, 'team_id' => $team->id]);

        $user = User::factory()->create(['osrs_username' => 'Member']);
        TeamMember::create(['team_id' => $team->id, 'user_id' => $user->id]);
        EventParticipant::create(['event_id' => $event->id, 'user_id' => $user->id, 'access_mode' => 'INVITE']);

        PlayerBoard::create([
            'board_id' => $event->board->id,
            'team_id' => $team->id,
            'user_id' => null,
            'current_position' => 24,
        ]);

        $this->tick($user, $event, $this->lastTile($event))->assertRedirect();

        $finish = EventFinish::where('event_id', $event->id)->firstOrFail();

        $this->assertSame($team->id, $finish->team_id);
        $this->assertNull($finish->user_id);
    }

    /** Second place is a real place, and it is decided by the clock. */
    #[Test]
    public function finishes_are_ordered_and_ranked_by_when_they_happened(): void
    {
        Notification::fake();
        $event = $this->board();
        [$first] = $this->player($event);
        [$second] = $this->player($event);

        $this->tick($first, $event, $this->lastTile($event));
        $this->travel(1)->minutes();
        $this->tick($second, $event, $this->lastTile($event));

        $places = app(\App\Services\EventFinishService::class)->places($event->fresh());

        $this->assertCount(2, $places);
        $this->assertSame(1, $places[0]['rank']);
        $this->assertSame($first->id, $places[0]['userId']);
        $this->assertSame(2, $places[1]['rank']);
        $this->assertSame($second->id, $places[1]['userId']);
    }

    /** Ticking again changes nothing — and must not announce a second time. */
    #[Test]
    public function a_finish_is_recorded_once(): void
    {
        Notification::fake();
        $event = $this->board();
        [$user, $playerBoard] = $this->player($event);

        $service = app(\App\Services\EventFinishService::class);

        $this->tick($user, $event, $this->lastTile($event));
        $service->evaluateSnakesLadders($event->fresh(), $playerBoard->fresh());
        $service->evaluateSnakesLadders($event->fresh(), $playerBoard->fresh());

        $this->assertSame(1, EventFinish::where('event_id', $event->id)->count());
        $this->assertSame(1, AuditLog::where('action', 'event.finished')->count());
    }

    // ------------------------------------------- who was first, not who was
    //                                              approved first

    /**
     * The one that matters on a reviewed board: two people get home minutes
     * apart, both send a screenshot, and the host works down the queue in
     * whatever order it happens to be in. The winner is whoever submitted
     * first — approving the later claim first must not hand it the win.
     */
    #[Test]
    public function the_first_submission_wins_whatever_order_the_host_approves_in(): void
    {
        Notification::fake();
        $event = $this->board(['requires_approval' => true]);
        $host = $this->host($event);
        [$early] = $this->player($event);
        [$late] = $this->player($event);

        $this->travelTo(now()->setTime(20, 0));
        $this->tick($early, $event, $this->lastTile($event), ['proof_url' => 'https://i.imgur.com/a.png']);

        $this->travelTo(now()->addMinutes(5));
        $this->tick($late, $event, $this->lastTile($event), ['proof_url' => 'https://i.imgur.com/b.png']);

        $claims = CompletedTile::orderBy('completed_at')->get();
        $this->assertCount(2, $claims);

        // The host works the queue backwards — newest first, which is what a
        // list sorted by "most recent" invites.
        $this->travelTo(now()->addHour());
        $this->actingAs($host)->patch("/events/{$event->id}/tiles/completions/{$claims[1]->id}", ['status' => 'APPROVED']);
        $this->travelTo(now()->addMinutes(2));
        $this->actingAs($host)->patch("/events/{$event->id}/tiles/completions/{$claims[0]->id}", ['status' => 'APPROVED']);

        $places = app(\App\Services\EventFinishService::class)->places($event->fresh());

        $this->assertSame($early->id, $places[0]['userId'], 'the earlier submission has to take first place');
        $this->assertSame($late->id, $places[1]['userId']);
    }

    /** And the stamp itself is the submission, not the verdict. */
    #[Test]
    public function the_finish_is_timestamped_when_it_was_submitted(): void
    {
        Notification::fake();
        $event = $this->board(['requires_approval' => true]);
        $host = $this->host($event);
        [$user] = $this->player($event);

        $this->travelTo(now()->setTime(20, 0));
        $this->tick($user, $event, $this->lastTile($event), ['proof_url' => 'https://i.imgur.com/a.png']);
        $submittedAt = CompletedTile::first()->completed_at;

        $this->travelTo(now()->addHours(3));
        $this->actingAs($host)->patch("/events/{$event->id}/tiles/completions/".CompletedTile::first()->id, ['status' => 'APPROVED']);

        $this->assertEquals(
            $submittedAt->toDateTimeString(),
            EventFinish::first()->finished_at->toDateTimeString(),
        );
    }

    /** The host can see it is a race, and where this claim sits in it. */
    #[Test]
    public function the_review_queue_marks_the_finishing_tile_and_the_submission_order(): void
    {
        Notification::fake();
        $event = $this->board(['requires_approval' => true]);
        $host = $this->host($event);
        [$early] = $this->player($event);
        [$late] = $this->player($event);
        [$other] = $this->player($event, position: 3);

        $this->travelTo(now()->setTime(20, 0));
        $this->tick($early, $event, $this->lastTile($event), ['proof_url' => 'https://i.imgur.com/a.png']);
        $this->travelTo(now()->addMinutes(5));
        $this->tick($late, $event, $this->lastTile($event), ['proof_url' => 'https://i.imgur.com/b.png']);

        // An ordinary claim on another tile, which must not be dressed up as
        // part of the race.
        $plainTile = Tile::where('board_id', $event->board->id)->where('position', 3)->firstOrFail();
        $this->tick($other, $event, $plainTile, ['proof_url' => 'https://i.imgur.com/c.png']);

        $queue = app(\App\Services\BoardReviewService::class)->pendingQueue($event->board)->values();

        $race = $queue->where('finishesBoard', true)->values();

        $this->assertCount(2, $race);
        $this->assertSame(1, $race[0]['raceOrder']);
        $this->assertSame(2, $race[1]['raceOrder']);
        $this->assertSame(2, $race[0]['raceTotal']);

        $plain = $queue->firstWhere('finishesBoard', false);
        $this->assertNotNull($plain);
        $this->assertNull($plain['raceOrder']);
    }

    /** One claimant is not a race, and should not be dressed up as one. */
    #[Test]
    public function a_lone_finishing_claim_has_no_race_order(): void
    {
        Notification::fake();
        $event = $this->board(['requires_approval' => true]);
        $this->host($event);
        [$user] = $this->player($event);

        $this->tick($user, $event, $this->lastTile($event), ['proof_url' => 'https://i.imgur.com/a.png']);

        $claim = app(\App\Services\BoardReviewService::class)->pendingQueue($event->board)->first();

        $this->assertTrue($claim['finishesBoard']);
        $this->assertNull($claim['raceOrder']);
        $this->assertNull($claim['raceTotal']);
    }

    // ------------------------------------------- announcing it, or not yet

    /**
     * The reported bug, in one test. The host approves the SECOND submission
     * first; everybody was told that competitor had got home first, while
     * the claim that actually won sat unopened in the queue.
     *
     * Nothing is announced until the place cannot move any more.
     */
    #[Test]
    public function a_provisional_finish_is_not_announced(): void
    {
        Notification::fake();
        $event = $this->board(['requires_approval' => true]);
        $host = $this->host($event);
        [$early] = $this->player($event);
        [$late] = $this->player($event);

        $this->travelTo(now()->setTime(20, 0));
        $this->tick($early, $event, $this->lastTile($event), ['proof_url' => 'https://i.imgur.com/a.png']);
        $this->travelTo(now()->addMinutes(5));
        $this->tick($late, $event, $this->lastTile($event), ['proof_url' => 'https://i.imgur.com/b.png']);

        $claims = CompletedTile::orderBy('completed_at')->get();

        $this->actingAs($host)->patch("/events/{$event->id}/tiles/completions/{$claims[1]->id}", ['status' => 'APPROVED']);

        $finish = EventFinish::where('user_id', $late->id)->firstOrFail();

        $this->assertNull($finish->announced_at, 'the second submission must not be announced as a win');
        $this->assertTrue(app(\App\Services\EventFinishService::class)->isProvisional($event->fresh(), $finish->finished_at));
    }

    /** And once the queue is clear, both go out — in the right order. */
    #[Test]
    public function clearing_the_queue_announces_every_settled_place(): void
    {
        Notification::fake();
        $event = $this->board(['requires_approval' => true]);
        $host = $this->host($event);
        [$early] = $this->player($event);
        [$late] = $this->player($event);

        $this->travelTo(now()->setTime(20, 0));
        $this->tick($early, $event, $this->lastTile($event), ['proof_url' => 'https://i.imgur.com/a.png']);
        $this->travelTo(now()->addMinutes(5));
        $this->tick($late, $event, $this->lastTile($event), ['proof_url' => 'https://i.imgur.com/b.png']);

        $claims = CompletedTile::orderBy('completed_at')->get();

        $this->actingAs($host)->patch("/events/{$event->id}/tiles/completions/{$claims[1]->id}", ['status' => 'APPROVED']);
        $this->actingAs($host)->patch("/events/{$event->id}/tiles/completions/{$claims[0]->id}", ['status' => 'APPROVED']);

        $this->assertNotNull(EventFinish::where('user_id', $early->id)->firstOrFail()->announced_at);
        $this->assertNotNull(EventFinish::where('user_id', $late->id)->firstOrFail()->announced_at);

        $places = app(\App\Services\EventFinishService::class)->places($event->fresh());
        $this->assertSame($early->id, $places[0]['userId']);
        $this->assertFalse($places[0]['provisional']);
        $this->assertFalse($places[1]['provisional']);
    }

    /** A rejection settles a place as surely as an approval does. */
    #[Test]
    public function rejecting_the_earlier_claim_settles_and_announces_the_other(): void
    {
        Notification::fake();
        $event = $this->board(['requires_approval' => true]);
        $host = $this->host($event);
        [$early] = $this->player($event);
        [$late] = $this->player($event);

        $this->travelTo(now()->setTime(20, 0));
        $this->tick($early, $event, $this->lastTile($event), ['proof_url' => 'https://i.imgur.com/a.png']);
        $this->travelTo(now()->addMinutes(5));
        $this->tick($late, $event, $this->lastTile($event), ['proof_url' => 'https://i.imgur.com/b.png']);

        $claims = CompletedTile::orderBy('completed_at')->get();

        $this->actingAs($host)->patch("/events/{$event->id}/tiles/completions/{$claims[1]->id}", ['status' => 'APPROVED']);
        $this->assertNull(EventFinish::where('user_id', $late->id)->firstOrFail()->announced_at);

        $this->actingAs($host)->patch("/events/{$event->id}/tiles/completions/{$claims[0]->id}", ['status' => 'REJECTED']);

        $this->assertNotNull(EventFinish::where('user_id', $late->id)->firstOrFail()->announced_at);
    }

    /** With nothing in the queue there is nothing to wait for. */
    #[Test]
    public function an_uncontested_finish_is_announced_immediately(): void
    {
        Notification::fake();
        $event = $this->board();
        [$user] = $this->player($event);

        $this->tick($user, $event, $this->lastTile($event));

        $this->assertNotNull(EventFinish::firstOrFail()->announced_at);
    }

    /** The page is told, so it can hold the banner and the celebration. */
    #[Test]
    public function the_page_is_told_when_a_place_is_provisional(): void
    {
        Notification::fake();
        $event = $this->board(['requires_approval' => true]);
        $host = $this->host($event);
        [$early] = $this->player($event);
        [$late] = $this->player($event);

        $this->travelTo(now()->setTime(20, 0));
        $this->tick($early, $event, $this->lastTile($event), ['proof_url' => 'https://i.imgur.com/a.png']);
        $this->travelTo(now()->addMinutes(5));
        $this->tick($late, $event, $this->lastTile($event), ['proof_url' => 'https://i.imgur.com/b.png']);

        $claims = CompletedTile::orderBy('completed_at')->get();
        $this->actingAs($host)->patch("/events/{$event->id}/tiles/completions/{$claims[1]->id}", ['status' => 'APPROVED']);

        $this->actingAs($late)->get("/events/{$event->id}")
            ->assertInertia(fn ($page) => $page
                ->where('myFinish.provisional', true)
                ->where('finishes.0.provisional', true));
    }

    // ------------------------------------------------------ the STOP rule

    #[Test]
    public function the_continue_rule_leaves_the_event_open(): void
    {
        Notification::fake();
        $event = $this->board(['finish_rule' => 'CONTINUE']);
        [$user] = $this->player($event);

        $this->tick($user, $event, $this->lastTile($event));

        $this->assertNull($event->fresh()->closed_at);
        $this->assertFalse($event->fresh()->isEnded());
    }

    #[Test]
    public function the_stop_rule_closes_the_event_on_the_first_finish(): void
    {
        Notification::fake();
        $event = $this->board(['finish_rule' => 'STOP']);
        [$user] = $this->player($event);

        $this->tick($user, $event, $this->lastTile($event));

        $fresh = $event->fresh();

        $this->assertNotNull($fresh->closed_at);
        $this->assertTrue($fresh->isClosed());
        $this->assertTrue($fresh->isEnded(), 'a closed event has to read as ended everywhere');
        $this->assertSame(1, AuditLog::where('action', 'event.closed')->count());
    }

    /**
     * The point of folding `closed_at` into isEnded(): every door that was
     * already shut for an ended event is shut for a closed one, with no new
     * check anywhere.
     */
    #[Test]
    public function a_closed_event_refuses_rolls_and_ticks(): void
    {
        Notification::fake();
        $event = $this->board(['finish_rule' => 'STOP']);
        [$winner] = $this->player($event);
        [$other, $otherBoard] = $this->player($event, position: 3);

        $this->tick($winner, $event, $this->lastTile($event));

        $tile = Tile::where('board_id', $event->board->id)->where('position', 3)->firstOrFail();

        $this->actingAs($other)->post("/events/{$event->id}/roll")->assertSessionHas('board-save-error');
        $this->assertSame(3, $otherBoard->fresh()->current_position, 'a closed event must not accept a roll');

        $this->tick($other, $event, $tile)->assertSessionHas('board-save-error');
        $this->assertSame(0, CompletedTile::where('player_board_id', $otherBoard->id)->count());
    }

    /**
     * Only the first one closes it. A second finish landing in the same
     * second must not stamp a second `closed_at` or announce twice.
     */
    #[Test]
    public function only_the_first_finish_closes_the_event(): void
    {
        Notification::fake();
        $event = $this->board(['finish_rule' => 'STOP']);
        [$first] = $this->player($event);
        [$secondUser, $secondBoard] = $this->player($event);

        $this->tick($first, $event, $this->lastTile($event));
        $closedAt = $event->fresh()->closed_at;

        // Straight at the service: the HTTP route is shut by now, which is
        // the point — this is the belt-and-braces half.
        CompletedTile::create([
            'player_board_id' => $secondBoard->id,
            'tile_id' => $this->lastTile($event)->id,
            'status' => 'APPROVED',
        ]);
        app(\App\Services\EventFinishService::class)->evaluateSnakesLadders($event->fresh(), $secondBoard);

        $this->assertEquals($closedAt, $event->fresh()->closed_at, 'the close stamp must not move');
        $this->assertSame(2, EventFinish::where('event_id', $event->id)->count());
        $this->assertSame(1, AuditLog::where('action', 'event.closed')->count());
    }

    /**
     * Reopening is a host's deliberate action, never a side effect. A
     * rejected claim takes the podium row away and leaves the event closed —
     * a competition that restarted itself while the host was looking
     * elsewhere would be worse than one that needs a button.
     */
    #[Test]
    public function undoing_the_winning_claim_does_not_reopen_the_event(): void
    {
        Notification::fake();
        $event = $this->board(['finish_rule' => 'STOP', 'requires_approval' => true]);
        $host = $this->host($event);
        [$user] = $this->player($event);

        $this->tick($user, $event, $this->lastTile($event), ['proof_url' => 'https://i.imgur.com/x.png']);
        $claim = CompletedTile::first();

        $this->actingAs($host)->patch("/events/{$event->id}/tiles/completions/{$claim->id}", ['status' => 'APPROVED']);
        $this->assertNotNull($event->fresh()->closed_at);

        $this->actingAs($host)->patch("/events/{$event->id}/tiles/completions/{$claim->id}", ['status' => 'REJECTED']);

        $this->assertSame(0, EventFinish::where('event_id', $event->id)->count());
        $this->assertNotNull($event->fresh()->closed_at, 'closing is undone by a host, not by a verdict');
    }

    /**
     * The other half of "the first submission wins": under STOP, closing on
     * the first approval would announce the wrong winner whenever an earlier
     * claim is still sitting in the queue. A push notification cannot be
     * taken back, so the close waits until nobody can still beat the leader.
     */
    #[Test]
    public function stop_waits_for_an_earlier_claim_still_in_the_queue(): void
    {
        Notification::fake();
        $event = $this->board(['finish_rule' => 'STOP', 'requires_approval' => true]);
        $host = $this->host($event);
        [$early] = $this->player($event);
        [$late] = $this->player($event);

        $this->travelTo(now()->setTime(20, 0));
        $this->tick($early, $event, $this->lastTile($event), ['proof_url' => 'https://i.imgur.com/a.png']);
        $this->travelTo(now()->addMinutes(5));
        $this->tick($late, $event, $this->lastTile($event), ['proof_url' => 'https://i.imgur.com/b.png']);

        $claims = CompletedTile::orderBy('completed_at')->get();

        // Approving the LATER one first must not close the event: the
        // earlier claim could still turn out to be the winner.
        $this->actingAs($host)->patch("/events/{$event->id}/tiles/completions/{$claims[1]->id}", ['status' => 'APPROVED']);

        $this->assertNull($event->fresh()->closed_at, 'closing here would announce the wrong winner');
        $this->assertSame(1, EventFinish::where('event_id', $event->id)->count());

        // Once the queue is clear, it closes — with the right winner.
        $this->actingAs($host)->patch("/events/{$event->id}/tiles/completions/{$claims[0]->id}", ['status' => 'APPROVED']);

        $this->assertNotNull($event->fresh()->closed_at);

        $places = app(\App\Services\EventFinishService::class)->places($event->fresh());
        $this->assertSame($early->id, $places[0]['userId']);
    }

    /** A rejection clears the way just as an approval does. */
    #[Test]
    public function rejecting_the_last_contender_lets_the_close_through(): void
    {
        Notification::fake();
        $event = $this->board(['finish_rule' => 'STOP', 'requires_approval' => true]);
        $host = $this->host($event);
        [$early] = $this->player($event);
        [$late] = $this->player($event);

        $this->travelTo(now()->setTime(20, 0));
        $this->tick($early, $event, $this->lastTile($event), ['proof_url' => 'https://i.imgur.com/a.png']);
        $this->travelTo(now()->addMinutes(5));
        $this->tick($late, $event, $this->lastTile($event), ['proof_url' => 'https://i.imgur.com/b.png']);

        $claims = CompletedTile::orderBy('completed_at')->get();

        $this->actingAs($host)->patch("/events/{$event->id}/tiles/completions/{$claims[1]->id}", ['status' => 'APPROVED']);
        $this->assertNull($event->fresh()->closed_at);

        // The earlier screenshot turns out to be no good.
        $this->actingAs($host)->patch("/events/{$event->id}/tiles/completions/{$claims[0]->id}", ['status' => 'REJECTED']);

        $this->assertNotNull($event->fresh()->closed_at, 'nothing can beat the leader now');

        $places = app(\App\Services\EventFinishService::class)->places($event->fresh());
        $this->assertCount(1, $places);
        $this->assertSame($late->id, $places[0]['userId']);
    }

    /**
     * A pending claim that is NOT on the finishing tile holds nothing up —
     * an event with a busy review queue would otherwise never close.
     */
    #[Test]
    public function an_unrelated_pending_claim_does_not_hold_the_close(): void
    {
        Notification::fake();
        $event = $this->board(['finish_rule' => 'STOP', 'requires_approval' => true]);
        $host = $this->host($event);
        [$winner] = $this->player($event);
        [$other] = $this->player($event, position: 3);

        $this->travelTo(now()->setTime(20, 0));
        $plainTile = Tile::where('board_id', $event->board->id)->where('position', 3)->firstOrFail();
        $this->tick($other, $event, $plainTile, ['proof_url' => 'https://i.imgur.com/c.png']);

        $this->travelTo(now()->addMinutes(5));
        $this->tick($winner, $event, $this->lastTile($event), ['proof_url' => 'https://i.imgur.com/a.png']);

        $claim = CompletedTile::whereHas('tile', fn ($q) => $q->where('position', 24))->firstOrFail();
        $this->actingAs($host)->patch("/events/{$event->id}/tiles/completions/{$claim->id}", ['status' => 'APPROVED']);

        $this->assertNotNull($event->fresh()->closed_at);
    }

    // ----------------------------------------------------- ending by hand

    #[Test]
    public function a_host_can_end_and_reopen_an_event(): void
    {
        Notification::fake();
        $event = $this->board();
        $host = $this->host($event);

        $this->actingAs($host)->patch("/events/{$event->id}/close", ['closed' => true])->assertRedirect();
        $this->assertNotNull($event->fresh()->closed_at);
        $this->assertSame(1, AuditLog::where('action', 'event.closed')->count());

        $this->actingAs($host)->patch("/events/{$event->id}/close", ['closed' => false])->assertRedirect();
        $this->assertNull($event->fresh()->closed_at);
        $this->assertSame(1, AuditLog::where('action', 'event.reopened')->count());
    }

    /** Reversible, so it is any host's — the same line pausing draws. */
    #[Test]
    public function a_co_host_can_end_it_but_a_stranger_cannot(): void
    {
        Notification::fake();
        $event = $this->board();
        $coHost = $this->host($event, owner: false);
        $stranger = User::factory()->create();

        $this->actingAs($stranger)->patch("/events/{$event->id}/close", ['closed' => true])->assertForbidden();
        $this->assertNull($event->fresh()->closed_at);

        $this->actingAs($coHost)->patch("/events/{$event->id}/close", ['closed' => true])->assertRedirect();
        $this->assertNotNull($event->fresh()->closed_at);
    }

    /** A double-click must not mail everybody twice. */
    #[Test]
    public function ending_an_already_ended_event_does_nothing(): void
    {
        Notification::fake();
        $event = $this->board();
        $host = $this->host($event);
        $this->participant($event);

        $this->actingAs($host)->patch("/events/{$event->id}/close", ['closed' => true]);
        $closedAt = $event->fresh()->closed_at;

        $this->actingAs($host)->patch("/events/{$event->id}/close", ['closed' => true]);

        $this->assertEquals($closedAt, $event->fresh()->closed_at);
        $this->assertSame(1, AuditLog::where('action', 'event.closed')->count());
        Notification::assertSentTimes(EventStatusChanged::class, 1);
    }

    #[Test]
    public function ending_an_event_tells_the_people_in_it(): void
    {
        Notification::fake();
        $event = $this->board();
        $host = $this->host($event);
        $player = $this->participant($event);

        $this->actingAs($host)->patch("/events/{$event->id}/close", ['closed' => true]);

        Notification::assertSentTo($player, EventStatusChanged::class, fn ($n) => $n->change === EventStatusChanged::ENDED);
    }

    /** The host who is only correcting a misclick can keep it quiet. */
    #[Test]
    public function ending_can_be_done_without_telling_anybody(): void
    {
        Notification::fake();
        $event = $this->board();
        $host = $this->host($event);
        $this->participant($event);

        $this->actingAs($host)->patch("/events/{$event->id}/close", ['closed' => true, 'notify' => false]);

        Notification::assertNothingSent();
        $this->assertNotNull($event->fresh()->closed_at);
    }

    /**
     * `closed_at` is not fillable, exactly like `paused_at`: ending an event
     * is an announcement, not a field that can ride along inside a save that
     * also renamed it.
     */
    #[Test]
    public function an_ordinary_save_cannot_close_an_event(): void
    {
        Notification::fake();
        $event = $this->board();
        $host = $this->host($event);

        $this->actingAs($host)->patch("/events/{$event->id}", [
            'title' => 'Renamed',
            'closed_at' => now()->toDateTimeString(),
        ]);

        $this->assertNull($event->fresh()->closed_at);
        $this->assertSame('Renamed', $event->fresh()->title);
    }

    // ------------------------------------------------------- the setting

    #[Test]
    public function the_finish_rule_defaults_to_continue_and_is_editable(): void
    {
        Notification::fake();
        $event = $this->board();
        $host = $this->host($event);

        $this->assertSame('CONTINUE', $event->finish_rule);

        $this->actingAs($host)->patch("/events/{$event->id}", [
            'title' => $event->title,
            'finish_rule' => 'STOP',
        ])->assertRedirect();

        $this->assertSame('STOP', $event->fresh()->finish_rule);
    }

    /** One form carries every type's fields, so a null must not blank it. */
    #[Test]
    public function a_null_finish_rule_leaves_the_setting_alone(): void
    {
        Notification::fake();
        $event = $this->board(['finish_rule' => 'STOP']);
        $host = $this->host($event);

        $this->actingAs($host)->patch("/events/{$event->id}", [
            'title' => $event->title,
            'finish_rule' => null,
        ])->assertRedirect();

        $this->assertSame('STOP', $event->fresh()->finish_rule);
    }

    #[Test]
    public function an_unknown_finish_rule_is_refused(): void
    {
        Notification::fake();
        $event = $this->board();
        $host = $this->host($event);

        $this->actingAs($host)->patch("/events/{$event->id}", [
            'title' => $event->title,
            'finish_rule' => 'SUDDEN_DEATH',
        ])->assertSessionHasErrors('finish_rule');
    }

    // ------------------------------------------------------------- bingo

    /** A won card is recorded, in the same table, by the same rule. */
    #[Test]
    public function winning_a_bingo_card_records_a_finish(): void
    {
        Notification::fake();
        $event = Event::create([
            'title' => 'Card night',
            'type' => 'BINGO',
            'mode' => 'SOLO',
            'access_mode' => 'OPEN',
            'is_listed' => true,
            'finish_rule' => 'STOP',
        ]);

        $card = BingoCard::create([
            'event_id' => $event->id,
            'size' => 3,
            'win_condition' => 'LINE',
            'win_lines' => ['ROW'],
            'line_bonus' => 0,
            'requires_approval' => false,
        ]);
        app(BingoService::class)->ensureSquares($card);

        $user = User::factory()->create(['osrs_username' => 'Bingoer']);
        EventParticipant::create(['event_id' => $event->id, 'user_id' => $user->id, 'access_mode' => 'INVITE']);

        $event = $event->fresh();
        $row = BingoSquare::where('bingo_card_id', $card->id)->whereIn('position', [0, 1, 2])->orderBy('position')->get();

        foreach ($row as $index => $square) {
            $this->actingAs($user)->post("/events/{$event->id}/bingo/squares/{$square->id}/claim")->assertRedirect();

            // Only the third one completes the row, so nothing before it may
            // record a finish.
            $expected = $index === 2 ? 1 : 0;
            $this->assertSame($expected, EventFinish::where('event_id', $event->id)->count());
        }

        $this->assertNotNull($event->fresh()->closed_at, 'STOP closes a bingo event too');
    }

    /** Withdrawing a square breaks the line again, and the finish with it. */
    #[Test]
    public function withdrawing_a_square_removes_a_bingo_finish(): void
    {
        Notification::fake();
        $event = Event::create([
            'title' => 'Card night',
            'type' => 'BINGO',
            'mode' => 'SOLO',
            'access_mode' => 'OPEN',
            'is_listed' => true,
        ]);

        $card = BingoCard::create([
            'event_id' => $event->id,
            'size' => 3,
            'win_condition' => 'LINE',
            'win_lines' => ['ROW'],
            'line_bonus' => 0,
            'requires_approval' => false,
        ]);
        app(BingoService::class)->ensureSquares($card);

        $user = User::factory()->create(['osrs_username' => 'Bingoer']);
        EventParticipant::create(['event_id' => $event->id, 'user_id' => $user->id, 'access_mode' => 'INVITE']);
        $event = $event->fresh();

        $row = BingoSquare::where('bingo_card_id', $card->id)->whereIn('position', [0, 1, 2])->orderBy('position')->get();

        foreach ($row as $square) {
            $this->actingAs($user)->post("/events/{$event->id}/bingo/squares/{$square->id}/claim");
        }

        $this->assertSame(1, EventFinish::where('event_id', $event->id)->count());

        // Take one back out of the line.
        $this->actingAs($user)->post("/events/{$event->id}/bingo/squares/{$row[1]->id}/claim");

        $this->assertSame(0, BingoCompletion::where('bingo_square_id', $row[1]->id)->count());
        $this->assertSame(0, EventFinish::where('event_id', $event->id)->count());
    }

    /**
     * The bingo half of the same rule: the earlier submission wins the card,
     * whatever order the host signs the squares off in.
     */
    #[Test]
    public function the_earlier_bingo_submission_wins_the_card(): void
    {
        Notification::fake();
        [$event, $card] = $this->card(['requires_approval' => true]);
        $host = $this->host($event);

        $early = User::factory()->create(['osrs_username' => 'Early']);
        $late = User::factory()->create(['osrs_username' => 'Late']);
        EventParticipant::create(['event_id' => $event->id, 'user_id' => $early->id]);
        EventParticipant::create(['event_id' => $event->id, 'user_id' => $late->id]);

        $row = BingoSquare::where('bingo_card_id', $card->id)
            ->whereIn('position', [0, 1, 2])->orderBy('position')->get();

        // Both fill the same row; one of them a few minutes ahead.
        $this->travelTo(now()->setTime(20, 0));
        foreach ($row as $square) {
            $this->claim($early, $event, $square);
        }

        $this->travelTo(now()->addMinutes(5));
        foreach ($row as $square) {
            $this->claim($late, $event, $square);
        }

        // The host approves the LATER competitor's squares first.
        $this->travelTo(now()->addHour());
        foreach ([$late, $early] as $user) {
            foreach (BingoCompletion::where('user_id', $user->id)->get() as $completion) {
                $this->actingAs($host)->patch("/events/{$event->id}/bingo/claims/{$completion->id}", ['status' => 'APPROVED']);
            }
        }

        $places = app(\App\Services\EventFinishService::class)->places($event->fresh());

        $this->assertCount(2, $places);
        $this->assertSame($early->id, $places[0]['userId'], 'the earlier submission has to take first place');
    }

    /** The host is told which waiting claim would win, and who was first. */
    #[Test]
    public function the_bingo_queue_marks_the_claims_that_would_win(): void
    {
        Notification::fake();
        [$event, $card] = $this->card(['requires_approval' => true]);

        $early = User::factory()->create(['osrs_username' => 'Early']);
        $late = User::factory()->create(['osrs_username' => 'Late']);
        EventParticipant::create(['event_id' => $event->id, 'user_id' => $early->id]);
        EventParticipant::create(['event_id' => $event->id, 'user_id' => $late->id]);

        $row = BingoSquare::where('bingo_card_id', $card->id)
            ->whereIn('position', [0, 1, 2])->orderBy('position')->get();

        $this->travelTo(now()->setTime(20, 0));
        foreach ($row as $square) {
            $this->claim($early, $event, $square);
        }

        $this->travelTo(now()->addMinutes(5));
        foreach ($row as $square) {
            $this->claim($late, $event, $square);
        }

        $queue = app(BingoService::class)->pendingQueue($card->fresh());
        $winning = $queue->where('winsCard', true)->values();

        // One per competitor: the square that tips them over, not all three.
        $this->assertCount(2, $winning);
        $this->assertSame(1, $winning[0]['raceOrder']);
        $this->assertSame(2, $winning[1]['raceOrder']);
        $this->assertSame(2, $winning[0]['raceTotal']);

        // And the ones that do not complete anything carry nothing.
        $this->assertGreaterThan(0, $queue->where('winsCard', false)->count());
    }

    // --------------------------------------------------- what the pages see

    #[Test]
    public function the_board_page_carries_the_podium_and_your_own_place(): void
    {
        Notification::fake();
        $event = $this->board();
        [$user] = $this->player($event);

        $this->tick($user, $event, $this->lastTile($event));

        $this->actingAs($user)->get("/events/{$event->id}")
            ->assertInertia(fn ($page) => $page
                ->has('finishes', 1)
                ->where('finishes.0.rank', 1)
                ->where('myFinish.rank', 1));
    }

    /**
     * The sidebar decides which row is yours by comparing team ids, and the
     * comparison was against a field the page was never sent — so on a TEAM
     * event nothing was ever marked as your own.
     */
    #[Test]
    public function the_page_knows_which_team_is_yours(): void
    {
        Notification::fake();
        $event = $this->board(mode: 'TEAM');

        $team = Team::create(['name' => 'Nardah', 'guild_id' => null]);
        BoardTeam::create(['event_id' => $event->id, 'team_id' => $team->id]);

        $user = User::factory()->create(['osrs_username' => 'Member']);
        TeamMember::create(['team_id' => $team->id, 'user_id' => $user->id]);
        EventParticipant::create(['event_id' => $event->id, 'user_id' => $user->id]);

        PlayerBoard::create([
            'board_id' => $event->board->id,
            'team_id' => $team->id,
            'user_id' => null,
            'current_position' => 4,
        ]);

        $this->actingAs($user)->get("/events/{$event->id}")
            ->assertInertia(fn ($page) => $page->where('playerBoard.team_id', $team->id));
    }

    /** Somebody who has not finished is told so plainly: null, not a rank. */
    #[Test]
    public function a_player_who_has_not_finished_has_no_place(): void
    {
        Notification::fake();
        $event = $this->board();
        [$winner] = $this->player($event);
        [$other] = $this->player($event, position: 2);

        $this->tick($winner, $event, $this->lastTile($event));

        $this->actingAs($other)->get("/events/{$event->id}")
            ->assertInertia(fn ($page) => $page->has('finishes', 1)->where('myFinish', null));
    }

    /**
     * Finishers first, then everyone still walking. Ordering by position
     * alone put everybody parked on the last tile in a heap at the top with
     * no tiebreak — including people who had never completed it.
     */
    #[Test]
    public function the_leaderboard_puts_finishers_above_everyone_else(): void
    {
        Notification::fake();
        $event = $this->board();

        // Standing on the last tile without completing it is not finishing.
        [$loiterer] = $this->player($event, position: 24);
        [$winner] = $this->player($event, position: 24);

        $this->tick($winner, $event, $this->lastTile($event));

        $this->actingAs($winner)->get("/events/{$event->id}/leaderboard")
            ->assertInertia(fn ($page) => $page
                ->where('entries.0.finishPlace', 1)
                ->where('entries.1.finishPlace', null)
                ->has('finishes', 1));

        $this->assertNotNull($loiterer);
    }

    /**
     * A shared channel is read by the least trusted viewer it has, so the
     * podium on it is anonymised on anything but an open event — the same
     * rule the player rows follow on the page itself.
     */
    #[Test]
    public function a_private_events_podium_hides_names_from_a_stranger(): void
    {
        Notification::fake();
        $event = $this->board(['access_mode' => 'INVITE']);
        [$user] = $this->player($event);
        \App\Models\BoardAccess::create(['event_id' => $event->id, 'user_id' => $user->id, 'access_mode' => 'INVITE']);

        $this->tick($user, $event, $this->lastTile($event))->assertRedirect();

        $places = app(\App\Services\EventFinishService::class)->places($event->fresh(), namesArePublic: false);

        $this->assertSame(trans('events.anonymous_player'), $places[0]['label']);
        $this->assertNull($places[0]['userId']);
    }

    /**
     * A 3x3 rows-only bingo event and its card.
     *
     * @return array{0: Event, 1: BingoCard}
     */
    private function card(array $attributes = []): array
    {
        $event = Event::create([
            'title' => 'Card night',
            'type' => 'BINGO',
            'mode' => 'SOLO',
            'access_mode' => 'OPEN',
            'is_listed' => true,
            ...collect($attributes)->except('requires_approval')->all(),
        ]);

        $card = BingoCard::create([
            'event_id' => $event->id,
            'size' => 3,
            'win_condition' => 'LINE',
            'win_lines' => ['ROW'],
            'line_bonus' => 0,
            'requires_approval' => $attributes['requires_approval'] ?? false,
        ]);
        app(BingoService::class)->ensureSquares($card);

        return [$event->fresh(), $card->fresh()];
    }

    private function claim(User $actor, Event $event, BingoSquare $square)
    {
        return $this->actingAs($actor)->post(
            "/events/{$event->id}/bingo/squares/{$square->id}/claim",
            ['proof_url' => 'https://i.imgur.com/'.$square->position.'.png'],
        );
    }

    private function participant(Event $event): User
    {
        $user = User::factory()->create(['email' => 'p'.random_int(1000, 9999).'@example.com']);
        EventParticipant::create(['event_id' => $event->id, 'user_id' => $user->id, 'access_mode' => 'INVITE']);

        return $user;
    }
}
