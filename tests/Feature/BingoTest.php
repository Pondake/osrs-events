<?php

namespace Tests\Feature;

use App\Models\BingoCard;
use App\Models\BingoCompletion;
use App\Models\BoardAuthor;
use App\Models\Event;
use App\Models\Role;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Services\BingoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Bingo's rules. The line detection is the only real logic in the type, and
 * it has to live server-side or the server has no way to agree with what a
 * player was shown.
 */
class BingoTest extends TestCase
{
    use RefreshDatabase;

    private function bingo(): BingoService
    {
        return app(BingoService::class);
    }

    private function event(array $attributes = []): Event
    {
        return Event::create([
            'title' => 'Clan Bingo',
            'type' => 'BINGO',
            'mode' => 'SOLO',
            'access_mode' => 'OPEN',
            'is_listed' => true,
            ...$attributes,
        ]);
    }

    private function card(Event $event, int $size = 5, string $win = 'LINE'): BingoCard
    {
        $card = $event->bingoCard()->create(['size' => $size, 'win_condition' => $win]);
        $this->bingo()->ensureSquares($card);

        return $card->fresh();
    }

    /**
     * Somebody who runs this event.
     *
     * These three tests used a site admin, which worked only because
     * canEditEvent() used to let any admin edit any event. It no longer does
     * — on the public side an admin is an ordinary user — so the fixture now
     * says what the test names always claimed.
     */
    private function author(Event $event): User
    {
        $author = User::factory()->create(['osrs_username' => 'Author']);

        BoardAuthor::create(['event_id' => $event->id, 'user_id' => $author->id, 'is_owner' => true]);

        return $author;
    }

    private function player(): User
    {
        return User::factory()->create(['osrs_username' => 'Pondake']);
    }

    // -------------------------------------------------------------- lines

    #[Test]
    public function a_square_card_has_rows_columns_and_both_diagonals(): void
    {
        // 5 rows + 5 columns + 2 diagonals.
        $this->assertCount(12, $this->bingo()->lines(5));
        // 3 + 3 + 2.
        $this->assertCount(8, $this->bingo()->lines(3));
    }

    #[Test]
    public function the_lines_hold_the_positions_you_would_expect(): void
    {
        $lines = $this->bingo()->lines(3);

        $this->assertContains([0, 1, 2], $lines, 'top row');
        $this->assertContains([0, 3, 6], $lines, 'left column');
        $this->assertContains([0, 4, 8], $lines, 'leading diagonal');
        $this->assertContains([2, 4, 6], $lines, 'counter diagonal');
    }

    #[Test]
    public function a_full_row_counts_as_a_completed_line(): void
    {
        $this->assertCount(1, $this->bingo()->completedLines(3, [0, 1, 2]));
    }

    #[Test]
    public function a_scattering_of_squares_completes_nothing(): void
    {
        $this->assertSame([], $this->bingo()->completedLines(3, [0, 1, 5, 7]));
    }

    /** The centre square sits on four lines, so it must not count as any. */
    #[Test]
    public function one_shared_square_does_not_complete_a_line_on_its_own(): void
    {
        $this->assertSame([], $this->bingo()->completedLines(3, [4]));
    }

    // ------------------------------------------------------------ winning

    #[Test]
    public function a_line_card_is_won_by_one_line(): void
    {
        $card = $this->card($this->event(), 3);

        $this->assertTrue($this->bingo()->hasWon($card, [0, 1, 2]));
        $this->assertFalse($this->bingo()->hasWon($card, [0, 1]));
    }

    #[Test]
    public function a_full_house_card_needs_every_square(): void
    {
        $card = $this->card($this->event(), 3, 'FULL_HOUSE');

        $this->assertFalse($this->bingo()->hasWon($card, [0, 1, 2]), 'a line is not enough');
        $this->assertTrue($this->bingo()->hasWon($card, range(0, 8)));
    }

    /** An empty card must not read as already won by nobody doing anything. */
    #[Test]
    public function a_card_with_no_squares_cannot_be_won(): void
    {
        $event = $this->event();
        $card = $event->bingoCard()->create(['size' => 3, 'win_condition' => 'FULL_HOUSE']);

        $this->assertFalse($this->bingo()->hasWon($card->fresh(), []));
    }

    // -------------------------------------------------------- competitors

    #[Test]
    public function a_solo_event_scores_against_the_user(): void
    {
        $user = $this->player();

        $this->assertSame(
            ['team_id' => null, 'user_id' => $user->id],
            $this->bingo()->competitorFor($this->event(), $user),
        );
    }

    #[Test]
    public function a_team_event_scores_against_the_team(): void
    {
        $event = $this->event(['mode' => 'TEAM']);
        $user = $this->player();
        $team = Team::create(['name' => 'Reds']);
        TeamMember::create(['team_id' => $team->id, 'user_id' => $user->id]);
        $event->eventTeams()->create(['team_id' => $team->id]);

        $this->assertSame(
            ['team_id' => $team->id, 'user_id' => null],
            $this->bingo()->competitorFor($event, $user),
        );
    }

