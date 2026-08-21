<?php

namespace Tests\Feature;

use App\Events\Channels\SnakesLaddersChannel;
use App\Models\BoardAuthor;
use App\Models\Event;
use App\Models\PlayerBoard;
use App\Models\Tile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * What the app says about a player to everybody else.
 *
 * Three places publish player identities — the board page, the leaderboard,
 * and the live channel — and each builds its own payload. `User` marks only
 * `password` and `remember_token` hidden, so anything that hands over a whole
 * model hands over the email address with it. The leaderboard did exactly
 * that, and any account could open the leaderboard of any open event.
 *
 * These tests are a net rather than a description: they assert the absence of
 * a specific string, so a future payload that stops naming its fields fails
 * here rather than in somebody's inbox.
 */
class PlayerIdentityTest extends TestCase
{
    use RefreshDatabase;

    private const EMAIL = 'real.person@example.com';

    /** @return array{0: User, 1: Event} */
    private function boardWithAPlayer(): array
    {
        $player = User::factory()->create([
            'osrs_username' => 'Pondake',
            'email' => self::EMAIL,
            'discord_id' => '1234567890',
        ]);

        $event = Event::create([
            'title' => 'Board night',
            'type' => 'SNAKES_LADDERS',
            'mode' => 'SOLO',
            'access_mode' => 'OPEN',
            'is_listed' => true,
        ]);
        $event->board()->create(['size' => 'SIZE_5X5']);
        BoardAuthor::create(['event_id' => $event->id, 'user_id' => $player->id, 'is_owner' => true]);

        for ($position = 0; $position < 25; $position++) {
            Tile::create(['board_id' => $event->board->id, 'position' => $position, 'type' => 'NORMAL']);
        }

        PlayerBoard::create([
            'user_id' => $player->id,
            'board_id' => $event->board->id,
            'current_position' => 4,
        ]);

        return [$player, $event->fresh()];
    }

    /** Somebody signed in who is not on this board. */
    private function stranger(): User
    {
        return User::factory()->create(['osrs_username' => 'Stranger']);
    }

    #[Test]
    public function the_board_page_does_not_carry_a_players_email(): void
    {
        [, $event] = $this->boardWithAPlayer();

        $this->actingAs($this->stranger())
            ->get("/events/{$event->id}")
            ->assertDontSee(self::EMAIL, escape: false);
    }

    #[Test]
    public function the_leaderboard_does_not_carry_a_players_email(): void
    {
        [, $event] = $this->boardWithAPlayer();

        $this->actingAs($this->stranger())
            ->get("/events/{$event->id}/leaderboard")
            ->assertDontSee(self::EMAIL, escape: false);
    }

    /**
     * The live channel is shared by every viewer of the event, so its payload
     * is the least private thing in the app — it cannot even be narrowed per
     * reader the way a page render can.
     */
    #[Test]
    public function the_live_channel_does_not_carry_a_players_email(): void
    {
        [, $event] = $this->boardWithAPlayer();

        $payload = json_encode(app(SnakesLaddersChannel::class)->payload($event));

        $this->assertStringNotContainsString(self::EMAIL, $payload);
        $this->assertStringNotContainsString('1234567890', $payload);
    }

    /**
     * The point is to publish less, not to publish nothing — a leaderboard
     * with no names on it is not a leaderboard.
     */
    #[Test]
    public function it_still_publishes_enough_to_name_the_player(): void
    {
        [$player, $event] = $this->boardWithAPlayer();
        $player->update(['nickname' => 'Pond']);

        $this->actingAs($this->stranger())
            ->get("/events/{$event->id}/leaderboard")
            ->assertSee('Pond', escape: false);
    }
}
