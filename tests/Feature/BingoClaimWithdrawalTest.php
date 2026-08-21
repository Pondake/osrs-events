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
 * Withdrawing a claim.
 *
 * The same endpoint both claims and withdraws — posting to a square you have
 * already claimed takes it back. That is a compact API and a hazardous UI,
 * which is why the page now opens a dialog for a claimed square rather than
 * acting on the click; these are the server-side rules that dialog is built
 * on top of.
 */
class BingoClaimWithdrawalTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: User, 1: Event, 2: BingoCard} */
    private function card(bool $requiresApproval = true): array
    {
        $player = User::factory()->create(['osrs_username' => 'Pondake']);

        $event = Event::create([
            'title' => 'Card night',
            'type' => 'BINGO',
            'mode' => 'SOLO',
            'access_mode' => 'OPEN',
            'is_listed' => true,
        ]);
        BoardAuthor::create(['event_id' => $event->id, 'user_id' => $player->id, 'is_owner' => true]);

        $card = $event->bingoCard()->create([
            'size' => 3,
            'win_condition' => 'LINE',
            'requires_approval' => $requiresApproval,
        ]);
        app(BingoService::class)->ensureSquares($card);

        return [$player, $event, $card->fresh()];
    }

    #[Test]
    public function claiming_a_square_on_a_reviewed_card_creates_a_pending_claim(): void
    {
        [$player, $event, $card] = $this->card();
        $square = $card->squares()->where('position', 0)->firstOrFail();

        $this->actingAs($player)
            ->post("/events/{$event->id}/bingo/squares/{$square->id}/claim", ['proof_url' => 'https://i.imgur.com/x.png'])
            ->assertRedirect();

        $claim = $square->completions()->firstOrFail();

        $this->assertSame('PENDING', $claim->status);
        $this->assertSame('https://i.imgur.com/x.png', $claim->proof_url);
    }

    #[Test]
    public function claiming_twice_withdraws_the_pending_claim(): void
    {
        [$player, $event, $card] = $this->card();
        $square = $card->squares()->where('position', 0)->firstOrFail();

        $this->actingAs($player)->post("/events/{$event->id}/bingo/squares/{$square->id}/claim");
        $this->actingAs($player)->post("/events/{$event->id}/bingo/squares/{$square->id}/claim")->assertRedirect();

        $this->assertSame(0, $square->completions()->count());
    }

    /**
     * Undoing a host's decision is the host's call, not the claimant's.
     */
    #[Test]
    public function a_reviewed_claim_cannot_be_withdrawn_by_the_claimant(): void
    {
        [$player, $event, $card] = $this->card();
        $square = $card->squares()->where('position', 0)->firstOrFail();

        BingoCompletion::create([
            'bingo_square_id' => $square->id,
            'user_id' => $player->id,
            'marked_by' => $player->id,
            'status' => 'APPROVED',
            'reviewed_at' => now(),
        ]);

        $this->actingAs($player)
            ->post("/events/{$event->id}/bingo/squares/{$square->id}/claim")
            ->assertSessionHas('board-save-error');

        $this->assertSame(1, $square->completions()->count());
    }

    /**
     * On a card that does not review, a claim lands approved immediately and
     * is still the claimant's to take back — there is no host decision to
     * undo.
     */
    #[Test]
    public function a_claim_on_a_trusting_card_can_be_taken_back(): void
    {
        [$player, $event, $card] = $this->card(requiresApproval: false);
        $square = $card->squares()->where('position', 0)->firstOrFail();

        $this->actingAs($player)->post("/events/{$event->id}/bingo/squares/{$square->id}/claim");
        $this->assertSame('APPROVED', $square->completions()->firstOrFail()->status);

        $this->actingAs($player)->post("/events/{$event->id}/bingo/squares/{$square->id}/claim")->assertRedirect();

        $this->assertSame(0, $square->completions()->count());
    }

    // ---------------------------------------------- what the dialog renders

    /**
     * The dialog shows what was submitted and what the host said back, so
     * the page has to be given all of it — a rejection that explains nothing
     * is the thing players complain about, and a note left on an APPROVAL
     * was being written and then discarded entirely.
     */
    #[Test]
    public function the_page_carries_the_whole_claim_not_just_its_verdict(): void
    {
        [$player, $event, $card] = $this->card();
        $square = $card->squares()->where('position', 0)->firstOrFail();

        BingoCompletion::create([
            'bingo_square_id' => $square->id,
            'user_id' => $player->id,
            'marked_by' => $player->id,
            'status' => 'APPROVED',
            'reviewed_at' => now(),
            'review_note' => 'Nice one.',
            'proof_url' => 'https://i.imgur.com/x.png',
            'note' => 'Got it on the third try',
        ]);

        $claims = $this->actingAs($player)
            ->get("/events/{$event->id}")
            ->viewData('page')['props']['claims'];

        $claim = $claims[0];

        $this->assertSame('APPROVED', $claim['status']);
        // The half that used to be thrown away.
        $this->assertSame('Nice one.', $claim['reviewNote']);
        $this->assertSame('https://i.imgur.com/x.png', $claim['proofUrl']);
        $this->assertSame('Got it on the third try', $claim['note']);
        $this->assertNotNull($claim['reviewedAt']);
    }

    /** Late claims are refused rather than quietly accepted. */
    #[Test]
    public function a_claim_after_the_event_ended_is_refused(): void
    {
        [$player, $event, $card] = $this->card();
        $event->update(['start_date' => '2026-07-01', 'end_date' => '2026-07-31']);

        $square = $card->squares()->where('position', 0)->firstOrFail();

        $this->actingAs($player)
            ->post("/events/{$event->id}/bingo/squares/{$square->id}/claim")
            ->assertSessionHas('board-save-error');

        $this->assertSame(0, $square->completions()->count());
    }

    /**
     * A square id from another event must not score against this one — the
     * same class of mix-up as comparing a board id to an event id.
     */
    #[Test]
    public function a_square_from_another_card_cannot_be_claimed_here(): void
    {
        [$player, $event] = $this->card();
        [, , $otherCard] = $this->card();

        $stray = $otherCard->squares()->where('position', 0)->firstOrFail();

        $this->actingAs($player)
            ->post("/events/{$event->id}/bingo/squares/{$stray->id}/claim")
            ->assertNotFound();
    }
}