    /** No team means no competitor — better than scoring against nobody. */
    #[Test]
    public function a_team_event_has_no_competitor_for_someone_with_no_team(): void
    {
        $this->assertNull(
            $this->bingo()->competitorFor($this->event(['mode' => 'TEAM']), $this->player()),
        );
    }

    // ------------------------------------------------------------- routes

    #[Test]
    public function the_page_renders_the_card(): void
    {
        $event = $this->event();
        $this->card($event, 5);

        $this->actingAs($this->player())
            ->get("/events/{$event->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Events/Bingo')
                ->where('card.size', 5)
                ->has('card.squares', 25));
    }

    #[Test]
    public function claiming_a_square_and_withdrawing_it_again(): void
    {
        $event = $this->event();
        $card = $this->card($event, 3);
        $square = $card->squares()->orderBy('position')->first();
        $user = $this->player();

        $this->actingAs($user)->post("/events/{$event->id}/bingo/squares/{$square->id}/claim", ['proof_url' => 'https://i.imgur.com/x.png']);
        $this->assertSame(1, BingoCompletion::count());

        $this->actingAs($user)->post("/events/{$event->id}/bingo/squares/{$square->id}/claim");
        $this->assertSame(0, BingoCompletion::count());
    }

    /**
     * The same class of bug as comparing a board id to an event id: a square
     * from another event must not tick against this one.
     */
    #[Test]
    public function a_square_from_another_event_cannot_be_ticked(): void
    {
        $mine = $this->event();
        $this->card($mine, 3);

        $theirs = $this->event(['title' => 'Someone else']);
        $foreign = $this->card($theirs, 3)->squares()->first();

        $this->actingAs($this->player())
            ->post("/events/{$mine->id}/bingo/squares/{$foreign->id}/claim")
            ->assertNotFound();
    }

    #[Test]
    public function a_player_cannot_edit_a_square(): void
    {
        $event = $this->event();
        $square = $this->card($event, 3)->squares()->first();

        $this->actingAs($this->player())
            ->patch("/events/{$event->id}/bingo/squares/{$square->id}", ['title_override' => 'Mine now'])
            ->assertForbidden();
    }

    #[Test]
    public function an_author_can_set_what_a_square_asks_for(): void
    {
        $event = $this->event();
        $square = $this->card($event, 3)->squares()->first();
        $author = $this->author($event);

        $this->actingAs($author)
            ->patch("/events/{$event->id}/bingo/squares/{$square->id}", ['title_override' => 'Kill 50 cows']);

        $this->assertSame('Kill 50 cows', $square->fresh()->title_override);
    }

    /**
     * Shrinking a card would delete squares other people have completions
     * on. A size dropdown must not be able to erase progress silently.
     */
    #[Test]
    public function shrinking_a_card_with_progress_outside_the_new_grid_is_refused(): void
    {
        $event = $this->event();
        $card = $this->card($event, 5);
        $author = $this->author($event);

        $last = $card->squares()->orderByDesc('position')->first();
        BingoCompletion::create([
            'bingo_square_id' => $last->id,
            'user_id' => $author->id,
            'marked_by' => $author->id,
        ]);

        $this->actingAs($author)
            ->patch("/events/{$event->id}/bingo", ['size' => 3])
            ->assertSessionHas('board-save-error');

        $this->assertSame(5, $card->fresh()->size);
        $this->assertSame(25, $card->squares()->count());
    }

    #[Test]
    public function growing_a_card_adds_the_new_squares(): void
    {
        $event = $this->event();
        $card = $this->card($event, 3);

        $this->actingAs($this->author($event))->patch("/events/{$event->id}/bingo", ['size' => 5]);

        $this->assertSame(25, $card->fresh()->squares()->count());
    }

    #[Test]
    public function creating_a_bingo_event_builds_its_card(): void
    {
        $admin = tap($this->player())->assignRole(Role::findOrCreate('ADMIN', 'web'));

        $this->actingAs($admin)->post('/events', [
            'title' => 'New bingo',
            'type' => 'BINGO',
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-15',
            'bingo_size' => 4,
            'win_condition' => 'FULL_HOUSE',
        ]);

        $card = Event::where('title', 'New bingo')->firstOrFail()->bingoCard;

        $this->assertNotNull($card);
        $this->assertSame(4, $card->size);
        $this->assertSame('FULL_HOUSE', $card->win_condition);
        $this->assertSame(16, $card->squares()->count());
    }

    /**
     * A BINGO event whose card has not been made yet renders one on the way
     * in — and that path was a 500 for as long as it existed. The card was
     * created with nothing but an id, so `size` was null on the instance in
     * hand (the column default only reaches PHP on a re-read) and the line
     * counter blew up on it a few statements later. Only reachable on an
     * event created outside the normal flow, which is exactly the kind of
     * row a migration or an import leaves behind.
     */
    #[Test]
    public function a_bingo_event_with_no_card_yet_still_opens(): void
    {
        $event = $this->event();

        $this->actingAs($this->player())->get("/events/{$event->id}")->assertOk();

        $card = $event->fresh()->bingoCard;

        $this->assertNotNull($card);
        $this->assertSame(5, $card->size);
        $this->assertSame(25, $card->squares()->count());
    }
}
