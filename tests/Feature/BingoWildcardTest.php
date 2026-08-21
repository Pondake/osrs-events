<?php

namespace Tests\Feature;

use App\Models\BingoCard;
use App\Models\BingoCompletion;
use App\Models\BoardAuthor;
use App\Models\Event;
use App\Models\User;
use App\Services\BingoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The free square.
 *
 * The rule that makes it worth having, and the one thing that would make it
 * useless: it counts for **every** competitor at once. A completion row
 * belongs to exactly one of them, so a wildcard cannot be modelled as a
 * pre-approved claim — it is merged in wherever positions are counted, and
 * both places that count them have to agree.
 */
class BingoWildcardTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: User, 1: Event, 2: BingoCard} */
    private function card(int $size = 3): array
    {
        $host = User::factory()->create(['osrs_username' => 'Host']);

        $event = Event::create([
            'title' => 'Card night',
            'type' => 'BINGO',
            'mode' => 'SOLO',
            'access_mode' => 'OPEN',
            'is_listed' => true,
        ]);
        BoardAuthor::create(['event_id' => $event->id, 'user_id' => $host->id, 'is_owner' => true]);

        $card = $event->bingoCard()->create(['size' => $size, 'win_condition' => 'LINE']);
        app(BingoService::class)->ensureSquares($card);

        return [$host, $event, $card->fresh()];
    }

    #[Test]
    public function a_host_can_mark_a_square_as_free(): void
    {
        [$host, $event, $card] = $this->card();

        $square = $card->squares()->where('position', 4)->firstOrFail();

        $this->actingAs($host)
            ->patch("/events/{$event->id}/bingo/squares/{$square->id}", ['is_wildcard' => true])
            ->assertRedirect();

        $this->assertTrue($square->fresh()->is_wildcard);
    }

    /** The point of the whole feature. */
    #[Test]
    public function a_free_square_counts_for_a_competitor_who_never_claimed_it(): void
    {
        [, , $card] = $this->card();

        $card->squares()->where('position', 4)->update(['is_wildcard' => true]);

        $stranger = User::factory()->create(['osrs_username' => 'Stranger']);

        $positions = app(BingoService::class)
            ->approvedPositions($card->fresh(), ['team_id' => null, 'user_id' => $stranger->id]);

        $this->assertSame([4], $positions);
    }

    /**
     * The middle of a 3x3 sits on both diagonals plus the middle row and
     * column — so a free centre plus two corners is already a line.
     */
    #[Test]
    public function a_free_centre_completes_a_line_with_the_two_ends(): void
    {
        [$host, , $card] = $this->card();

        $card->squares()->where('position', 4)->update(['is_wildcard' => true]);

        $bingo = app(BingoService::class);
        $competitor = ['team_id' => null, 'user_id' => $host->id];

        foreach ([0, 8] as $position) {
            BingoCompletion::create([
                'bingo_square_id' => $card->squares()->where('position', $position)->value('id'),
                'user_id' => $host->id,
                'marked_by' => $host->id,
                'status' => 'APPROVED',
            ]);
        }

        $approved = $bingo->approvedPositions($card->fresh(), $competitor);

        $this->assertContains(4, $approved);
        $this->assertTrue($bingo->hasWon($card->fresh(), $approved));
    }

    /**
     * The card and the standings have to agree. Counting a wildcard on the
     * grid but not in the score is the failure mode that makes a player
     * think the leaderboard is broken.
     */
    #[Test]
    public function the_standings_credit_a_free_square_too(): void
    {
        [$host, $event, $card] = $this->card();

        $card->squares()->where('position', 4)->update(['is_wildcard' => true]);

        BingoCompletion::create([
            'bingo_square_id' => $card->squares()->where('position', 0)->value('id'),
            'user_id' => $host->id,
            'marked_by' => $host->id,
            'status' => 'APPROVED',
        ]);

        $row = app(BingoService::class)->standings($event, $card->fresh())->first();

        // One claimed square plus the free one, both worth the default point.
        $this->assertSame(2, $row['points']);
    }

    /** Claiming one would be a queue entry that changes no score. */
    #[Test]
    public function a_free_square_cannot_be_claimed(): void
    {
        [$host, $event, $card] = $this->card();

        $square = $card->squares()->where('position', 4)->firstOrFail();
        $square->update(['is_wildcard' => true]);

        $this->actingAs($host)
            ->post("/events/{$event->id}/bingo/squares/{$square->id}/claim")
            ->assertSessionHas('board-save-error');

        $this->assertSame(0, $square->completions()->count());
    }

    /**
     * No default label. The square draws a star, which says what it is more
     * clearly than the word does — printing "Free square" under the star is
     * the label saying it twice, and it crowds a square that is only ~90px
     * wide. A host who wants it named can still say so.
     */
    #[Test]
    public function a_free_square_has_no_label_of_its_own_but_takes_an_override(): void
    {
        [, , $card] = $this->card();

        $square = $card->squares()->where('position', 4)->firstOrFail();
        $square->update(['is_wildcard' => true]);

        $this->assertNull($square->fresh()->label());

        $square->update(['title_override' => 'On the house']);

        $this->assertSame('On the house', $square->fresh()->label());
    }
}
