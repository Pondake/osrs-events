<?php

namespace Tests\Feature;

use App\Models\BingoCard;
use App\Models\BingoCompletion;
use App\Models\BoardAuthor;
use App\Models\Event;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\BingoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Which shapes count as a line, server side.
 *
 * The twin of tests/js/bingoLines.test.js — deliberately asserting the same
 * counts. The card highlights lines in the browser and the standings score
 * them here, in different languages with nothing but these two files forcing
 * them to agree; a card that lights up a diagonal the leaderboard will not
 * credit is a page arguing with itself.
 */
class BingoWinLinesTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: User, 1: Event, 2: BingoCard} */
    private function card(int $size = 3, ?array $winLines = null): array
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

        $card = $event->bingoCard()->create([
            'size' => $size,
            'win_condition' => 'LINE',
            'win_lines' => $winLines,
        ]);
        app(BingoService::class)->ensureSquares($card);

        return [$host, $event, $card->fresh()];
    }

    private function approve(BingoCard $card, User $user, array $positions): void
    {
        foreach ($positions as $position) {
            BingoCompletion::create([
                'bingo_square_id' => $card->squares()->where('position', $position)->value('id'),
                'user_id' => $user->id,
                'marked_by' => $user->id,
                'status' => 'APPROVED',
            ]);
        }
    }

    // ------------------------------------------------------------ the shapes

    #[Test]
    public function a_three_by_three_has_eight_lines_by_default(): void
    {
        $this->assertCount(8, app(BingoService::class)->lines(3));
    }

    #[Test]
    public function the_line_count_scales_with_the_grid(): void
    {
        $bingo = app(BingoService::class);

        $this->assertCount(12, $bingo->lines(5));
        $this->assertCount(22, $bingo->lines(10));
    }

    #[Test]
    public function a_row_runs_left_to_right_and_a_column_top_to_bottom(): void
    {
        $bingo = app(BingoService::class);

        $this->assertSame([0, 1, 2], $bingo->lines(3, ['ROW'])[0]);
        $this->assertSame([0, 3, 6], $bingo->lines(3, ['COLUMN'])[0]);
        $this->assertSame([[0, 4, 8], [2, 4, 6]], $bingo->lines(3, ['DIAGONAL']));
    }

    #[Test]
    public function a_card_only_has_the_shapes_it_counts(): void
    {
        $bingo = app(BingoService::class);

        $this->assertCount(5, $bingo->lines(5, ['ROW']));
        $this->assertCount(2, $bingo->lines(5, ['DIAGONAL']));
        $this->assertCount(10, $bingo->lines(5, ['ROW', 'COLUMN']));
    }

    #[Test]
    public function nothing_counts_when_nothing_is_ticked(): void
    {
        $this->assertSame([], app(BingoService::class)->lines(5, []));
    }

    // ------------------------------------------------------------- the rules

    /** The setting has to reach the win check, not just the drawing. */
    #[Test]
    public function a_rows_only_card_is_not_won_by_a_diagonal(): void
    {
        [$host, , $card] = $this->card(3, ['ROW']);

        $this->approve($card, $host, [0, 4, 8]);

        $bingo = app(BingoService::class);
        $approved = $bingo->approvedPositions($card->fresh(), ['team_id' => null, 'user_id' => $host->id]);

        $this->assertFalse($bingo->hasWon($card->fresh(), $approved));
    }

    #[Test]
    public function a_rows_only_card_is_won_by_a_row(): void
    {
        [$host, , $card] = $this->card(3, ['ROW']);

        $this->approve($card, $host, [0, 1, 2]);

        $bingo = app(BingoService::class);
        $approved = $bingo->approvedPositions($card->fresh(), ['team_id' => null, 'user_id' => $host->id]);

        $this->assertTrue($bingo->hasWon($card->fresh(), $approved));
    }

    /** The line bonus is paid per completed line, so it follows too. */
    #[Test]
    public function the_line_bonus_only_pays_for_shapes_that_count(): void
    {
        [$host, $event, $card] = $this->card(3, ['ROW']);
        $card->update(['line_bonus' => 10]);

        $this->approve($card, $host, [0, 4, 8]);

        $row = app(BingoService::class)->standings($event, $card->fresh())->first();

        // Three squares at a point each, and no line bonus: the diagonal
        // they form is not a line on this card.
        $this->assertSame(3, $row['points']);
    }

    #[Test]
    public function the_line_bonus_pays_for_a_shape_that_does_count(): void
    {
        [$host, $event, $card] = $this->card(3, ['ROW']);
        $card->update(['line_bonus' => 10]);

        $this->approve($card, $host, [0, 1, 2]);

        $row = app(BingoService::class)->standings($event, $card->fresh())->first();

        $this->assertSame(13, $row['points']);
    }

    // --------------------------------------------------------- the defaults

    /**
     * Every card that existed before the column did behaved as all three,
     * and must keep doing so — a null here is "never chosen", not "nothing".
     */
    #[Test]
    public function a_card_with_no_setting_counts_every_shape(): void
    {
        [, , $card] = $this->card(3, null);

        $this->assertSame(BingoCard::LINE_KINDS, $card->winLines());
    }

    /** An empty array reads the same way, for the same reason. */
    #[Test]
    public function an_empty_setting_falls_back_rather_than_disabling_the_card(): void
    {
        [, , $card] = $this->card(3, []);

        $this->assertSame(BingoCard::LINE_KINDS, $card->winLines());
    }

    // ------------------------------------------------------------ the form

    #[Test]
    public function the_settings_form_can_change_which_shapes_count(): void
    {
        [$host, $event, $card] = $this->card(5);

        $this->actingAs($host)
            ->patch("/events/{$event->id}", ['win_lines' => ['DIAGONAL']])
            ->assertSessionHasNoErrors();

        $this->assertSame(['DIAGONAL'], $card->fresh()->winLines());
    }

    #[Test]
    public function an_unknown_shape_is_refused(): void
    {
        [$host, $event] = $this->card(5);

        $this->actingAs($host)
            ->patch("/events/{$event->id}", ['win_lines' => ['SPIRAL']])
            ->assertSessionHasErrors('win_lines.0');
    }

    /** "First line wins" with no shapes is a condition nothing can meet. */
    #[Test]
    public function an_empty_set_is_refused_by_the_form(): void
    {
        [$host, $event] = $this->card(5);

        $this->actingAs($host)
            ->patch("/events/{$event->id}", ['win_lines' => []])
            ->assertSessionHasErrors('win_lines');
    }

    #[Test]
    public function creating_an_event_stores_the_chosen_shapes(): void
    {
        $creator = User::factory()->create(['osrs_username' => 'Host']);
        $role = Role::findOrCreate('EDITOR', 'web');
        $role->givePermissionTo(Permission::findOrCreate('canCreateBoards', 'web'));
        $creator->assignRole($role);

        $this->actingAs($creator)->post('/events', [
            'title' => 'Rows only',
            'type' => 'BINGO',
            'mode' => 'SOLO',
            'access_mode' => 'OPEN',
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-15',
            'bingo_size' => 5,
            'win_condition' => 'LINE',
            'win_lines' => ['ROW', 'COLUMN'],
        ])->assertRedirect();

        $card = Event::where('title', 'Rows only')->firstOrFail()->bingoCard;

        $this->assertSame(['ROW', 'COLUMN'], $card->winLines());
    }
}
