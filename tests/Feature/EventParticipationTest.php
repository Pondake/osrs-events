<?php

namespace Tests\Feature;

use App\Models\BingoCard;
use App\Models\Board;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\EventStanding;
use App\Models\PlayerBoard;
use App\Models\Tile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Joining an event — the same action whatever kind of event it is.
 *
 * This used to exist only for races, under the name "enter". Every other type
 * inferred participation from whatever you happened to write while playing,
 * which gave bingo no answer at all until a square was claimed, and made
 * merely opening a Snakes & Ladders board the same thing as playing it.
 */
class EventParticipationTest extends TestCase
{
    use RefreshDatabase;

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

    private function board(): Event
    {
        $event = $this->event('SNAKES_LADDERS');
        $board = Board::create(['event_id' => $event->id, 'size' => 'SIZE_5X5']);

        foreach (range(0, 24) as $position) {
            Tile::create(['board_id' => $board->id, 'position' => $position, 'type' => 'NORMAL']);
        }

        return $event->fresh();
    }

    private function player(string $name = 'Pondake'): User
    {
        return User::factory()->create(['osrs_username' => $name]);
    }

    private function joined(User $user, Event $event): bool
    {
        return EventParticipant::where(['event_id' => $event->id, 'user_id' => $user->id])->exists();
    }

    // ------------------------------------------------------------- joining

    #[Test]
    public function every_event_type_can_be_joined(): void
    {
        Http::fake(['api.wiseoldman.net/*' => Http::response(['data' => ['skills' => []]])]);

        $events = [
            $this->board(),
            $this->event('BINGO'),
            $this->event('SKILL_RACE', [
                'metric' => 'mining',
                'start_date' => Carbon::now()->subWeek(),
                'end_date' => Carbon::now()->addWeek(),
            ]),
        ];

        foreach ($events as $event) {
            $user = $this->player("P{$event->type}");

            $this->actingAs($user)->post("/events/{$event->id}/join")->assertRedirect();

            $this->assertTrue($this->joined($user, $event), "{$event->type} could not be joined");
        }
    }

    /** A race still enters the standings, because that is what playing one is. */
    #[Test]
    public function joining_a_race_enters_the_standings(): void
    {
        Http::fake(['api.wiseoldman.net/*' => Http::response(['data' => ['skills' => []]])]);

        $event = $this->event('SKILL_RACE', [
            'metric' => 'mining',
            'start_date' => Carbon::now()->subWeek(),
            'end_date' => Carbon::now()->addWeek(),
        ]);

        $this->actingAs($this->player())->post("/events/{$event->id}/join")->assertRedirect();

        $this->assertSame('Pondake', EventStanding::firstOrFail()->username);
    }

    /**
     * A race without an RSN cannot be entered, and must not leave the person
     * listed as taking part in a race they are not in.
     */
    #[Test]
    public function a_race_join_that_cannot_enter_records_nothing(): void
    {
        $event = $this->event('SKILL_RACE', [
            'metric' => 'mining',
            'start_date' => Carbon::now()->subWeek(),
            'end_date' => Carbon::now()->addWeek(),
        ]);

        $user = $this->player();
        $user->forceFill(['osrs_username' => null])->save();

        // The gate middleware sends them off to add one first.
        $this->actingAs($user)->post("/events/{$event->id}/join")->assertRedirect('/welcome/osrs-username');

        $this->assertFalse($this->joined($user, $event));
        $this->assertSame(0, EventStanding::count());
    }

    /** Joining a board hands out the board to play on. */
    #[Test]
    public function joining_a_board_creates_the_player_board(): void
    {
        $event = $this->board();
        $user = $this->player();

        $this->actingAs($user)->post("/events/{$event->id}/join");

        $this->assertSame(1, PlayerBoard::where('user_id', $user->id)->count());
    }

