<?php

namespace Tests\Feature;

use App\Models\BingoCard;
use App\Models\BingoCompletion;
use App\Models\Event;
use App\Models\Role;
use App\Models\User;
use App\Services\BingoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The claim-and-review workflow, which is what separates a bingo tracker from
 * a shared checklist — see docs/bingo-research.md.
 *
 * The rule everything here rests on: **only APPROVED claims score.** A pending
 * claim is visible to the person who made it and invisible to the standings.
 */
class BingoReviewTest extends TestCase
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

    private function card(Event $event, array $attributes = []): BingoCard
    {
        $card = $event->bingoCard()->create(['size' => 3, ...$attributes]);
        $this->bingo()->ensureSquares($card);

        return $card->fresh();
    }

    private function player(): User
    {
        return User::factory()->create(['osrs_username' => 'Pondake']);
    }

    private function host(): User
    {
        return tap(User::factory()->create(['osrs_username' => 'Host']))
            ->assignRole(Role::findOrCreate('ADMIN', 'web'));
    }

    // -------------------------------------------------------------- claims

    #[Test]
    public function a_claim_on_a_reviewed_card_starts_pending(): void
    {
        $event = $this->event();
        $square = $this->card($event)->squares()->first();

        $this->actingAs($this->player())
            ->post("/events/{$event->id}/bingo/squares/{$square->id}/claim", [
                'proof_url' => 'https://i.imgur.com/proof.png',
                'note' => 'Got it on the third kill',
            ]);

        $claim = BingoCompletion::firstOrFail();
        $this->assertSame('PENDING', $claim->status);
        $this->assertSame('https://i.imgur.com/proof.png', $claim->proof_url);
    }

    /** A clan that trusts everyone should not have to review every square. */
    #[Test]
    public function a_claim_on_a_trusting_card_is_approved_immediately(): void
    {
        $event = $this->event();
        $square = $this->card($event, ['requires_approval' => false])->squares()->first();

        $this->actingAs($this->player())
            ->post("/events/{$event->id}/bingo/squares/{$square->id}/claim");

        $this->assertSame('APPROVED', BingoCompletion::firstOrFail()->status);
    }

    #[Test]
    public function a_pending_claim_scores_nothing(): void
    {
        $event = $this->event();
        $card = $this->card($event);
        $user = $this->player();

        foreach ($card->squares()->orderBy('position')->take(3)->get() as $square) {
            BingoCompletion::create([
                'bingo_square_id' => $square->id,
                'user_id' => $user->id,
                'status' => 'PENDING',
            ]);
        }

        // A whole row claimed, none of it approved: no standings row at all.
        $this->assertCount(0, $this->bingo()->standings($event, $card));
        $this->assertSame([], $this->bingo()->approvedPositions($card, ['team_id' => null, 'user_id' => $user->id]));
    }

    #[Test]
    public function a_rejected_claim_scores_nothing_either(): void
    {
        $event = $this->event();
        $card = $this->card($event);
        $user = $this->player();

        BingoCompletion::create([
            'bingo_square_id' => $card->squares()->first()->id,
            'user_id' => $user->id,
            'status' => 'REJECTED',
        ]);

        $this->assertCount(0, $this->bingo()->standings($event, $card));
    }

    /** A claim closes with the event — "after the deadline" is not a judgement call. */
    #[Test]
    public function a_claim_after_the_event_ended_is_refused(): void
    {
        $event = $this->event(['end_date' => Carbon::now()->subWeek()]);
        $square = $this->card($event)->squares()->first();

        $this->actingAs($this->player())
            ->post("/events/{$event->id}/bingo/squares/{$square->id}/claim")
            ->assertSessionHas('board-save-error');

        $this->assertSame(0, BingoCompletion::count());
    }

    #[Test]
    public function a_proof_link_must_be_a_url(): void
    {
        $event = $this->event();
        $square = $this->card($event)->squares()->first();

        $this->actingAs($this->player())
            ->post("/events/{$event->id}/bingo/squares/{$square->id}/claim", ['proof_url' => 'not a url'])
            ->assertSessionHasErrors('proof_url');
    }

    // ------------------------------------------------------------ withdraw

    #[Test]
    public function a_player_can_withdraw_their_own_pending_claim(): void
    {
        $event = $this->event();
        $square = $this->card($event)->squares()->first();
        $user = $this->player();

        $this->actingAs($user)->post("/events/{$event->id}/bingo/squares/{$square->id}/claim");
        $this->actingAs($user)->post("/events/{$event->id}/bingo/squares/{$square->id}/claim");

        $this->assertSame(0, BingoCompletion::count());
    }

    /** Undoing a host's decision is not the claimant's call. */
    #[Test]
    public function a_player_cannot_withdraw_a_claim_a_host_has_ruled_on(): void
    {
        $event = $this->event();
        $card = $this->card($event);
        $square = $card->squares()->first();
        $user = $this->player();

        BingoCompletion::create([
            'bingo_square_id' => $square->id,
            'user_id' => $user->id,
            'status' => 'APPROVED',
        ]);

        $this->actingAs($user)
            ->post("/events/{$event->id}/bingo/squares/{$square->id}/claim")
            ->assertSessionHas('board-save-error');

        $this->assertSame(1, BingoCompletion::count());
    }

    // -------------------------------------------------------------- review

    #[Test]
    public function a_host_can_approve_a_claim(): void
    {
        $event = $this->event();
        $card = $this->card($event);
        $host = $this->host();

        $claim = BingoCompletion::create([
            'bingo_square_id' => $card->squares()->first()->id,
            'user_id' => $this->player()->id,
            'status' => 'PENDING',
        ]);

        $this->actingAs($host)->patch("/events/{$event->id}/bingo/claims/{$claim->id}", ['status' => 'APPROVED']);

        $claim->refresh();
        $this->assertSame('APPROVED', $claim->status);
        $this->assertSame($host->id, $claim->reviewed_by);
        $this->assertNotNull($claim->reviewed_at);
    }

    /** Kept rather than deleted, so the claimant can see why. */
    #[Test]
    public function a_rejection_keeps_the_row_and_its_reason(): void
    {
        $event = $this->event();
        $card = $this->card($event);

        $claim = BingoCompletion::create([
            'bingo_square_id' => $card->squares()->first()->id,
            'user_id' => $this->player()->id,
            'status' => 'PENDING',
        ]);

        $this->actingAs($this->host())->patch("/events/{$event->id}/bingo/claims/{$claim->id}", [
            'status' => 'REJECTED',
            'review_note' => 'Chatbox is cropped out',
        ]);

        $claim->refresh();
        $this->assertSame('REJECTED', $claim->status);
        $this->assertSame('Chatbox is cropped out', $claim->review_note);
    }

    #[Test]
    public function a_player_cannot_review_anything(): void
    {
        $event = $this->event();
        $card = $this->card($event);

        $claim = BingoCompletion::create([
            'bingo_square_id' => $card->squares()->first()->id,
            'user_id' => $this->player()->id,
            'status' => 'PENDING',
        ]);

        $this->actingAs($this->player())
            ->patch("/events/{$event->id}/bingo/claims/{$claim->id}", ['status' => 'APPROVED'])
            ->assertForbidden();
    }

    /** A claim from another event must not be reviewable through this one. */
    #[Test]
    public function a_claim_from_another_event_cannot_be_reviewed(): void
    {
        $mine = $this->event();
        $this->card($mine);

        $theirs = $this->event(['title' => 'Someone else']);
        $foreignSquare = $this->card($theirs)->squares()->first();

        $claim = BingoCompletion::create([
            'bingo_square_id' => $foreignSquare->id,
            'user_id' => $this->player()->id,
            'status' => 'PENDING',
        ]);

        $this->actingAs($this->host())
            ->patch("/events/{$mine->id}/bingo/claims/{$claim->id}", ['status' => 'APPROVED'])
            ->assertNotFound();
    }

    #[Test]
    public function the_queue_shows_hosts_what_is_waiting_oldest_first(): void
    {
        $event = $this->event();
        $card = $this->card($event);
        $squares = $card->squares()->orderBy('position')->take(2)->get();
        $user = $this->player();

        $older = BingoCompletion::create([
            'bingo_square_id' => $squares[0]->id, 'user_id' => $user->id, 'status' => 'PENDING',
        ]);
        $older->forceFill(['created_at' => Carbon::now()->subHour()])->save();

        BingoCompletion::create([
            'bingo_square_id' => $squares[1]->id, 'user_id' => $user->id, 'status' => 'APPROVED',
        ]);

        $queue = $this->bingo()->pendingQueue($card);

        // Only the pending one, and the approved claim is not in the queue.
        $this->assertCount(1, $queue);
        $this->assertSame($older->id, $queue->first()['id']);
    }

    #[Test]
    public function the_page_gives_hosts_the_queue_and_players_nothing(): void
    {
        $event = $this->event();
        $card = $this->card($event);

        BingoCompletion::create([
            'bingo_square_id' => $card->squares()->first()->id,
            'user_id' => $this->player()->id,
            'status' => 'PENDING',
        ]);

        $this->actingAs($this->host())
            ->get("/events/{$event->id}")
            ->assertInertia(fn ($page) => $page->has('pending', 1));

        $this->actingAs($this->player())
            ->get("/events/{$event->id}")
            ->assertInertia(fn ($page) => $page->has('pending', 0));
    }

    // -------------------------------------------------------------- points

    #[Test]
    public function points_come_from_the_squares_plus_the_line_bonus(): void
    {
        $event = $this->event();
        $card = $this->card($event, ['line_bonus' => 10]);

        // Top row worth 5, 3 and 2; the rest default to 1.
        $squares = $card->squares()->orderBy('position')->get();
        $squares[0]->update(['points' => 5]);
        $squares[1]->update(['points' => 3]);
        $squares[2]->update(['points' => 2]);

        $points = $card->squares()->pluck('points', 'position')->map(fn ($p) => (int) $p)->all();

        // 5 + 3 + 2 = 10 in tiles, plus one completed line at 10 = 20.
        $this->assertSame(20, $this->bingo()->score($card->fresh(), [0, 1, 2], $points));
        // Two squares, no line: just the tile points.
        $this->assertSame(8, $this->bingo()->score($card->fresh(), [0, 1], $points));
    }

    #[Test]
    public function the_standings_rank_by_points_not_square_count(): void
    {
        $event = $this->event();
        $card = $this->card($event);

        $squares = $card->squares()->orderBy('position')->get();
        // One expensive square beats three cheap ones.
        $squares[8]->update(['points' => 50]);

        $few = User::factory()->create(['osrs_username' => 'Heavy']);
        $many = User::factory()->create(['osrs_username' => 'Light']);

        BingoCompletion::create(['bingo_square_id' => $squares[8]->id, 'user_id' => $few->id, 'status' => 'APPROVED']);
        foreach ([3, 4, 5] as $i) {
            BingoCompletion::create(['bingo_square_id' => $squares[$i]->id, 'user_id' => $many->id, 'status' => 'APPROVED']);
        }

        $standings = $this->bingo()->standings($event, $card->fresh());

        $this->assertSame($few->id, $standings->first()['id'], 'the heavier square wins');
        $this->assertSame(50, $standings->first()['points']);
        $this->assertSame(1, $standings->first()['squares']);
    }
}
