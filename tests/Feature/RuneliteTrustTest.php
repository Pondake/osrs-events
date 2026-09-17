<?php

namespace Tests\Feature;

use App\Models\BingoCard;
use App\Models\BingoCompletion;
use App\Models\Board;
use App\Models\BoardAuthor;
use App\Models\CompletedTile;
use App\Models\Event;
use App\Models\PlayerBoard;
use App\Models\Tile;
use App\Models\User;
use App\Services\BingoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RuneliteTrustTest extends TestCase
{
    use RefreshDatabase;

    private function event(string $type): Event
    {
        return Event::create([
            'title' => 'Clan night',
            'type' => $type,
            'mode' => 'SOLO',
            'access_mode' => 'OPEN',
            'is_listed' => true,
        ]);
    }

    private function host(Event $event): User
    {
        $host = User::factory()->create();
        BoardAuthor::create(['event_id' => $event->id, 'user_id' => $host->id, 'is_owner' => true]);

        return $host;
    }

    public static function statusMatrix(): array
    {
        return [
            'no review, manual' => [false, false, 'MANUAL', 'APPROVED'],
            'no review, runelite' => [false, false, 'RUNELITE', 'APPROVED'],
            'review, manual' => [true, false, 'MANUAL', 'PENDING'],
            'review, runelite untrusted' => [true, false, 'RUNELITE', 'PENDING'],
            'review, runelite trusted' => [true, true, 'RUNELITE', 'APPROVED'],
            'review, manual on a trusting board' => [true, true, 'MANUAL', 'PENDING'],
        ];
    }

    #[Test]
    #[DataProvider('statusMatrix')]
    public function a_claim_starts_in_the_status_the_board_allows(bool $review, bool $trust, string $via, string $expected): void
    {
        $settings = ['requires_approval' => $review, 'trust_runelite_completions' => $trust];

        $this->assertSame($expected, (new Board($settings))->initialClaimStatus($via));
        $this->assertSame($expected, (new BingoCard($settings))->initialClaimStatus($via));
    }

    #[Test]
    public function trust_is_on_by_default(): void
    {
        // Owner's call 2026-09-17: a new board or card trusts RuneLite
        // completions unless a host turns it off — see the column default
        // migration.
        $board = $this->event('SNAKES_LADDERS')->board()->create(['size' => 'SIZE_5X5'])->fresh();
        $card = $this->event('BINGO')->bingoCard()->create(['size' => 3])->fresh();

        $this->assertTrue($board->trust_runelite_completions);
        $this->assertTrue($card->trust_runelite_completions);
    }

    #[Test]
    public function a_host_can_turn_trust_on_for_a_snakes_and_ladders_board(): void
    {
        $event = $this->event('SNAKES_LADDERS');
        $event->board()->create(['size' => 'SIZE_5X5']);

        $this->actingAs($this->host($event))
            ->patch("/events/{$event->id}", ['trust_runelite_completions' => true])
            ->assertSessionHasNoErrors();

        $this->assertTrue($event->board()->first()->trust_runelite_completions);
    }

    #[Test]
    public function a_host_can_turn_trust_on_for_a_bingo_card(): void
    {
        $event = $this->event('BINGO');
        $event->bingoCard()->create(['size' => 3]);

        $this->actingAs($this->host($event))
            ->patch("/events/{$event->id}", ['trust_runelite_completions' => true])
            ->assertSessionHasNoErrors();

        $this->assertTrue($event->bingoCard()->first()->trust_runelite_completions);
    }

    #[Test]
    public function a_player_cannot_change_trust(): void
    {
        $event = $this->event('BINGO');
        $event->bingoCard()->create(['size' => 3, 'trust_runelite_completions' => false]);

        $this->actingAs(User::factory()->create())
            ->patch("/events/{$event->id}", ['trust_runelite_completions' => true]);

        $this->assertFalse($event->bingoCard()->first()->trust_runelite_completions);
    }

    #[Test]
    public function a_manual_bingo_claim_is_stamped_manual(): void
    {
        $event = $this->event('BINGO');
        $card = $event->bingoCard()->create(['size' => 3]);
        app(BingoService::class)->ensureSquares($card);

        $this->actingAs(User::factory()->create())
            ->post("/events/{$event->id}/bingo/squares/{$card->squares()->first()->id}/claim", [
                'proof_url' => 'https://i.imgur.com/proof.png',
            ]);

        $this->assertSame('MANUAL', BingoCompletion::firstOrFail()->completed_via);
    }

    #[Test]
    public function the_bingo_review_queue_says_where_a_claim_came_from(): void
    {
        $event = $this->event('BINGO');
        $card = $event->bingoCard()->create(['size' => 3]);
        app(BingoService::class)->ensureSquares($card);
        [$first, $second] = $card->squares()->orderBy('position')->take(2)->get()->all();

        BingoCompletion::create(['bingo_square_id' => $first->id, 'user_id' => User::factory()->create()->id, 'status' => 'PENDING']);
        BingoCompletion::create(['bingo_square_id' => $second->id, 'user_id' => User::factory()->create()->id, 'status' => 'PENDING', 'completed_via' => 'RUNELITE']);

        $vias = collect(app(BingoService::class)->pendingQueue($card->fresh()))->pluck('completedVia', 'position')->all();

        $this->assertSame([0 => 'MANUAL', 1 => 'RUNELITE'], $vias);
    }

    #[Test]
    public function the_tile_review_queue_says_where_a_claim_came_from(): void
    {
        $event = $this->event('SNAKES_LADDERS');
        $board = $event->board()->create(['size' => 'SIZE_5X5']);
        $tile = Tile::create(['board_id' => $board->id, 'position' => 2, 'type' => 'NORMAL']);
        $playerBoard = PlayerBoard::create(['user_id' => User::factory()->create()->id, 'board_id' => $board->id, 'current_position' => 2]);

        CompletedTile::create([
            'player_board_id' => $playerBoard->id,
            'tile_id' => $tile->id,
            'completed_at' => now(),
            'completed_via' => 'RUNELITE',
            'status' => 'PENDING',
        ]);

        $this->actingAs($this->host($event))
            ->get("/events/{$event->id}")
            ->assertInertia(fn ($page) => $page->where('pending.0.completedVia', 'RUNELITE'));
    }
}