    #[Test]
    public function joining_twice_is_not_two_rows(): void
    {
        $event = $this->event('BINGO');
        $user = $this->player();

        $this->actingAs($user)->post("/events/{$event->id}/join");
        $this->actingAs($user)->post("/events/{$event->id}/join");

        $this->assertSame(1, EventParticipant::where('user_id', $user->id)->count());
    }

    // ------------------------------------------------------------- looking

    /**
     * The reason this exists at all. Opening a board used to create a player
     * board for whoever looked, so every passer-by turned up in the player
     * list and on the leaderboard at square one.
     */
    #[Test]
    public function merely_opening_a_board_does_not_join_it(): void
    {
        $event = $this->board();

        $this->actingAs($this->player())->get("/events/{$event->id}")->assertOk();

        $this->assertSame(0, PlayerBoard::count());
        $this->assertSame(0, EventParticipant::count());
    }

    /** But playing is joining — nobody should have to press a button twice. */
    #[Test]
    public function rolling_the_dice_joins(): void
    {
        $event = $this->board();
        $user = $this->player();

        $this->actingAs($user)->post("/events/{$event->id}/roll");

        $this->assertTrue($this->joined($user, $event));
    }

    #[Test]
    public function claiming_a_bingo_square_joins(): void
    {
        $event = $this->event('BINGO', ['end_date' => Carbon::now()->addWeek()]);
        $user = $this->player();

        // The card and its squares are created on first view.
        $this->actingAs($user)->get("/events/{$event->id}")->assertOk();

        $square = BingoCard::firstOrFail()->squares()->orderBy('position')->firstOrFail();

        $this->actingAs($user)->post("/events/{$event->id}/bingo/squares/{$square->id}/claim");

        $this->assertTrue($this->joined($user, $event));
    }

    // ------------------------------------------------------------- leaving

    #[Test]
    public function leaving_removes_the_record(): void
    {
        $event = $this->event('BINGO');
        $user = $this->player();
        $this->actingAs($user)->post("/events/{$event->id}/join");

        $this->actingAs($user)->delete("/events/{$event->id}/join")->assertRedirect();

        $this->assertFalse($this->joined($user, $event));
    }

    /**
     * Leaving must not quietly delete something somebody did. A board past
     * square one is a record of play, and the message says to ask a host.
     */
    #[Test]
    public function leaving_is_refused_once_there_is_progress(): void
    {
        $event = $this->board();
        $user = $this->player();
        $this->actingAs($user)->post("/events/{$event->id}/join");

        PlayerBoard::where('user_id', $user->id)->update(['current_position' => 6]);

        $this->actingAs($user)
            ->delete("/events/{$event->id}/join")
            ->assertSessionHas('board-save-error');

        $this->assertTrue($this->joined($user, $event));
    }

    /** A fresh board holds nothing worth keeping, so it goes with them. */
    #[Test]
    public function leaving_an_untouched_board_takes_the_player_board_with_it(): void
    {
        $event = $this->board();
        $user = $this->player();
        $this->actingAs($user)->post("/events/{$event->id}/join");

        $this->actingAs($user)->delete("/events/{$event->id}/join");

        $this->assertSame(0, PlayerBoard::count());
    }

    // -------------------------------------------------------- what it feeds

    /** The whole point of the record: a list of what somebody is playing. */
    #[Test]
    public function a_joined_event_shows_up_on_my_events(): void
    {
        $event = $this->event('BINGO');
        $user = $this->player();
        $this->actingAs($user)->post("/events/{$event->id}/join");

        $this->assertSame(1, Event::playedBy($user)->count());
    }

    #[Test]
    public function the_event_page_says_whether_you_have_joined(): void
    {
        $event = $this->event('BINGO');
        $user = $this->player();

        $this->actingAs($user)->get("/events/{$event->id}")
            ->assertInertia(fn ($page) => $page->where('joined', false));

        $this->actingAs($user)->post("/events/{$event->id}/join");

        $this->actingAs($user)->get("/events/{$event->id}")
            ->assertInertia(fn ($page) => $page->where('joined', true));
    }
}
