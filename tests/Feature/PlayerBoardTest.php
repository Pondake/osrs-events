<?php

namespace Tests\Feature;

use App\Models\Board;
use App\Models\BoardAuthor;
use App\Models\BoardTeam;
use App\Models\CompletedTile;
use App\Models\Event;
use App\Models\PlayerBoard;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\Tile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Playing a Snakes & Ladders board: rolling, jumping, and ticking tiles off.
 *
 * This is the oldest game loop in the app and had no test at all. It is also
 * the one place where a player's own action writes their score, so the
 * interesting questions are about what a player can make it do that they
 * should not be able to.
 */
class PlayerBoardTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: User, 1: Event} */
    private function board(array $attributes = [], string $mode = 'SOLO'): array
    {
        $owner = User::factory()->create(['osrs_username' => 'Owner']);

        $event = Event::create(array_merge([
            'title' => 'Board night',
            'type' => 'SNAKES_LADDERS',
            'mode' => $mode,
            'access_mode' => 'OPEN',
            'is_listed' => true,
        ], $attributes['event'] ?? []));

        $event->board()->create(array_merge(
            ['size' => 'SIZE_5X5'],
            $attributes['board'] ?? [],
        ));

        BoardAuthor::create(['event_id' => $event->id, 'user_id' => $owner->id, 'is_owner' => true]);

        return [$owner, $event->fresh()];
    }

    /** Fills the grid so a roll has somewhere to land. */
    private function fillTiles(Board $board): void
    {
        for ($position = 0; $position < $board->tileCount(); $position++) {
            Tile::create(['board_id' => $board->id, 'position' => $position, 'type' => 'NORMAL']);
        }
    }

    // ------------------------------------------------------------------ rolling

    /**
     * A snake takes back everything above where it drops you — including work
     * done beyond its own head.
     *
     * You cannot normally complete a tile you have not stood on, so "above the
     * head" sounds unreachable. A ladder makes it reachable: finish tile 19,
     * slide to the start, climb back to 19, and under the old rule it was
     * still ticked off — credit carried over from a run that no longer exists.
     */
    #[Test]
    public function a_snake_clears_progress_above_where_it_drops_you(): void
    {
        [$owner, $event] = $this->board();
        $this->fillTiles($event->board);

        // Every tile the first roll can reach is a snake back to the start,
        // because the die cannot be forced outside the local environment.
        Tile::where('board_id', $event->board->id)
            ->whereBetween('position', [1, 6])
            ->update(['type' => 'SNAKE', 'target_position' => 0]);

        $player = PlayerBoard::create([
            'board_id' => $event->board->id,
            'user_id' => $owner->id,
            'current_position' => 0,
        ]);

        // Two completions: one inside the snake's own span, one well past its
        // head — the sort a ladder leaves behind.
        foreach ([2, 19] as $position) {
            CompletedTile::create([
                'player_board_id' => $player->id,
                'tile_id' => Tile::where('board_id', $event->board->id)->where('position', $position)->value('id'),
            ]);
        }

        $this->actingAs($owner)->post("/events/{$event->id}/roll")->assertRedirect();

        $this->assertSame(0, $player->fresh()->current_position, 'expected the snake to have been hit');
        $this->assertSame(0, CompletedTile::where('player_board_id', $player->id)->count());
    }

    /**
     * A host fills in the squares they care about and leaves the rest blank.
     * The page renders the whole grid regardless, so the finish line has to be
     * the board's size — not the number of tiles that happen to exist.
     *
     * Reported from a 5×5 board with six configured tiles: a player standing
     * on 19 rolled a 1 and was put on 6, because six rows made position 5 the
     * end of the board and every roll clamped them back to it.
     */
    #[Test]
    public function a_sparsely_configured_board_still_runs_its_whole_grid(): void
    {
        [$owner, $event] = $this->board();

        // Six tiles on a twenty-five square board, none of them near the end.
        foreach ([0, 1, 2, 7, 17, 20] as $position) {
            Tile::create(['board_id' => $event->board->id, 'position' => $position, 'type' => 'NORMAL']);
        }

        $player = PlayerBoard::create([
            'board_id' => $event->board->id,
            'user_id' => $owner->id,
            'current_position' => 18,
        ]);

        $this->actingAs($owner)->post("/events/{$event->id}/roll")->assertRedirect();

        $this->assertGreaterThan(18, $player->fresh()->current_position);
    }

    /** And the floor still holds on a board with nothing on it at all. */
    #[Test]
    public function an_empty_board_does_not_walk_anybody_off_the_front(): void
    {
        [$owner, $event] = $this->board();

        $this->actingAs($owner)->post("/events/{$event->id}/roll")->assertRedirect();

        $this->assertGreaterThanOrEqual(0, PlayerBoard::where('board_id', $event->board->id)->firstOrFail()->current_position);
    }

    /**
     * The board animates the move, and the piece has to travel the same route
     * the score took. So the roll reports where it started, where the dice
     * reached, and where a snake or ladder then dropped the player — rather
     * than leaving the browser to work that out again from a position and a
     * number, which is a second place for it to be wrong.
     *
     * Every reachable tile is a ladder here, because the die cannot be forced:
     * the `force` parameter only exists in the local environment, and tests do
     * not run there. That gate is deliberate — a forced roll on production
     * would be cheating with extra steps — so a test that leaned on it would
     * be testing something no deployment has.
     */
    #[Test]
    public function a_roll_reports_the_whole_move_and_not_just_the_number(): void
    {
        [$owner, $event] = $this->board();
        $this->fillTiles($event->board);

        Tile::where('board_id', $event->board->id)
            ->whereBetween('position', [1, 6])
            ->update(['type' => 'LADDER', 'target_position' => 20]);

        $this->actingAs($owner)
            ->post("/events/{$event->id}/roll")
            ->assertSessionHas('last-move', fn (array $move) => $move['from'] === 0
                && $move['landed'] >= 1 && $move['landed'] <= 6
                && $move['to'] === 20
                && $move['jump'] === 'ladder');
    }

    /** A plain move says so, rather than leaving `jump` to be guessed at. */
    #[Test]
    public function a_move_that_lands_on_nothing_reports_no_jump(): void
    {
        [$owner, $event] = $this->board();
        $this->fillTiles($event->board);

        $this->actingAs($owner)
            ->post("/events/{$event->id}/roll")
            ->assertSessionHas('last-move', fn (array $move) => $move['from'] === 0
                && $move['to'] === $move['landed']
                && $move['jump'] === null);
    }

    #[Test]
    public function a_roll_moves_the_player_forward_by_one_to_six(): void
    {
        [$owner, $event] = $this->board();
        $this->fillTiles($event->board);

        $this->actingAs($owner)->post("/events/{$event->id}/roll")->assertRedirect();

        $player = PlayerBoard::where('board_id', $event->board->id)->firstOrFail();

        $this->assertGreaterThanOrEqual(1, $player->current_position);
        $this->assertLessThanOrEqual(6, $player->current_position);
        $this->assertSame(1, $player->dice_rolls_today);
    }

    /** The number itself is flashed separately so the dice can render a face. */
    #[Test]
    public function the_rolled_number_comes_back_on_its_own(): void
    {
        [$owner, $event] = $this->board();
        $this->fillTiles($event->board);

        $response = $this->actingAs($owner)->post("/events/{$event->id}/roll");

        $rolled = $response->getSession()->get('last-roll');

        $this->assertIsInt($rolled);
        $this->assertGreaterThanOrEqual(1, $rolled);
        $this->assertLessThanOrEqual(6, $rolled);
    }

    #[Test]
    public function a_player_cannot_be_carried_past_the_last_tile(): void
    {
        [$owner, $event] = $this->board();
        $this->fillTiles($event->board);

        $last = $event->board->tileCount() - 1;

        PlayerBoard::create([
            'user_id' => $owner->id,
            'board_id' => $event->board->id,
            'current_position' => $last - 1,
        ]);

        $this->actingAs($owner)->post("/events/{$event->id}/roll");

        $this->assertSame($last, PlayerBoard::where('board_id', $event->board->id)->first()->current_position);
    }

    /**
     * An empty board has no last tile to stop at. Rolling on one used to walk
     * the player to position -1 — `count() - 1` with nothing in the grid.
     */
    #[Test]
    public function rolling_on_a_board_with_no_tiles_does_not_move_the_player_backwards(): void
    {
        [$owner, $event] = $this->board();

        $this->actingAs($owner)->post("/events/{$event->id}/roll");

        $player = PlayerBoard::where('board_id', $event->board->id)->first();

        $this->assertNotNull($player);
        $this->assertGreaterThanOrEqual(0, $player->current_position);
    }

    // ---------------------------------------------------------- ended events

    /**
     * The status badge on the page derives "Ended" from end_date alone
     * (eventStatus() in Support/board.js); rolling and tile-completion used to
     * only check isPaused() and never end_date at all, so a finished event
     * still let a player roll and move — reported live on staging.
     */
    #[Test]
    public function rolling_is_refused_once_the_event_has_ended(): void
    {
        [$owner, $event] = $this->board(['event' => [
            'start_date' => now()->subDays(10),
            'end_date' => now()->subDay(),
        ]]);
        $this->fillTiles($event->board);

        $player = PlayerBoard::create([
            'user_id' => $owner->id,
            'board_id' => $event->board->id,
            'current_position' => 0,
        ]);

        $this->actingAs($owner)->post("/events/{$event->id}/roll")->assertRedirect();

        $this->assertSame(0, $player->fresh()->current_position);
    }

    #[Test]
    public function ticking_a_tile_is_refused_once_the_event_has_ended(): void
    {
        [$owner, $event] = $this->board(['event' => [
            'start_date' => now()->subDays(10),
            'end_date' => now()->subDay(),
        ]]);
        $this->fillTiles($event->board);

        $tile = Tile::where('board_id', $event->board->id)->where('position', 3)->firstOrFail();

        $this->actingAs($owner)->post("/events/{$event->id}/tiles/{$tile->id}/toggle")->assertRedirect();

        $this->assertSame(0, CompletedTile::where('tile_id', $tile->id)->count());
    }

    /** An event still running through the rest of its own end_date is not ended yet. */
    #[Test]
    public function rolling_still_works_on_the_last_day_of_the_event(): void
    {
        [$owner, $event] = $this->board(['event' => [
            'start_date' => now()->subDays(10),
            'end_date' => now(),
        ]]);
        $this->fillTiles($event->board);

        PlayerBoard::create([
            'user_id' => $owner->id,
            'board_id' => $event->board->id,
            'current_position' => 0,
        ]);

        $this->actingAs($owner)->post("/events/{$event->id}/roll");

        $this->assertGreaterThan(0, PlayerBoard::where('board_id', $event->board->id)->first()->current_position);
    }

    // ------------------------------------------------------------ the roll limit

    #[Test]
    public function the_daily_roll_limit_is_enforced(): void
    {
        [$owner, $event] = $this->board(['board' => ['dice_roll_limit' => 2]]);
        $this->fillTiles($event->board);

        $this->actingAs($owner)->post("/events/{$event->id}/roll");
        $this->actingAs($owner)->post("/events/{$event->id}/roll");
        $this->actingAs($owner)->post("/events/{$event->id}/roll")->assertSessionHas('board-save-error');

        $this->assertSame(2, PlayerBoard::where('board_id', $event->board->id)->first()->dice_rolls_today);
    }

    /**
     * The counter is per day, not per board — yesterday's rolls must not
     * count against today. `last_roll_date` is a cast datetime for exactly
     * this comparison; a missed cast here was a real 500 once.
     */
    #[Test]
    public function yesterdays_rolls_do_not_count_against_today(): void
    {
        [$owner, $event] = $this->board(['board' => ['dice_roll_limit' => 1]]);
        $this->fillTiles($event->board);

        PlayerBoard::create([
            'user_id' => $owner->id,
            'board_id' => $event->board->id,
            'current_position' => 0,
            'dice_rolls_today' => 5,
            'last_roll_date' => now()->subDay(),
        ]);

        $this->actingAs($owner)->post("/events/{$event->id}/roll")->assertSessionMissing('board-save-error');

        $this->assertSame(1, PlayerBoard::where('board_id', $event->board->id)->first()->dice_rolls_today);
    }

    #[Test]
    public function a_board_with_no_limit_keeps_taking_rolls(): void
    {
        [$owner, $event] = $this->board();
        $this->fillTiles($event->board);

        for ($i = 0; $i < 4; $i++) {
            $this->actingAs($owner)->post("/events/{$event->id}/roll")->assertSessionMissing('board-save-error');
        }

        $this->assertSame(4, PlayerBoard::where('board_id', $event->board->id)->first()->dice_rolls_today);
    }

    // ------------------------------------------------------------ snakes/ladders

    #[Test]
    public function a_ladder_carries_the_player_up(): void
    {
        [$owner, $event] = $this->board();
        $this->fillTiles($event->board);

        // Every square a d6 can reach from 0 is a ladder to the same place,
        // so the roll's randomness cannot decide the outcome.
        Tile::where('board_id', $event->board->id)
            ->whereBetween('position', [1, 6])
            ->update(['type' => 'LADDER', 'target_position' => 20]);

        $this->actingAs($owner)->post("/events/{$event->id}/roll")->assertRedirect();

        $this->assertSame(20, PlayerBoard::where('board_id', $event->board->id)->first()->current_position);
    }

    #[Test]
    public function a_snake_drops_the_player_back_down(): void
    {
        [$owner, $event] = $this->board();
        $this->fillTiles($event->board);

        Tile::where('board_id', $event->board->id)
            ->whereBetween('position', [11, 16])
            ->update(['type' => 'SNAKE', 'target_position' => 2]);

        $player = PlayerBoard::create([
            'user_id' => $owner->id,
            'board_id' => $event->board->id,
            'current_position' => 10,
        ]);

        $this->actingAs($owner)->post("/events/{$event->id}/roll");

        $this->assertSame(2, $player->fresh()->current_position);
    }

    /**
     * Sliding down a snake gives back the ground you covered — the tiles
     * between the snake's target and where you landed stop counting as done,
     * so you have to earn them again.
     */
    #[Test]
    public function a_snake_un_completes_the_tiles_it_slides_past(): void
    {
        [$owner, $event] = $this->board();
        $this->fillTiles($event->board);

        Tile::where('board_id', $event->board->id)
            ->whereBetween('position', [11, 16])
            ->update(['type' => 'SNAKE', 'target_position' => 5]);

        $player = PlayerBoard::create([
            'user_id' => $owner->id,
            'board_id' => $event->board->id,
            'current_position' => 10,
        ]);

        $tiles = Tile::where('board_id', $event->board->id)->orderBy('position')->get();

        foreach ($tiles as $tile) {
            CompletedTile::create([
                'id' => (string) str()->uuid(),
                'player_board_id' => $player->id,
                'tile_id' => $tile->id,
                'completed_via' => 'MANUAL',
            ]);
        }

        $this->actingAs($owner)->post("/events/{$event->id}/roll");

        $remaining = CompletedTile::where('player_board_id', $player->id)
            ->pluck('tile_id')
            ->map(fn ($id) => $tiles->firstWhere('id', $id)->position)
            ->sort()
            ->values()
            ->all();

        // Everything below the snake's target survives.
        $this->assertContains(4, $remaining);
        // The stretch it slid past does not.
        $this->assertNotContains(6, $remaining);
        $this->assertNotContains(10, $remaining);
    }

    // ----------------------------------------------------------- ticking a tile

    /**
     * On a board that trusts self-toggles (requires_approval off), a claim
     * still needs no proof — there is nothing for a host to check it
     * against.
     */
    #[Test]
    public function a_player_can_tick_a_tile_off_and_back_on(): void
    {
        [$owner, $event] = $this->board(['board' => ['requires_approval' => false]]);
        $this->fillTiles($event->board);

        $tile = Tile::where('board_id', $event->board->id)->where('position', 3)->firstOrFail();

        $this->actingAs($owner)->post("/events/{$event->id}/tiles/{$tile->id}/toggle")->assertRedirect();
        $this->assertSame(1, CompletedTile::where('tile_id', $tile->id)->count());

        $this->actingAs($owner)->post("/events/{$event->id}/tiles/{$tile->id}/toggle");
        $this->assertSame(0, CompletedTile::where('tile_id', $tile->id)->count());
    }

    /**
     * A board that requires approval refuses a claim with no proof — the
     * whole point of the review queue is something for a host to check.
     */
    #[Test]
    public function a_claim_without_proof_is_refused_when_approval_is_required(): void
    {
        [$owner, $event] = $this->board(['board' => ['requires_approval' => true]]);
        $this->fillTiles($event->board);

        $tile = Tile::where('board_id', $event->board->id)->where('position', 3)->firstOrFail();

        $this->actingAs($owner)
            ->post("/events/{$event->id}/tiles/{$tile->id}/toggle")
            ->assertSessionHasErrors('proof_url');

        $this->assertSame(0, CompletedTile::where('tile_id', $tile->id)->count());
    }

    /** With proof supplied, a board that requires approval lands the claim PENDING, not scored yet. */
    #[Test]
    public function a_claim_with_proof_lands_pending_on_a_board_that_requires_approval(): void
    {
        [$owner, $event] = $this->board(['board' => ['requires_approval' => true]]);
        $this->fillTiles($event->board);

        $tile = Tile::where('board_id', $event->board->id)->where('position', 3)->firstOrFail();

        $this->actingAs($owner)
            ->post("/events/{$event->id}/tiles/{$tile->id}/toggle", ['proof_url' => 'https://imgur.com/abc'])
            ->assertRedirect();

        $completed = CompletedTile::where('tile_id', $tile->id)->firstOrFail();
        $this->assertSame('PENDING', $completed->status);
        $this->assertSame('https://imgur.com/abc', $completed->proof_url);
    }

    /** Withdrawing a still-pending claim needs no proof — it's a bare POST clearing the row. */
    #[Test]
    public function a_pending_claim_can_be_withdrawn_without_proof(): void
    {
        [$owner, $event] = $this->board(['board' => ['requires_approval' => true]]);
        $this->fillTiles($event->board);

        $tile = Tile::where('board_id', $event->board->id)->where('position', 3)->firstOrFail();

        $this->actingAs($owner)->post("/events/{$event->id}/tiles/{$tile->id}/toggle", ['proof_url' => 'https://imgur.com/abc']);
        $this->assertSame(1, CompletedTile::where('tile_id', $tile->id)->count());

        $this->actingAs($owner)->post("/events/{$event->id}/tiles/{$tile->id}/toggle")->assertRedirect();
        $this->assertSame(0, CompletedTile::where('tile_id', $tile->id)->count());
    }

    /**
     * A rejected claim must NOT lock a player out the way an approved one
     * does — unlike a bingo square, this tile is the one they are standing
     * on, and refusing a retry would brick the entire board for them:
     * nothing past it is reachable without completing it first.
     */
    #[Test]
    public function a_rejected_claim_can_be_cleared_and_retried(): void
    {
        [$owner, $event] = $this->board(['board' => ['requires_approval' => true]]);
        $this->fillTiles($event->board);

        $tile = Tile::where('board_id', $event->board->id)->where('position', 3)->firstOrFail();

        $completed = CompletedTile::create([
            'id' => (string) str()->uuid(),
            'player_board_id' => PlayerBoard::firstOrCreate(
                ['user_id' => $owner->id, 'board_id' => $event->board->id],
                ['id' => (string) str()->uuid(), 'current_position' => 0],
            )->id,
            'tile_id' => $tile->id,
            'completed_via' => 'MANUAL',
            'status' => 'REJECTED',
            'reviewed_at' => now(),
        ]);

        // Clearing it out — same endpoint, empty body, same as withdrawing a pending one.
        $this->actingAs($owner)->post("/events/{$event->id}/tiles/{$tile->id}/toggle")->assertRedirect();
        $this->assertSame(0, CompletedTile::where('id', $completed->id)->count());

        // Then submitting fresh.
        $this->actingAs($owner)
            ->post("/events/{$event->id}/tiles/{$tile->id}/toggle", ['proof_url' => 'https://imgur.com/retry.png'])
            ->assertRedirect();

        $fresh = CompletedTile::where('tile_id', $tile->id)->firstOrFail();
        $this->assertSame('PENDING', $fresh->status);
    }

    /** Undoing an APPROVAL is the host's call — the one status a player cannot clear themselves. */
    #[Test]
    public function an_approved_claim_cannot_be_cleared_by_the_player(): void
    {
        [$owner, $event] = $this->board(['board' => ['requires_approval' => true]]);
        $this->fillTiles($event->board);

        $tile = Tile::where('board_id', $event->board->id)->where('position', 3)->firstOrFail();

        CompletedTile::create([
            'id' => (string) str()->uuid(),
            'player_board_id' => PlayerBoard::firstOrCreate(
                ['user_id' => $owner->id, 'board_id' => $event->board->id],
                ['id' => (string) str()->uuid(), 'current_position' => 0],
            )->id,
            'tile_id' => $tile->id,
            'completed_via' => 'MANUAL',
            'status' => 'APPROVED',
            'reviewed_at' => now(),
        ]);

        $this->actingAs($owner)
            ->post("/events/{$event->id}/tiles/{$tile->id}/toggle")
            ->assertSessionHas('board-save-error');

        $this->assertSame(1, CompletedTile::where('tile_id', $tile->id)->count());
    }

    /**
     * The tile id comes from the browser and is bound by id alone, so nothing
     * about the route says it belongs to the board being played. Ticking off
     * somebody else's tile counts towards your progress here, which on a
     * competitive board is simply a way to win without playing.
     */
    #[Test]
    public function a_tile_from_another_board_cannot_be_ticked_off_here(): void
    {
        [$owner, $event] = $this->board();
        $this->fillTiles($event->board);

        [, $other] = $this->board();
        $this->fillTiles($other->board);
        $stray = Tile::where('board_id', $other->board->id)->where('position', 3)->firstOrFail();

        $this->actingAs($owner)
            ->post("/events/{$event->id}/tiles/{$stray->id}/toggle")
            ->assertNotFound();

        $this->assertSame(0, CompletedTile::where('tile_id', $stray->id)->count());
    }

    // ----------------------------------------------------------------- access

    #[Test]
    public function a_stranger_cannot_roll_on_a_private_board(): void
    {
        [, $event] = $this->board(['event' => ['access_mode' => 'INVITE', 'is_listed' => false]]);
        $this->fillTiles($event->board);

        $stranger = User::factory()->create(['osrs_username' => 'Nosy']);

        $this->actingAs($stranger)->post("/events/{$event->id}/roll")->assertForbidden();

        $this->assertSame(0, PlayerBoard::where('board_id', $event->board->id)->count());
    }

    #[Test]
    public function rolling_requires_being_logged_in(): void
    {
        [, $event] = $this->board();

        $this->post("/events/{$event->id}/roll")->assertRedirect('/login');
    }

    // -------------------------------------------------------------- team boards

    /**
     * On a TEAM board the progress row belongs to the team, so two members
     * rolling is one piece moving twice — not two pieces.
     */
    #[Test]
    public function teammates_share_one_piece(): void
    {
        [$owner, $event] = $this->board(mode: 'TEAM');
        $this->fillTiles($event->board);

        $team = Team::create(['name' => 'Blue', 'owner_id' => $owner->id]);
        TeamMember::create(['team_id' => $team->id, 'user_id' => $owner->id, 'role' => 'OWNER']);

        $mate = User::factory()->create(['osrs_username' => 'Mate']);
        TeamMember::create(['team_id' => $team->id, 'user_id' => $mate->id, 'role' => 'MEMBER']);

        BoardTeam::create(['event_id' => $event->id, 'team_id' => $team->id]);

        $this->actingAs($owner)->post("/events/{$event->id}/roll");
        $this->actingAs($mate)->post("/events/{$event->id}/roll");

        $rows = PlayerBoard::where('board_id', $event->board->id)->get();

        $this->assertCount(1, $rows);
        $this->assertSame(2, $rows->first()->dice_rolls_today);
    }

    /**
     * "No team yet" is a state the page has an empty state for, not an error
     * — so it comes back as a message rather than a 403 or a 500.
     */
    #[Test]
    public function a_player_with_no_team_is_told_so_rather_than_erroring(): void
    {
        [, $event] = $this->board(mode: 'TEAM');
        $this->fillTiles($event->board);

        $loner = User::factory()->create(['osrs_username' => 'Loner']);

        $this->actingAs($loner)
            ->post("/events/{$event->id}/roll")
            ->assertRedirect()
            ->assertSessionHas('board-save-error');

        $this->assertSame(0, PlayerBoard::where('board_id', $event->board->id)->count());
    }
}
